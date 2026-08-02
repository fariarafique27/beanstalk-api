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
    // protected function syncAttendance(ZktecoService $zkteco): void
    //     {
    //         $entries = $zkteco->fetchAttendance();

    //         if (empty($entries)) {
    //             $this->warn('No attendance records returned by the device.');
    //             return;
    //         }

    //         // Define cutoff: Only process records from the past 7 days
    //         $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

    //         // Preload all ZKTeco users into memory keyed by 'uid' (string)
    //         $zkUsers = ZktecoUser::all()->keyBy('uid');

    //         $insertedCount = 0;
    //         $duplicateCount = 0;
    //         $skippedCount = 0;
    //         $oldRecordCount = 0;

    //         foreach ($entries as $entry) {
    //             $record = $entry['record'] ?? null;

    //             if (!$record) {
    //                 continue;
    //             }

    //             $timestamp = Carbon::instance($record->recordedAt); // DateTimeImmutable -> Carbon

    //             // Skip records older than 1 week
    //             if ($timestamp->lessThan($oneWeekAgo)) {
    //                 $oldRecordCount++;
    //                 continue;
    //             }

    //             $uid = (string) $record->uid;
    //             $zkUser = $zkUsers->get($uid);

    //             if (!$zkUser) {
    //                 $skippedCount++;
    //                 continue;
    //             }

    //             $date = $timestamp->toDateString();
    //             $punchName = $record->punchState->name ?? null; // 'CheckIn' | 'CheckOut' | etc
    //             $isCheckIn = stripos($punchName ?? '', 'in') !== false
    //                 && stripos($punchName ?? '', 'checkout') === false;

    //             try {
    //                 DB::transaction(function () use (
    //                     $zkUser, $date, $timestamp, $punchName, $isCheckIn,
    //                     &$insertedCount, &$duplicateCount
    //                 ) {
    //                     $attendance = Attendance::firstOrCreate(
    //                         [
    //                             'employee_id' => $zkUser->id,
    //                             'attendance_date' => $date,
    //                         ],
    //                         [
    //                             'organization_id' => 1,
    //                             'status' => 'Present',
    //                             'user_name' => $zkUser->name,
    //                         ]
    //                     );

    //                     if ($isCheckIn) {
    //                         $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
    //                             ->where('check_in_time', $timestamp)
    //                             ->exists();

    //                         if ($exists) {
    //                             $duplicateCount++;
    //                             return;
    //                         }

    //                         AttendanceLog::create([
    //                             'attendance_id' => $attendance->id,
    //                             'zkteco_user_id' => $zkUser->id,
    //                             'check_in_time' => $timestamp,
    //                             'check_in_punch_state' => $punchName,
    //                         ]);
    //                     } else {
    //                         $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
    //                             ->where('check_out_time', $timestamp)
    //                             ->exists();

    //                         if ($exists) {
    //                             $duplicateCount++;
    //                             return;
    //                         }

    //                         AttendanceLog::create([
    //                             'attendance_id' => $attendance->id,
    //                             'zkteco_user_id' => $zkUser->id,
    //                             'check_out_time' => $timestamp,
    //                             'check_out_punch_state' => $punchName,
    //                         ]);
    //                     }

    //                     $insertedCount++;
    //                 });
    //             } catch (Throwable $e) {
    //                 $this->warn("Skipped one record for uid {$uid}: {$e->getMessage()}");
    //             }
    //         }

    //         $this->info(
    //             "{$insertedCount} new punch(es) inserted, "
    //             . "{$duplicateCount} already existed, "
    //             . "{$oldRecordCount} older-than-a-week record(s) ignored, "
    //             . "{$skippedCount} unrecognized user(s) skipped."
    //         );
    //     }

    protected function syncAttendance(ZktecoService $zkteco): void
    {
        $entries = $zkteco->fetchAttendance();

        if (empty($entries)) {
            $this->warn('No attendance records returned by the device.');
            return;
        }

        // Define cutoff: Only process records from the past 7 days
        $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

        // Preload all ZKTeco users into memory keyed by 'uid' (string)
        $zkUsers = ZktecoUser::all()->keyBy('uid');

        // 1. Filter out invalid, unrecognized, or old records first
        $validEntries = [];
        $skippedCount = 0;
        $oldRecordCount = 0;

        foreach ($entries as $entry) {
            $record = $entry['record'] ?? null;
            if (!$record) {
                continue;
            }

            $timestamp = Carbon::instance($record->recordedAt);

            if ($timestamp->lessThan($oneWeekAgo)) {
                $oldRecordCount++;
                continue;
            }

            $uid = (string) $record->uid;
            $zkUser = $zkUsers->get($uid);

            if (!$zkUser) {
                $skippedCount++;
                continue;
            }

            $validEntries[] = [
                'zkUser' => $zkUser,
                'timestamp' => $timestamp,
                'date' => $timestamp->toDateString(),
                'original_state' => $record->punchState->name ?? 'Unknown',
            ];
        }

        // 2. Sort all entries strictly by timestamp chronologically (Oldest to Newest)
        usort($validEntries, fn ($a, $b) => $a['timestamp']->greaterThan($b['timestamp']) ? 1 : -1);

        // 3. Group and sequence entries per user per day to enforce alternating In/Out
        // Track running punch counts per user per day: [userId][date] => count
        $userDailyPunchCounts = [];
        
        $insertedCount = 0;
        $duplicateCount = 0;

        foreach ($validEntries as $item) {
            $zkUser = $item['zkUser'];
            $date = $item['date'];
            $timestamp = $item['timestamp'];
            $originalState = $item['original_state'];

            // Initialize or increment sequence counter for this user on this specific day
            if (!isset($userDailyPunchCounts[$zkUser->id][$date])) {
                $userDailyPunchCounts[$zkUser->id][$date] = 0;
            }
            $userDailyPunchCounts[$zkUser->id][$date]++;

            $sequenceNumber = $userDailyPunchCounts[$zkUser->id][$date];

            // Odd sequence (1, 3, 5...) = Check-In, Even sequence (2, 4, 6...) = Check-Out
            $isCheckIn = ($sequenceNumber % 2 !== 0);

            try {
                DB::transaction(function () use (
                    $zkUser, $date, $timestamp, $isCheckIn, $sequenceNumber, $originalState,
                    &$insertedCount, &$duplicateCount
                ) {
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
                           // 'check_in_punch_state' => "Auto(Seq:{$sequenceNumber}) - " . $originalState,
                           'check_in_punch_state' => "Check In at " . $timestamp->format('h:i A'),
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
                           // 'check_out_punch_state' => "Auto(Seq:{$sequenceNumber}) - " . $originalState,
                           'check_out_punch_state' => "Check Out at " . $timestamp->format('h:i A'),
                        ]);
                    }

                    $insertedCount++;
                });
            } catch (Throwable $e) {
                $this->warn("Skipped record for user ID {$zkUser->id}: {$e->getMessage()}");
            }
        }

        $this->info(
            "{$insertedCount} new punch(es) inserted, "
            . "{$duplicateCount} already existed, "
            . "{$oldRecordCount} older-than-a-week record(s) ignored, "
            . "{$skippedCount} unrecognized user(s) skipped."
        );
    }
}