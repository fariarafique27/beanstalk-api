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
use App\Models\OrganizationDevice;

// php artisan zkteco:sync
// class ZktecoSyncCommand extends Command
// {
//     protected $signature = 'zkteco:sync
//         {--users-only : Only sync users}
//         {--attendance-only : Only sync attendance}';

//     protected $description = 'Pull users and attendance logs from the ZKTeco device and store them in the database';

//     public function handle(ZktecoService $zkteco): int
//     {
//         $syncAttendance = ! $this->option('users-only');
//         $syncUsers = ! $this->option('attendance-only');

//         try {
//             if ($syncUsers) {
//                 $this->syncUsers($zkteco);
//             }

//             if ($syncAttendance) {
//                 $this->syncAttendance($zkteco);
//             }
//         } catch (Throwable $e) {
//             $this->error("ZKTeco sync failed: {$e->getMessage()}");
//             return self::FAILURE;
//         }

//         return self::SUCCESS;
//     }

//     protected function syncUsers(ZktecoService $zkteco): void
//     {
//         $users = $zkteco->fetchUsers();

//         if (empty($users)) {
//             $this->warn('No users returned by the device.');
//             return;
//         }

//         $rows = collect($users)->map(fn ($user) => [
//             'uid' => (string) $user->uid,
//             'user_id' => (string) $user->userId,
//             'name' => trim($user->name),
//             'role' => $user->privilege->value ?? 0,
//             'cardno' => trim((string) ($user->cardNumber ?? '')) ?: null,
//             'password' => trim((string) ($user->password ?? '')) ?: null,
//             'group_id' => $user->groupId ?? 0,
//             'created_at' => now(),
//             'updated_at' => now(),
//         ])->values()->all();

//         ZktecoUser::upsert(
//             $rows,
//             ['uid'],
//             ['user_id', 'name', 'role', 'cardno', 'password', 'group_id', 'updated_at']
//         );

//         $this->info(count($rows) . ' zkteco user(s) synced via upsert.');
//     }

//     /**
//      * Store every raw punch as its own row. No in/out pairing here --
//      * a CheckIn punch fills check_in_time (check_out_time stays null),
//      * a CheckOut punch fills check_out_time (check_in_time stays null).
//      * Re-running this command never duplicates rows already saved --
//      * only genuinely new punches get inserted.
//      */
//     // protected function syncAttendance(ZktecoService $zkteco): void
//     //     {
//     //         $entries = $zkteco->fetchAttendance();

//     //         if (empty($entries)) {
//     //             $this->warn('No attendance records returned by the device.');
//     //             return;
//     //         }

//     //         // Define cutoff: Only process records from the past 7 days
//     //         $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

//     //         // Preload all ZKTeco users into memory keyed by 'uid' (string)
//     //         $zkUsers = ZktecoUser::all()->keyBy('uid');

//     //         $insertedCount = 0;
//     //         $duplicateCount = 0;
//     //         $skippedCount = 0;
//     //         $oldRecordCount = 0;

//     //         foreach ($entries as $entry) {
//     //             $record = $entry['record'] ?? null;

//     //             if (!$record) {
//     //                 continue;
//     //             }

//     //             $timestamp = Carbon::instance($record->recordedAt); // DateTimeImmutable -> Carbon

//     //             // Skip records older than 1 week
//     //             if ($timestamp->lessThan($oneWeekAgo)) {
//     //                 $oldRecordCount++;
//     //                 continue;
//     //             }

//     //             $uid = (string) $record->uid;
//     //             $zkUser = $zkUsers->get($uid);

//     //             if (!$zkUser) {
//     //                 $skippedCount++;
//     //                 continue;
//     //             }

//     //             $date = $timestamp->toDateString();
//     //             $punchName = $record->punchState->name ?? null; // 'CheckIn' | 'CheckOut' | etc
//     //             $isCheckIn = stripos($punchName ?? '', 'in') !== false
//     //                 && stripos($punchName ?? '', 'checkout') === false;

//     //             try {
//     //                 DB::transaction(function () use (
//     //                     $zkUser, $date, $timestamp, $punchName, $isCheckIn,
//     //                     &$insertedCount, &$duplicateCount
//     //                 ) {
//     //                     $attendance = Attendance::firstOrCreate(
//     //                         [
//     //                             'employee_id' => $zkUser->id,
//     //                             'attendance_date' => $date,
//     //                         ],
//     //                         [
//     //                             'organization_id' => 1,
//     //                             'status' => 'Present',
//     //                             'user_name' => $zkUser->name,
//     //                         ]
//     //                     );

