<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationIndex extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'index_name',
        'is_active',
    ];
}