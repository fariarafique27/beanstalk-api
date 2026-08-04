<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Models\OrganizationPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganizationRepository extends BaseRepository
{

    public function getAllOrganizations()
    {
        return Organization::with(['permissions', 'users'])->get()->map(function ($org) {
            // Transform the permissions relation into a collection/array of name strings
            $org->permissions = $org->permissions->pluck('name')->toArray();
            return $org;
        });
    }


    public function findById($id)
    {
        return Organization::with('permissions')->find($id);
    }

    public function createOrganization(array $data)
    {
        return Organization::create([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'status' => 1,
        ]);
    }

    public function createOrgAdminUser(Organization $organization, array $data)
    {
        $user = User::create([
            'name'            => $data['name'] . ' Admin',
            'email'           => $data['email'],
            'password'        => $data['password'] ?? '123456789', // Set via invitation link if not provided
            'organization_id' => $organization->id,
        ]);

        // Assign Spatie Role cleanly
        $user->assignRole('org_admin');

        return $user;
    }

    public function syncPermissions($organizationId, array $permissions)
    {
        OrganizationPermission::where('organization_id', $organizationId)->delete();

        foreach ($permissions as $moduleKey) {
            OrganizationPermission::create([
                'organization_id' => $organizationId,
                'module_key'      => $moduleKey,
            ]);
        }
    }

    public function createInvitationToken(string $email, string $token)
    {
        DB::table('organization_invitations')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => $token,
                'created_at' => now(),
            ]
        );
    }

    public function findInvitationToken(string $email, string $token)
    {
        return DB::table('organization_invitations')
            ->where('email', $email)
            ->where('token', $token)
            ->first();
    }

    public function deleteInvitationToken(string $email)
    {
        DB::table('organization_invitations')->where('email', $email)->delete();
    }

    public function updatePasswordAndActivate(User $user, string $password)
    {
        $user->password = $password; // Auto-hashed via model casts
        $user->password_set_at = now();
        $user->save();

        return $user;
    }
}