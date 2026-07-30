<?php
namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceRepository
{
    public function fetchTodayAttendances(Request $request)
    {
        $today = now()->toDateString(); // '2026-07-29'

        // Get all unique employees/users who have attendance records in the system
        $uniqueEmployees = Attendance::select('employee_id', 'user_name')
            ->distinct()
            ->get();

        return $uniqueEmployees->map(function ($employee) use ($today) {
            // Find today's specific record for this employee
            $todayAttendance = Attendance::where('employee_id', $employee->employee_id)
                ->whereDate('attendance_date', $today)
                ->first();

            // Fetch check-in/out if logs or attendance columns exist
            // (Assumes check_in can be pulled from related attendance_logs or attendance columns)
            $checkIn = '00:00:00';
            $checkOut = '00:00:00';
            $status = 'Absent';

            if ($todayAttendance) {
                $status = 'Present';
                // If you store check_in/out directly on attendance or via relation, map it here:
                $checkIn = $todayAttendance->check_in ?? '09:00:00'; // Adjust column to your schema if needed
                $checkOut = $todayAttendance->check_out ?? '00:00:00';
            }

            return [
                'id' => $todayAttendance->id ?? $employee->employee_id, // Used for the show route
                'employee_id' => $employee->employee_id,
                'user_name' => $employee->user_name ?? 'Unknown User',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
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
        // Make sure to eager load 'attendanceLogs' (plural)
        $query = Attendance::with('attendanceLogs')
            ->where('employee_id', $id);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                ->orWhere('attendance_date', 'like', "%{$search}%")
                ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->orderBy('attendance_date', 'desc')->paginate(10);
    }
}