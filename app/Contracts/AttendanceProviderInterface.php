<?php
namespace App\Contracts;

use Carbon\Carbon;

interface AttendanceProviderInterface
{
    /**
     * Fetch attendance records and logs from the source (Mock or ZKTeco Device).
     */
    public function fetchAndSyncLogs(?Carbon $date = null): void;
}