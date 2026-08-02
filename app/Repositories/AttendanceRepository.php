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
    //     logger('[REPO] fetchEmployeeHistory called', ['employee_id' => $id, 'params' => $request->all()]);

    //     $query = Attendance::with('logs')
    //         ->where('employee_id', $id);

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->where(function ($q) use ($search) {
    //             $q->where('status', 'like', "%{$search}%")
    //             ->orWhere('attendance_date', 'like', "%{$search}%")
    //             ->orWhere('user_name', 'like', "%{$search}%");
    //         });
    //     }

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->input('status'));
    //     }

    //     $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

    //     logger('[REPO] raw paginated count', ['total' => $paginated->total()]);

    //     if ($paginated->count() > 0) {
    //         $first = $paginated->first();
    //         logger('[REPO] first raw attendance row', [
    //             'attendance_id' => $first->id,
    //             'employee_id' => $first->employee_id,
    //             'logs_count' => $first->logs->count(),
    //             'logs_raw' => $first->logs->toArray(),
    //         ]);
    //     }

    //     $paginated->getCollection()->transform(function ($attendance) {
    //         $firstCheckIn = $attendance->logs->whereNotNull('check_in_time')->sortBy('check_in_time')->first();
    //         $lastCheckOut = $attendance->logs->whereNotNull('check_out_time')->sortByDesc('check_out_time')->first();

    //         $row = [
    //             'id' => $attendance->id,
    //             'employee_id' => $attendance->employee_id,
    //             'user_name' => $attendance->user_name,
    //             'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
    //             'check_in' => $firstCheckIn?->check_in_time?->format('h:i A'),
    //             'check_out' => $lastCheckOut?->check_out_time?->format('h:i A'),
    //             'status' => $attendance->status,
    //             'remarks' => $attendance->remarks,
    //             'total_minutes' => $attendance->total_minutes,
    //         ];

    //         logger('[REPO] transformed row', $row);

    //         return $row;
    //     });

    //     logger('[REPO] final paginated first item after transform', $paginated->items()[0] ?? []);

    //     return $paginated;
    // }

    // public function fetchEmployeeHistory(Request $request, $id)
    // {
    //     $query = Attendance::with('logs')
    //         ->where('employee_id', $id);

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->where(function ($q) use ($search) {
    //             $q->where('status', 'like', "%{$search}%")
    //             ->orWhere('attendance_date', 'like', "%{$search}%")
    //             ->orWhere('user_name', 'like', "%{$search}%");
    //         });
    //     }

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->input('status'));
    //     }

    //     $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

    //     $paginated->getCollection()->transform(function ($attendance) {
    //         // All check-in punches for the day, in the order they happened
    //         $checkIns = $attendance->logs
    //             ->whereNotNull('check_in_time')
    //             ->sortBy('check_in_time')
    //             ->values()
    //             ->map(fn ($log) => $log->check_in_time->format('h:i A'));

    //         // All check-out punches for the day, in the order they happened
    //         $checkOuts = $attendance->logs
    //             ->whereNotNull('check_out_time')
    //             ->sortBy('check_out_time')
    //             ->values()
    //             ->map(fn ($log) => $log->check_out_time->format('h:i A'));

    //         return [
    //             'id' => $attendance->id,
    //             'employee_id' => $attendance->employee_id,
    //             'user_name' => $attendance->user_name,
    //             'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
    //             'check_ins' => $checkIns->all(),   // e.g. ["08:00 AM", "01:15 PM", "05:40 PM"]
    //             'check_outs' => $checkOuts->all(), // e.g. ["12:00 PM", "04:30 PM"]
    //             'total_punches' => $attendance->logs->count(),
    //             'status' => $attendance->status,
    //             'remarks' => $attendance->remarks,
    //             'total_minutes' => $attendance->total_minutes,
    //         ];
    //     });

    //     return $paginated;
    // }

    // public function fetchEmployeeHistory(Request $request, $id)
    // {
    //     $query = Attendance::with('logs')
    //         ->where('employee_id', $id);

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->where(function ($q) use ($search) {
    //             $q->where('attendance_date', 'like', "%{$search}%")
    //             ->orWhere('user_name', 'like', "%{$search}%");
    //         });
    //     }

    //     // Date-range filter: today, week, month, year
    //     if ($request->filled('range')) {
    //         $now = now();

    //         match ($request->input('range')) {
    //             'today' => $query->whereDate('attendance_date', $now->toDateString()),
    //             'week' => $query->whereBetween('attendance_date', [$now->copy()->subDays(7)->toDateString(), $now->toDateString()]),
    //             'month' => $query->whereBetween('attendance_date', [$now->copy()->subMonth()->toDateString(), $now->toDateString()]),
    //             'year' => $query->whereBetween('attendance_date', [$now->copy()->subYear()->toDateString(), $now->toDateString()]),
    //             default => null,
    //         };
    //     }

    //     $paginated = $query->orderBy('attendance_date', 'desc')->paginate(10);

    //     $paginated->getCollection()->transform(function ($attendance) {
    //         $checkIns = $attendance->logs
    //             ->whereNotNull('check_in_time')
    //             ->sortBy('check_in_time')
    //             ->values()
    //             ->map(fn ($log) => $log->check_in_time->format('h:i A'));

    //         $checkOuts = $attendance->logs
    //             ->whereNotNull('check_out_time')
    //             ->sortBy('check_out_time')
    //             ->values()
    //             ->map(fn ($log) => $log->check_out_time->format('h:i A'));

    //         return [
    //             'id' => $attendance->id,
    //             'employee_id' => $attendance->employee_id,
    //             'user_name' => $attendance->user_name,
    //             'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
    //             'check_ins' => $checkIns->all(),
    //             'check_outs' => $checkOuts->all(),
    //             'total_punches' => $attendance->logs->count(),
    //             'status' => $attendance->status,
    //             'remarks' => $attendance->remarks,
    //             'total_minutes' => $attendance->total_minutes,
    //         ];
    //     });

    //     return $paginated;
    // }

    public function fetchEmployeeHistory(Request $request, $id)
    {
        $query = Attendance::with('logs')
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