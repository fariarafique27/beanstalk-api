<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\ZktecoUser;
use App\Services\ZktecoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

// php artisan zkteco:sync
class ZktecoSyncCommand extends Command
{
    protected $signature = 'zkteco:sync
        {--users-only : Only sync users}
        {--attendance-only : Only sync attendance}';

    protected $description = 'Pull users and attendance logs from the ZKTeco device and store them in the database';

    public function handle(ZktecoService $zkteco): int
    {
        $syncAttendance = ! $this->option('users-only');
        $syncUsers = ! $this->option('attendance-only');

        try {
            if ($syncUsers) {
                $this->syncUsers($zkteco);
            }

            if ($syncAttendance) {
                $this->syncAttendance($zkteco);
            }
        } catch (Throwable $e) {
            $this->error("ZKTeco sync failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function syncUsers(ZktecoService $zkteco): void
    {
        $users = $zkteco->fetchUsers();

        if (empty($users)) {
            $this->warn('No users returned by the device.');
            return;
        }

        $rows = collect($users)->map(fn ($user) => [
            'uid' => (string) $user->uid,
            'user_id' => (string) $user->userId,
            'name' => trim($user->name),
            'role' => $user->privilege->value ?? 0,
            'cardno' => trim((string) ($user->cardNumber ?? '')) ?: null,
            'password' => trim((string) ($user->password ?? '')) ?: null,
            'group_id' => $user->groupId ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        ZktecoUser::upsert(
            $rows,
            ['uid'],
            ['user_id', 'name', 'role', 'cardno', 'password', 'group_id', 'updated_at']
        );

        $this->info(count($rows) . ' zkteco user(s) synced via upsert.');
    }

    /**
     * Store every raw punch as its own row. No in/out pairing here --
     * a CheckIn punch fills check_in_time (check_out_time stays null),
     * a CheckOut punch fills check_out_time (check_in_time stays null).
     * Re-running this command never duplicates rows already saved --
     * only genuinely new punches get inserted.
     */
    protected function syncAttendance(ZktecoService $zkteco): void
    {
        $entries = $zkteco->fetchAttendance();

        if (empty($entries)) {
            $this->warn('No attendance records returned by the device.');
            return;
        }

        // Preload all ZKTeco users into memory keyed by 'uid' (string)
        $zkUsers = ZktecoUser::all()->keyBy('uid');

        $insertedCount = 0;
        $duplicateCount = 0;
        $skippedCount = 0;

        foreach ($entries as $entry) {
            $record = $entry['record'] ?? null;

            if (!$record) {
                continue;
            }

            // The record always carries its own uid, even when the
            // "user" object comes back NULL (this happens in real data --
            // see uid 81 in your dump). Never rely on $entry['user'].
            $uid = (string) $record->uid;
            $zkUser = $zkUsers->get($uid);

            if (!$zkUser) {
                // User hasn't been synced via syncUsers() yet -- skip,
                // it'll pick up correctly next run once users are synced.
                $skippedCount++;
                continue;
            }

            $timestamp = Carbon::instance($record->recordedAt); // DateTimeImmutable -> Carbon
            $date = $timestamp->toDateString();

            $punchName = $record->punchState->name ?? null; // 'CheckIn' | 'CheckOut' | etc
            $isCheckIn = stripos($punchName ?? '', 'in') !== false
                && stripos($punchName ?? '', 'checkout') === false;

            try {
                DB::transaction(function () use (
                    $zkUser, $date, $timestamp, $punchName, $isCheckIn,
                    &$insertedCount, &$duplicateCount
                ) {
                    // One Attendance "day sheet" per employee per day.
                    $attendance = Attendance::firstOrCreate(
                        [
                            'employee_id' => $zkUser->id,
                            'attendance_date' => $date,
                        ],
                        [
                            'organization_id' => 1,
                            'status' => 'Present',
                            'user_name' => $zkUser->name,
                        ]
                    );

                    if ($isCheckIn) {
                        $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
                            ->where('check_in_time', $timestamp)
                            ->exists();

                        if ($exists) {
                            $duplicateCount++;
                            return;
                        }

                        AttendanceLog::create([
                            'attendance_id' => $attendance->id,
                            'zkteco_user_id' => $zkUser->id,
                            'check_in_time' => $timestamp,
                            'check_in_punch_state' => $punchName,
                        ]);
                    } else {
                        $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
                            ->where('check_out_time', $timestamp)
                            ->exists();

                        if ($exists) {
                            $duplicateCount++;
                            return;
                        }

                        AttendanceLog::create([
                            'attendance_id' => $attendance->id,
                            'zkteco_user_id' => $zkUser->id,
                            'check_out_time' => $timestamp,
                            'check_out_punch_state' => $punchName,
                        ]);
                    }

                    $insertedCount++;
                });
            } catch (Throwable $e) {
                $this->warn("Skipped one record for uid {$uid}: {$e->getMessage()}");
            }
        }

        $this->info(
            "{$insertedCount} new punch(es) inserted, "
            . "{$duplicateCount} already existed, "
            . "{$skippedCount} unrecognized user(s) skipped."
        );
    }
}