//     //                     if ($isCheckIn) {
//     //                         $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
//     //                             ->where('check_in_time', $timestamp)
//     //                             ->exists();

//     //                         if ($exists) {
//     //                             $duplicateCount++;
//     //                             return;
//     //                         }

//     //                         AttendanceLog::create([
//     //                             'attendance_id' => $attendance->id,
//     //                             'zkteco_user_id' => $zkUser->id,
//     //                             'check_in_time' => $timestamp,
//     //                             'check_in_punch_state' => $punchName,
//     //                         ]);
//     //                     } else {
//     //                         $exists = AttendanceLog::where('zkteco_user_id', $zkUser->id)
//     //                             ->where('check_out_time', $timestamp)
//     //                             ->exists();

//     //                         if ($exists) {
//     //                             $duplicateCount++;
//     //                             return;
//     //                         }

//     //                         AttendanceLog::create([
//     //                             'attendance_id' => $attendance->id,
//     //                             'zkteco_user_id' => $zkUser->id,
//     //                             'check_out_time' => $timestamp,
//     //                             'check_out_punch_state' => $punchName,
//     //                         ]);
//     //                     }

//     //                     $insertedCount++;
//     //                 });
//     //             } catch (Throwable $e) {
//     //                 $this->warn("Skipped one record for uid {$uid}: {$e->getMessage()}");
//     //             }
//     //         }

//     //         $this->info(
//     //             "{$insertedCount} new punch(es) inserted, "
//     //             . "{$duplicateCount} already existed, "
//     //             . "{$oldRecordCount} older-than-a-week record(s) ignored, "
//     //             . "{$skippedCount} unrecognized user(s) skipped."
//     //         );
//     //     }

//     protected function syncAttendance(ZktecoService $zkteco): void
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

//         // 1. Filter out invalid, unrecognized, or old records first
//         $validEntries = [];
//         $skippedCount = 0;
//         $oldRecordCount = 0;

//         foreach ($entries as $entry) {
//             $record = $entry['record'] ?? null;
//             if (!$record) {
//                 continue;
//             }

//             $timestamp = Carbon::instance($record->recordedAt);

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

//             $validEntries[] = [
//                 'zkUser' => $zkUser,
//                 'timestamp' => $timestamp,
//                 'date' => $timestamp->toDateString(),
//                 'original_state' => $record->punchState->name ?? 'Unknown',
//             ];
//         }

//         // 2. Sort all entries strictly by timestamp chronologically (Oldest to Newest)
//         usort($validEntries, fn ($a, $b) => $a['timestamp']->greaterThan($b['timestamp']) ? 1 : -1);

//         // 3. Group and sequence entries per user per day to enforce alternating In/Out
//         // Track running punch counts per user per day: [userId][date] => count
//         $userDailyPunchCounts = [];
        
//         $insertedCount = 0;
//         $duplicateCount = 0;

//         foreach ($validEntries as $item) {
//             $zkUser = $item['zkUser'];
//             $date = $item['date'];
//             $timestamp = $item['timestamp'];
//             $originalState = $item['original_state'];

//             // Initialize or increment sequence counter for this user on this specific day
//             if (!isset($userDailyPunchCounts[$zkUser->id][$date])) {
//                 $userDailyPunchCounts[$zkUser->id][$date] = 0;
//             }
//             $userDailyPunchCounts[$zkUser->id][$date]++;

//             $sequenceNumber = $userDailyPunchCounts[$zkUser->id][$date];

//             // Odd sequence (1, 3, 5...) = Check-In, Even sequence (2, 4, 6...) = Check-Out
//             $isCheckIn = ($sequenceNumber % 2 !== 0);

//             try {
//                 DB::transaction(function () use (
//                     $zkUser, $date, $timestamp, $isCheckIn, $sequenceNumber, $originalState,
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
//                            // 'check_in_punch_state' => "Auto(Seq:{$sequenceNumber}) - " . $originalState,
//                            'check_in_punch_state' => "Check In at " . $timestamp->format('h:i A'),
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
//                            // 'check_out_punch_state' => "Auto(Seq:{$sequenceNumber}) - " . $originalState,
//                            'check_out_punch_state' => "Check Out at " . $timestamp->format('h:i A'),
//                         ]);
//                     }

//                     $insertedCount++;
//                 });
//             } catch (Throwable $e) {
//                 $this->warn("Skipped record for user ID {$zkUser->id}: {$e->getMessage()}");
//             }
//         }

//         $this->info(
//             "{$insertedCount} new punch(es) inserted, "
//             . "{$duplicateCount} already existed, "
//             . "{$oldRecordCount} older-than-a-week record(s) ignored, "
//             . "{$skippedCount} unrecognized user(s) skipped."
//         );
//     }
// }



