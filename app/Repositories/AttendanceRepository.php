<?php
namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\ZktecoUser;
use Illuminate\Support\Facades\Auth;

class AttendanceRepository
{
    public function fetchTodayAttendances(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $today = now()->toDateString();

        // Pull today's attendance sheets with their logs eager-loaded,
        // scoped to the current admin's org, keyed by employee_id for
        // fast lookup.
        $todayAttendances = Attendance::with('logs')
            ->where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy('employee_id');

        // All employees IN THIS ORG (so absent employees show up too,
        // not just ones who've ever had an attendance row) — was
        // previously pulling every org's employees.
        $employees = ZktecoUser::where('organization_id', $orgId)
            ->select('id', 'name')
            ->get();

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

    public function fetchEmployeeHistory(Request $request, $id)
    {
        $orgId = Auth::user()->organization_id;

        // organization_id scope added here is the key fix -- without it,
        // any admin could view any employee's history across orgs just
        // by knowing/guessing their numeric id.
        $query = Attendance::with('logs')
            ->where('organization_id', $orgId)
            ->where('employee_id', $id);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('attendance_date', 'like', "%{$search}%")
                ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('range')) {
            $now = now();

            match ($request->input('range')) {
                'today' => $query->whereDate('attendance_date', $now->toDateString()),
                'week' => $query->whereBetween('attendance_date', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]),
                'month' => $query->whereBetween('attendance_date', [$now->copy()->subMonth()->toDateString(), $now->toDateString()]),
                'year' => $query->whereBetween('attendance_date', [$now->copy()->subYear()->toDateString(), $now->toDateString()]),
                default => null,
            };
        }

        $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

        $paginated->getCollection()->transform(function ($attendance) {
            // Raw Carbon instances, sorted chronologically -- used for BOTH
            // display formatting and duration math below.
            $checkInLogs = $attendance->logs
                ->whereNotNull('check_in_time')
                ->sortBy('check_in_time')
                ->values();

            $checkOutLogs = $attendance->logs
                ->whereNotNull('check_out_time')
                ->sortBy('check_out_time')
                ->values();

            $checkIns = $checkInLogs->map(fn ($log) => $log->check_in_time->format('h:i A'));
            $checkOuts = $checkOutLogs->map(fn ($log) => $log->check_out_time->format('h:i A'));

            // Pair check-in[i] with check-out[i] positionally. Only count
            // pairs where BOTH sides exist -- an unmatched trailing check-in
            // (no corresponding check-out yet) is skipped entirely, not
            // counted as zero and not guessed at.
            $pairCount = min($checkInLogs->count(), $checkOutLogs->count());
            $totalMinutes = 0;

            for ($i = 0; $i < $pairCount; $i++) {
                $in = $checkInLogs[$i]->check_in_time;
                $out = $checkOutLogs[$i]->check_out_time;

                // Guard against a malformed pair where out < in (bad device
                // data) -- never let it subtract from the day's total.
                $minutes = $out->greaterThan($in) ? $in->diffInMinutes($out) : 0;
                $totalMinutes += $minutes;
            }

            $totalHoursFormatted = $totalMinutes > 0
                ? sprintf('%dh %dm', intdiv($totalMinutes, 60), $totalMinutes % 60)
                : '—';

            return [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'user_name' => $attendance->user_name,
                'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                'check_ins' => $checkIns->all(),
                'check_outs' => $checkOuts->all(),
                'total_punches' => $attendance->logs->count(),
                'total_hours' => $totalHoursFormatted,   // e.g. "8h 12m" or "—"
                'total_minutes_worked' => $totalMinutes, // raw value, for sorting/reporting later
                'status' => $attendance->status,
                'remarks' => $attendance->remarks,
            ];
        });

        return $paginated;
    }
}


// class AttendanceRepository
// {
//     public function fetchTodayAttendances(Request $request)
//     {
//         $today = now()->toDateString();

//         // Pull today's attendance sheets with their logs eager-loaded,
//         // keyed by employee_id for fast lookup.
//         $todayAttendances = Attendance::with('logs')
//             ->whereDate('attendance_date', $today)
//             ->get()
//             ->keyBy('employee_id');

//         // All employees in the system (so absent employees show up too,
//         // not just ones who've ever had an attendance row).
//         $employees = ZktecoUser::select('id', 'name')->get();

//         return $employees->map(function ($employee) use ($todayAttendances) {
//             $attendance = $todayAttendances->get($employee->id);

