<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Organization extends Model
{
    use HasFactory, SoftDeletes ;

    public function User(): HasMany
    {
        return $this->HasMany(Organization::class);
    }

    public function permissions()
    {
        return $this->hasMany(OrganizationPermission::class, 'organization_id');
    }
}
