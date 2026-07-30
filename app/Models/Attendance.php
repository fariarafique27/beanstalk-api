<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}