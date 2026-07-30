<?php
namespace App\Services\Attendance;

use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;
use App\Services\BaseService;

class AttendanceService extends BaseService 
{
    protected AttendanceRepository $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    public function getTodayAttendances(Request $request)
    {
        $attendances = $this->attendanceRepository->fetchTodayAttendances($request);

        $totalPresent = 0;
        $totalAbsent = 0;

        foreach ($attendances as $item) {
            if (strtolower($item['status']) === 'present') {
                $totalPresent++;
            } else {
                $totalAbsent++;
            }
        }

        return response()->json([
            'success' => true,
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'data' => $attendances
        ], 200);
    }

    public function getEmployeeHistory(Request $request, $id)
    {
        $paginatedLogs = $this->attendanceRepository->fetchEmployeeHistory($request, $id);

        return response()->json([
            'success' => true,
            'data' => $paginatedLogs
        ], 200);
    }
}