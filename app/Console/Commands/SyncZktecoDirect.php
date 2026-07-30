<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;

// php artisan zkteco:sync-direct
class SyncZktecoDirect extends Command
{
    protected $signature = 'zkteco:sync-direct';
    protected $description = 'Fetch raw attendance directly from zktecko database, backup to storage, and sync locally';

    public function handle()
    {
        $this->info("Fetching records from zktecko database...");

        try {
            $referenceAttendances = DB::select('SELECT * FROM zktecko.zkteco_attendances');
            $referenceUsers = DB::select('SELECT * FROM zktecko.zkteco_users');
        } catch (\Exception $e) {
            $this->error("Failed to connect or query zktecko database: " . $e->getMessage());
            return;
        }

        if (empty($referenceAttendances)) {
            $this->warn("No records found in zktecko.zkteco_attendances.");
            return;
        }

        // 1. Save a backup JSON file to storage/app/
        $this->info("Saving backup file to storage/app/zkteco_dump_backup.json...");
        $payload = [
            'users' => $referenceUsers,
            'attendances' => $referenceAttendances,
        ];
        Storage::put('zkteco_dump_backup.json', json_encode($payload, JSON_PRETTY_PRINT));

        // 2. Sync into local database
        $this->info("Syncing records into local database tables...");
        $bar = $this->output->createProgressBar(count($referenceAttendances));
        $bar->start();

        $count = 0;
        foreach ($referenceAttendances as $attendance) {
            $attArray = (array) $attendance;

            Attendance::updateOrCreate(
                ['id' => $attArray['id'] ?? null], 
                [
                    'organization_id' => $attArray['organization_id'] ?? 1,
                    'employee_id' => $attArray['employee_id'] ?? $attArray['user_id'] ?? $attArray['userid'] ?? 1,
                    'attendance_date' => isset($attArray['recorded_at']) ? date('Y-m-d', strtotime($attArray['recorded_at'])) : now(),
                ]
            );

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully synced {$count} records to DB and created storage/app/zkteco_dump_backup.json!");
    }
}