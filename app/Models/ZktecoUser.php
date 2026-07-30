<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZktecoUser extends Model
{
    protected $table = 'zkteco_users';
    protected $guarded = [];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }
}