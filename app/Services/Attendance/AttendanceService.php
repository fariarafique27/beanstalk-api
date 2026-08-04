<?php
namespace App\Services\Attendance;

use App\Repositories\AttendanceRepository;
use App\Http\Responses\AttendanceResponse;
use Illuminate\Http\Request;
use App\Services\BaseService;

class AttendanceService extends BaseService
{
    public function __construct(
        protected AttendanceRepository $attendanceRepository,
        protected AttendanceResponse $attendanceResponse,
    ) {}

    public function getTodayAttendances(Request $request)
    {
        $attendances = $this->attendanceRepository->fetchTodayAttendances($request);

        return $this->attendanceResponse->today($attendances);
    }

    public function getEmployeeHistory(Request $request, $id)
    {
        $paginatedLogs = $this->attendanceRepository->fetchEmployeeHistory($request, $id);

        return $this->attendanceResponse->history($paginatedLogs);
    }
}