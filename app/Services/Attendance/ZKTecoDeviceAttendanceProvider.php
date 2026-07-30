<?php

namespace App\Services\Attendance;

use App\Contracts\AttendanceProviderInterface;
use App\Services\ZktecoService;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ZKTecoDeviceAttendanceProvider implements AttendanceProviderInterface
{
    protected ZktecoService $zkService;

    public function __construct(ZktecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    public function fetchAndSyncLogs(?Carbon $date = null): void
    {
        $targetDate = $date ?? today();

        try {
            $rawAttendance = $this->zkService->fetchAttendance();
            Log::info("ZKTeco Device synced successfully. Total logs fetched: " . count($rawAttendance));

            foreach ($rawAttendance as $item) {
                $record = $item['record'];
                $user = $item['user'];

                // Example mapping logic to your database schema:
                // Find employee by device UID or user id/badge number, 
                // then insert/update into attendances and attendance_logs tables.
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync attendance from ZKTeco device: " . $e->getMessage());
        }
    }
}