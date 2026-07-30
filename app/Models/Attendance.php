<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'organization_id',
        'attendance_date',
        'total_minutes',
        'status',
        'remarks',
        'user_name',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class)
            ->orderByRaw('COALESCE(check_in_time, check_out_time) ASC');
    }

    public function employee()
    {
        return $this->belongsTo(ZktecoUser::class, 'employee_id');
    }
}