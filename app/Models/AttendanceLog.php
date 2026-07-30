<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'attendance_id',
        'zkteco_user_id',
        'check_in_time',
        'check_out_time',
        'check_in_punch_state',
        'check_out_punch_state',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_address',
        'check_out_address',
        'check_in_ip',
        'check_out_ip',
        'duration',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function zktecoUser()
    {
        return $this->belongsTo(ZktecoUser::class, 'zkteco_user_id');
    }
}