//             if (!$attendance) {
//                 return [
//                     'id' => $employee->id,
//                     'employee_id' => $employee->id,
//                     'user_name' => $employee->name,
//                     'check_in' => null,
//                     'check_out' => null,
//                     'status' => 'Absent',
//                 ];
//             }

//             // First check-in of the day = earliest log row with check_in_time set
//             $firstCheckIn = $attendance->logs
//                 ->whereNotNull('check_in_time')
//                 ->sortBy('check_in_time')
//                 ->first();

//             // Last check-out of the day = latest log row with check_out_time set
//             $lastCheckOut = $attendance->logs
//                 ->whereNotNull('check_out_time')
//                 ->sortByDesc('check_out_time')
//                 ->first();

//             return [
//                 'id' => $attendance->id,
//                 'employee_id' => $employee->id,
//                 'user_name' => $employee->name,
//                 'check_in' => $firstCheckIn?->check_in_time?->format('H:i:s'),
//                 'check_out' => $lastCheckOut?->check_out_time?->format('H:i:s'),
//                 'status' => $attendance->status,
//             ];
//         });
//     }


//     public function fetchEmployeeHistory(Request $request, $id)
//     {
//         $query = Attendance::with('logs')
//             ->where('employee_id', $id);

//         if ($request->filled('search')) {
//             $search = $request->input('search');
//             $query->where(function ($q) use ($search) {
//                 $q->where('attendance_date', 'like', "%{$search}%")
//                 ->orWhere('user_name', 'like', "%{$search}%");
//             });
//         }

//         if ($request->filled('range')) {
//             $now = now();

//             match ($request->input('range')) {
//                 'today' => $query->whereDate('attendance_date', $now->toDateString()),
//                 'week' => $query->whereBetween('attendance_date', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]),
//                 'month' => $query->whereBetween('attendance_date', [$now->copy()->subMonth()->toDateString(), $now->toDateString()]),
//                 'year' => $query->whereBetween('attendance_date', [$now->copy()->subYear()->toDateString(), $now->toDateString()]),
//                 default => null,
//             };
//         }

//         $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

//         $paginated->getCollection()->transform(function ($attendance) {
//             // Raw Carbon instances, sorted chronologically -- used for BOTH
//             // display formatting and duration math below.
//             $checkInLogs = $attendance->logs
//                 ->whereNotNull('check_in_time')
//                 ->sortBy('check_in_time')
//                 ->values();

//             $checkOutLogs = $attendance->logs
//                 ->whereNotNull('check_out_time')
//                 ->sortBy('check_out_time')
//                 ->values();

//             $checkIns = $checkInLogs->map(fn ($log) => $log->check_in_time->format('h:i A'));
//             $checkOuts = $checkOutLogs->map(fn ($log) => $log->check_out_time->format('h:i A'));

//             // Pair check-in[i] with check-out[i] positionally. Only count
//             // pairs where BOTH sides exist -- an unmatched trailing check-in
//             // (no corresponding check-out yet) is skipped entirely, not
//             // counted as zero and not guessed at.
//             $pairCount = min($checkInLogs->count(), $checkOutLogs->count());
//             $totalMinutes = 0;

//             for ($i = 0; $i < $pairCount; $i++) {
//                 $in = $checkInLogs[$i]->check_in_time;
//                 $out = $checkOutLogs[$i]->check_out_time;

//                 // Guard against a malformed pair where out < in (bad device
//                 // data) -- never let it subtract from the day's total.
//                 $minutes = $out->greaterThan($in) ? $in->diffInMinutes($out) : 0;
//                 $totalMinutes += $minutes;
//             }

//             $totalHoursFormatted = $totalMinutes > 0
//                 ? sprintf('%dh %dm', intdiv($totalMinutes, 60), $totalMinutes % 60)
//                 : '—';

//             return [
//                 'id' => $attendance->id,
//                 'employee_id' => $attendance->employee_id,
//                 'user_name' => $attendance->user_name,
//                 'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
//                 'check_ins' => $checkIns->all(),
//                 'check_outs' => $checkOuts->all(),
//                 'total_punches' => $attendance->logs->count(),
//                 'total_hours' => $totalHoursFormatted,   // e.g. "8h 12m" or "—"
//                 'total_minutes_worked' => $totalMinutes, // raw value, for sorting/reporting later
//                 'status' => $attendance->status,
//                 'remarks' => $attendance->remarks,
//             ];
//         });

//         return $paginated;
//     }
    
// }