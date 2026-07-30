<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;
use App\Models\AttendanceLog;

// php artisan zkteco:import
class ImportZktecoData extends Command
{
    protected $signature = 'zkteco:import';
    protected $description = 'Import ZKTeco attendance and logs from JSON storage into local database';

    public function handle()
    {
        $filename = 'zkteco_dump_backup.json';

        if (!Storage::exists($filename)) {
            $this->error("Backup file not found in storage/app/{$filename}!");
            return;
        }

        $this->info("Reading backup file...");
        $data = json_decode(Storage::get($filename), true);
        $rawAttendances = $data['attendances'] ?? [];

        if (empty($rawAttendances)) {
            $this->warn("No attendance records found in the backup file.");
            return;
        }

        // Group raw logs by userid and date
        $grouped = [];
        foreach ($rawAttendances as $att) {
            $recordedAt = $att['recorded_at'] ?? now();
            $date = date('Y-m-d', strtotime($recordedAt));
            $userId = $att['userid'] ?? $att['user_id'] ?? 1;

            $grouped[$userId][$date][] = $att;
        }

        $importedAttendances = 0;
        $importedLogs = 0;

        $bar = $this->output->createProgressBar(count($rawAttendances));
        $bar->start();

        foreach ($grouped as $userId => $dates) {
            foreach ($dates as $date => $punches) {
                // Sort punches chronologically for the day
                usort($punches, function ($a, $b) {
                    return strcmp($a['recorded_at'], $b['recorded_at']);
                });

                // Extract the user's name from the first available record in the day's punches
                $userName = 'N/A';
                foreach ($punches as $punch) {
                    if (!empty($punch['name'])) {
                        $userName = $punch['name'];
                        break;
                    } elseif (!empty($punch['username'])) {
                        $userName = $punch['username'];
                        break;
                    } elseif (!empty($punch['user_name'])) {
                        $userName = $punch['user_name'];
                        break;
                    }
                }

                // Create or get the parent Attendance record for the day (including user_name)
                $attendance = Attendance::updateOrCreate(
                    [
                        'employee_id' => $userId,
                        'attendance_date' => $date,
                    ],
                    [
                        'organization_id' => 1, // Change if needed
                        'user_name' => $userName, // Store the extracted name
                        'status' => 'present',
                    ]
                );
                $importedAttendances++;

                // Process check-in / check-out pairs into logs
                $checkInTime = null;

                foreach ($punches as $punch) {
                    $time = date('H:i:s', strtotime($punch['recorded_at']));
                    $stateName = strtolower($punch['state_name'] ?? '');
                    $isCheckOut = str_contains($stateName, 'out') || ($punch['state'] ?? 0) == 1;

                    if (!$isCheckOut) {
                        if ($checkInTime) {
                            AttendanceLog::create([
                                'attendance_id' => $attendance->id,
                                'check_in_time' => $checkInTime,
                                'check_out_time' => null,
                            ]);
                            $importedLogs++;
                        }
                        $checkInTime = $time;
                    } else {
                        if ($checkInTime) {
                            AttendanceLog::create([
                                'attendance_id' => $attendance->id,
                                'check_in_time' => $checkInTime,
                                'check_out_time' => $time,
                            ]);
                            $importedLogs++;
                            $checkInTime = null;
                        } else {
                            AttendanceLog::create([
                                'attendance_id' => $attendance->id,
                                'check_in_time' => $time,
                                'check_out_time' => $time,
                            ]);
                            $importedLogs++;
                        }
                    }
                    $bar->advance();
                }

                if ($checkInTime) {
                    AttendanceLog::create([
                        'attendance_id' => $attendance->id,
                        'check_in_time' => $checkInTime,
                        'check_out_time' => null,
                    ]);
                    $importedLogs++;
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully imported {$importedAttendances} attendance days and {$importedLogs} session logs!");
    }
}
// class ImportZktecoData extends Command
// {
//     protected $signature = 'zkteco:import';
//     protected $description = 'Import ZKTeco attendance and logs from JSON storage into local database';

//     public function handle()
//     {
//         $filename = 'zkteco_dump_backup.json';

//         if (!Storage::exists($filename)) {
//             $this->error("Backup file not found in storage/app/{$filename}!");
//             return;
//         }

//         $this->info("Reading backup file...");
//         $data = json_decode(Storage::get($filename), true);
//         $rawAttendances = $data['attendances'] ?? [];

//         if (empty($rawAttendances)) {
//             $this->warn("No attendance records found in the backup file.");
//             return;
//         }

//         // Group raw logs by userid and date
//         $grouped = [];
//         foreach ($rawAttendances as $att) {
//             $recordedAt = $att['recorded_at'] ?? now();
//             $date = date('Y-m-d', strtotime($recordedAt));
//             $userId = $att['userid'] ?? $att['user_id'] ?? 1;

//             $grouped[$userId][$date][] = $att;
//         }

//         $importedAttendances = 0;
//         $importedLogs = 0;

//         $bar = $this->output->createProgressBar(count($rawAttendances));
//         $bar->start();

//         foreach ($grouped as $userId => $dates) {
//             foreach ($dates as $date => $punches) {
//                 // Sort punches chronologically for the day
//                 usort($punches, function ($a, $b) {
//                     return strcmp($a['recorded_at'], $b['recorded_at']);
//                 });

//                 // Create or get the parent Attendance record for the day
//                 $attendance = Attendance::firstOrCreate(
//                     [
//                         'employee_id' => $userId,
//                         'attendance_date' => $date,
//                     ],
//                     [
//                         'organization_id' => 1, // Change if needed
//                         'status' => 'present',
//                     ]
//                 );
//                 $importedAttendances++;

//                 // Process check-in / check-out pairs into logs
//                 $checkInTime = null;

//                 foreach ($punches as $punch) {
//                     $time = date('H:i:s', strtotime($punch['recorded_at']));
//                     $stateName = strtolower($punch['state_name'] ?? '');
//                     $isCheckOut = str_contains($stateName, 'out') || ($punch['state'] ?? 0) == 1;

//                     if (!$isCheckOut) {
//                         if ($checkInTime) {
//                             AttendanceLog::create([
//                                 'attendance_id' => $attendance->id,
//                                 'check_in_time' => $checkInTime,
//                                 'check_out_time' => null,
//                             ]);
//                             $importedLogs++;
//                         }
//                         $checkInTime = $time;
//                     } else {
//                         if ($checkInTime) {
//                             AttendanceLog::create([
//                                 'attendance_id' => $attendance->id,
//                                 'check_in_time' => $checkInTime,
//                                 'check_out_time' => $time,
//                             ]);
//                             $importedLogs++;
//                             $checkInTime = null;
//                         } else {
//                             AttendanceLog::create([
//                                 'attendance_id' => $attendance->id,
//                                 'check_in_time' => $time,
//                                 'check_out_time' => $time,
//                             ]);
//                             $importedLogs++;
//                         }
//                     }
//                     $bar->advance();
//                 }

//                 if ($checkInTime) {
//                     AttendanceLog::create([
//                         'attendance_id' => $attendance->id,
//                         'check_in_time' => $checkInTime,
//                         'check_out_time' => null,
//                     ]);
//                     $importedLogs++;
//                 }
//             }
//         }

//         $bar->finish();
//         $this->newLine(2);
//         $this->info("Successfully imported {$importedAttendances} attendance days and {$importedLogs} session logs!");
//     }
// }