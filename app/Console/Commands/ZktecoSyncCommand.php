<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\ZktecoUser;
use App\Services\ZktecoService;
use Illuminate\Console\Command;
use Throwable;

class ZktecoSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:sync
        {--users-only : Only sync users}
        {--attendance-only : Only sync attendance}';

    /**
     * The console command description.
     *
     * @var string
     */
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

        $rows = collect($users)->map(fn ($user) => [
            'uid' => $user->uid,
            'userid' => $user->userId,
            'name' => trim($user->name),
            'role' => $user->privilege->value,
            'cardno' => trim((string) $user->cardNumber) ?: null,
        ])->values()->all();

        if (empty($rows)) {
            $this->warn('No users returned by the device.');

            return;
        }

        AttendanceLog::upsert($rows, ['uid'], ['userid', 'name', 'role', 'cardno']);

        $this->info(count($rows).' user(s) synced.');
    }

    protected function syncAttendance(ZktecoService $zkteco): void
    {
        $entries = $zkteco->fetchAttendance();

        $rows = collect($entries)->map(fn (array $entry) => [
            'uid' => $entry['record']->uid,
            'userid' => $entry['user']->userId ?? null,
            'name' => $entry['user']->name ?? null,
            'state' => $entry['record']->punchState->value,
            'state_name' => $entry['record']->punchState->name,
            'verify_mode' => $entry['record']->verifyMode->name,
            'recorded_at' => $entry['record']->recordedAt->format('Y-m-d H:i:s'),
        ])->values()->all();

        if (empty($rows)) {
            $this->warn('No attendance records returned by the device.');

            return;
        }

      Attendance::upsert(
            $rows,
            ['uid', 'recorded_at', 'state', 'verify_mode'],
            ['userid', 'name', 'state_name']
        );

        $this->info(count($rows).' attendance record(s) synced.');
    }
}
