<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\OrganizationDevice;
use App\Models\ZktecoUser;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncZktecoDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected OrganizationDevice $device) {}

    public function handle(): void
    {
        $zkteco = new ZktecoService($this->device->ip, $this->device->port);

        $this->syncUsers($zkteco);
        $this->syncAttendance($zkteco);

        $this->device->update(['last_synced_at' => now()]);
    }

    protected function syncUsers(ZktecoService $zkteco): void
    {
        $users = $zkteco->fetchUsers();
        if (empty($users)) {
            return;
        }

        $orgId = $this->device->organization_id;

        $rows = collect($users)->map(fn ($user) => [
            'organization_id' => $orgId,
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
            ['organization_id', 'uid'],
            ['user_id', 'name', 'role', 'cardno', 'password', 'group_id', 'updated_at']
        );
    }

    protected function syncAttendance(ZktecoService $zkteco): void
    {
        $entries = $zkteco->fetchAttendance();
        if (empty($entries)) {
            return;
        }

        $orgId = $this->device->organization_id;
        $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

        // Scoped to THIS org only
        $zkUsers = ZktecoUser::where('organization_id', $orgId)->get()->keyBy('uid');

        // 1. Filter out invalid, unrecognized, or old records first
        $validEntries = [];

        foreach ($entries as $entry) {
            $record = $entry['record'] ?? null;
            if (!$record) {
                continue;
            }

            $timestamp = Carbon::instance($record->recordedAt);
            if ($timestamp->lessThan($oneWeekAgo)) {
                continue;
            }

            $uid = (string) $record->uid;
            $zkUser = $zkUsers->get($uid);
            if (!$zkUser) {
                continue;
            }

            $validEntries[] = [
                'zkUser' => $zkUser,
                'timestamp' => $timestamp,
                'date' => $timestamp->toDateString(),
            ];
        }

        // 2. Sort all entries strictly by timestamp chronologically (Oldest to Newest)
        usort($validEntries, fn ($a, $b) => $a['timestamp']->greaterThan($b['timestamp']) ? 1 : -1);

        // 3. Group and sequence entries per user per day to enforce alternating In/Out
        $userDailyPunchCounts = [];

        foreach ($validEntries as $item) {
            $zkUser = $item['zkUser'];
            $date = $item['date'];
            $timestamp = $item['timestamp'];

            if (!isset($userDailyPunchCounts[$zkUser->id][$date])) {
                $userDailyPunchCounts[$zkUser->id][$date] = 0;
            }
            $userDailyPunchCounts[$zkUser->id][$date]++;

            $sequenceNumber = $userDailyPunchCounts[$zkUser->id][$date];

            // Odd sequence (1, 3, 5...) = Check-In, Even sequence (2, 4, 6...) = Check-Out
            $isCheckIn = ($sequenceNumber % 2 !== 0);

            try {
                DB::transaction(function () use ($zkUser, $date, $timestamp, $isCheckIn, $orgId) {
                    $attendance = Attendance::firstOrCreate(
                        ['employee_id' => $zkUser->id, 'attendance_date' => $date],
                        ['organization_id' => $orgId, 'status' => 'Present', 'user_name' => $zkUser->name]
                    );

                    if ($isCheckIn) {
                        $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
                            ->where('check_in_time', $timestamp)
                            ->exists();

                        if ($exists) {
                            return;
                        }

                        AttendanceLog::create([
                            'attendance_id' => $attendance->id,
                            'zkteco_user_id' => $zkUser->id,
                            'check_in_time' => $timestamp,
                            'check_in_punch_state' => 'Check In at ' . $timestamp->format('h:i A'),
                        ]);
                    } else {
                        $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
                            ->where('check_out_time', $timestamp)
                            ->exists();

                        if ($exists) {
                            return;
                        }

                        AttendanceLog::create([
                            'attendance_id' => $attendance->id,
                            'zkteco_user_id' => $zkUser->id,
                            'check_out_time' => $timestamp,
                            'check_out_punch_state' => 'Check Out at ' . $timestamp->format('h:i A'),
                        ]);
                    }
                });
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}