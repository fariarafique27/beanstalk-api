<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    //A Trait is a reusable plug-in of code that gives a PHP class extra features without inheriting from a new parent class.
    //below are all traits ::
    //HasFactory:: Lets you generate fake test users instantly using factories
    //Notifiable:: Allows you to send emails, SMS, or database notifications directly to the user
    //HasRoles (Spatie):: Unlocks all Spatie permission methods on the user.
    //SoftDeletes:: Prevents users from being permanently deleted when calling $user->delete().
    use HasFactory, Notifiable, HasRoles, SoftDeletes , HasApiTokens ;

    // $fillable tells Laravel: "Only allow these specific columns to be saved.
    protected $fillable = [
            'organization_id',
            'is_root',
            'name',
            'email',
            'password',
            'phone',
            'image',
        ];

        //$hidden tells Laravel: "Never show these columns when converting the model to JSON or an API response."
    protected $hidden = [
        'password',
        'remember_token',
    ];


    //casts() tells Laravel: "Automatically convert these columns to specific PHP data types whenever they are read or saved."
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_root' => 'boolean',
        ];
    }

    //Every user belongs to one organization (tenant).
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

}
