<?php
namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\ZktecoUser;

class AttendanceRepository
{
    public function fetchTodayAttendances(Request $request)
{
    $today = now()->toDateString();

    // Pull today's attendance sheets with their logs eager-loaded,
    // keyed by employee_id for fast lookup.
    $todayAttendances = Attendance::with('logs')
        ->whereDate('attendance_date', $today)
        ->get()
        ->keyBy('employee_id');

    // All employees in the system (so absent employees show up too,
    // not just ones who've ever had an attendance row).
    $employees = ZktecoUser::select('id', 'name')->get();

    return $employees->map(function ($employee) use ($todayAttendances) {
        $attendance = $todayAttendances->get($employee->id);

        if (!$attendance) {
            return [
                'id' => $employee->id,
                'employee_id' => $employee->id,
                'user_name' => $employee->name,
                'check_in' => null,
                'check_out' => null,
                'status' => 'Absent',
            ];
        }

        // First check-in of the day = earliest log row with check_in_time set
        $firstCheckIn = $attendance->logs
            ->whereNotNull('check_in_time')
            ->sortBy('check_in_time')
            ->first();

        // Last check-out of the day = latest log row with check_out_time set
        $lastCheckOut = $attendance->logs
            ->whereNotNull('check_out_time')
            ->sortByDesc('check_out_time')
            ->first();

        return [
            'id' => $attendance->id,
            'employee_id' => $employee->id,
            'user_name' => $employee->name,
            'check_in' => $firstCheckIn?->check_in_time?->format('H:i:s'),
            'check_out' => $lastCheckOut?->check_out_time?->format('H:i:s'),
            'status' => $attendance->status,
        ];
    });
}

    // public function fetchEmployeeHistory(Request $request, $id)
    // {
    //     // Make sure this matches whether your route parameter is an employee_id or user id/primary key
    //     $query = Attendance::where('employee_id', $id); // or ->where('user_id', $id)

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->where(function($q) use ($search) {
    //             $q->where('check_in', 'like', "%{$search}%")
    //             ->orWhere('check_out', 'like', "%{$search}%")
    //             ->orWhere('status', 'like', "%{$search}%")
    //             ->orWhere('attendance_date', 'like', "%{$search}%");
    //         });
    //     }

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->input('status'));
    //     }

    //     return $query->orderBy('attendance_date', 'desc')->paginate(10);
    // }

    public function fetchEmployeeHistory(Request $request, $id)
{
    logger('[REPO] fetchEmployeeHistory called', ['employee_id' => $id, 'params' => $request->all()]);

    $query = Attendance::with('logs')
        ->where('employee_id', $id);

    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('status', 'like', "%{$search}%")
              ->orWhere('attendance_date', 'like', "%{$search}%")
              ->orWhere('user_name', 'like', "%{$search}%");
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

    logger('[REPO] raw paginated count', ['total' => $paginated->total()]);

    if ($paginated->count() > 0) {
        $first = $paginated->first();
        logger('[REPO] first raw attendance row', [
            'attendance_id' => $first->id,
            'employee_id' => $first->employee_id,
            'logs_count' => $first->logs->count(),
            'logs_raw' => $first->logs->toArray(),
        ]);
    }

    $paginated->getCollection()->transform(function ($attendance) {
        $firstCheckIn = $attendance->logs->whereNotNull('check_in_time')->sortBy('check_in_time')->first();
        $lastCheckOut = $attendance->logs->whereNotNull('check_out_time')->sortByDesc('check_out_time')->first();

        $row = [
            'id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'user_name' => $attendance->user_name,
            'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
            'check_in' => $firstCheckIn?->check_in_time?->format('h:i A'),
            'check_out' => $lastCheckOut?->check_out_time?->format('h:i A'),
            'status' => $attendance->status,
            'remarks' => $attendance->remarks,
            'total_minutes' => $attendance->total_minutes,
        ];

        logger('[REPO] transformed row', $row);

        return $row;
    });

    logger('[REPO] final paginated first item after transform', $paginated->items()[0] ?? []);

    return $paginated;
}
}