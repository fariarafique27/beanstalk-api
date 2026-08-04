<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationDevice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        // Encrypted automatically on save and decrypted automatically on
        // read — the DB column only ever holds ciphertext, never plain text.
        'password' => 'encrypted',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);           //An organization can have many devices, but a single device belongs to one organization. (One-to-Many / Many-to-One).
    }
}