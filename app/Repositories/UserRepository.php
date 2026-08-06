<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Organization;

class UserRepository extends BaseRepository
{
    /**
     * Find user by email
     * 
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create new user
     * 
     * @param array $data
     * @return User
     */
    public function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'organization_id' => $data['organization_id'],
            'phone' => $data['phone'] ?? null,
            'password_set_at' => $data['password_set_at'] ?? null,
        ]);
    }

    /**
     * Find user by ID
     * 
     * @param int $id
     * @return User|null
     */
    public function findById(int $id)
    {
        return User::find($id);
    }

    /**
     * Get all users for organization
     * 
     * @param int $organizationId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByOrganization(int $organizationId)
    {
        return User::where('organization_id', $organizationId)->get();
    }
}