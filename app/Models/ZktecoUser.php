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

        // ZktecoUser.php
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'zkteco_user_id');
    }
}