class ZktecoSyncCommand extends Command
{
    protected $signature = 'zkteco:sync
        {--users-only : Only sync users}
        {--attendance-only : Only sync attendance}
        {--organization= : Sync only this organization ID}';

    protected $description = 'Pull users and attendance logs from every org\'s ZKTeco device and store them, scoped per organization';

    public function handle(): int
    {
        $syncAttendance = ! $this->option('users-only');
        $syncUsers = ! $this->option('attendance-only');
        $orgFilter = $this->option('organization');

        // Only devices marked active are attempted. Flip is_active to false
        // for any org that shouldn't be synced (no device-management
        // permission, device retired, etc.) instead of deleting the row.
        $devicesQuery = OrganizationDevice::where('is_active', true);
        if ($orgFilter) {
            $devicesQuery->where('organization_id', $orgFilter);
        }
        $devices = $devicesQuery->get();

        if ($devices->isEmpty()) {
            $this->warn('No active devices found to sync.');
            return self::SUCCESS;
        }

        foreach ($devices as $device) {
            $this->info("Syncing organization {$device->organization_id} ({$device->ip}:{$device->port})...");

            $zkteco = new ZktecoService($device->ip, $device->port);

            try {
                if ($syncUsers) {
                    $this->syncUsers($zkteco, $device->organization_id);
                }
                if ($syncAttendance) {
                    $this->syncAttendance($zkteco, $device->organization_id);
                }

                $device->update(['last_synced_at' => now()]);
            } catch (Throwable $e) {
                $this->error("Sync failed for org {$device->organization_id}: {$e->getMessage()}");
                report($e);
                // continue to next device rather than aborting the whole run
            }
        }

        return self::SUCCESS;
    }

    protected function syncUsers(ZktecoService $zkteco, int $organizationId): void
    {
        $users = $zkteco->fetchUsers();

        if (empty($users)) {
            $this->warn("No users returned by the device for org {$organizationId}.");
            return;
        }

        $rows = collect($users)->map(fn ($user) => [
            'organization_id' => $organizationId,
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

        $this->info(count($rows) . " zkteco user(s) synced for org {$organizationId}.");
    }

    /**
     * Store every raw punch as its own row. No in/out pairing based on
     * device-reported punch state -- instead, punches are sorted
     * chronologically per user per day and assigned by position:
     * 1st = check-in, 2nd = check-out, 3rd = check-in, etc.
     *
     * This is deterministic and stateless: it's recomputed fresh from
     * the full punch list every run (the device returns full history,
     * not just new punches), so re-running never shifts existing
     * pairings around -- odd punches are always check-ins, even punches
     * are always check-outs, regardless of what's already in the DB.
     * The exists() checks below just prevent duplicate rows.
     */
    protected function syncAttendance(ZktecoService $zkteco, int $organizationId): void
    {
        $entries = $zkteco->fetchAttendance();

        if (empty($entries)) {
            $this->warn("No attendance records returned for org {$organizationId}.");
            return;
        }

        // Define cutoff: Only process records from the past 7 days
        $oneWeekAgo = Carbon::now()->subWeek()->startOfDay();

        // Scoped to THIS org only — the key multi-tenant fix.
        $zkUsers = ZktecoUser::where('organization_id', $organizationId)->get()->keyBy('uid');

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
        $userDailyPunchCounts = [];

        $insertedCount = 0;
        $duplicateCount = 0;

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
                DB::transaction(function () use (
                    $zkUser, $date, $timestamp, $isCheckIn, $organizationId,
                    &$insertedCount, &$duplicateCount
                ) {
                    $attendance = Attendance::firstOrCreate(
                        ['employee_id' => $zkUser->id, 'attendance_date' => $date],
                        ['organization_id' => $organizationId, 'status' => 'Present', 'user_name' => $zkUser->name]
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
                            'check_in_punch_state' => 'Check In at ' . $timestamp->format('h:i A'),
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
                            'check_out_punch_state' => 'Check Out at ' . $timestamp->format('h:i A'),
                        ]);
                    }

                    $insertedCount++;
                });
            } catch (Throwable $e) {
                $this->warn("Skipped record for user ID {$zkUser->id}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info(
            "Org {$organizationId}: {$insertedCount} new punch(es) inserted, "
            . "{$duplicateCount} already existed, "
            . "{$oldRecordCount} older-than-a-week record(s) ignored, "
            . "{$skippedCount} unrecognized user(s) skipped."
        );
    }
}