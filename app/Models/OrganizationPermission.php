<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'module_key',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}