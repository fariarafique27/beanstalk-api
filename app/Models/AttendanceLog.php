<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $guarded = ['id'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}