<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDevice extends Model
{
    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);           //An organization can have many devices, but a single device belongs to one organization. (One-to-Many / Many-to-One).
    }
}