<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\BaseRepository;
use App\Models\OrganizationPermission;

class AuthRepository extends BaseRepository
{
    public function registerUser($data)
    {
        $user = User::create($data);
        $user->assignRole('user');

        $organization = $user->organization()->create();
        $organization->organizationSetting()->create([
            'emp_no_start' => '000001',
            'emp_no_next' => '000001',
            'separator' => '/',
            'use_dept_code' => 0,
            'use_type_code' => 0,
            'use_loc_code' => 0,
            'prefix_order' => json_encode(['use_type', 'use_deptartment', 'use_location']),
        ]);

        return $user;
    }

    //use
    public function fetchUser($request)                             
    {
        return User::where('email', $request->email)->with(['organization' => function ($query) {                //// Find user by request email and include their organization (even if deleted)    
            $query->withTrashed();
        }])->first();
    }

    //use 
    public function getUserPermissions($user): array
    {
        $orgPermissions = [];

        if ($user->organization_id) {
            $orgPermissions = OrganizationPermission::where('organization_id', $user->organization_id)
                ->pluck('module_key')
                ->toArray();
        }

        $spatiePermissions = $user->getAllPermissions()->pluck('name')->toArray();

        return array_values(array_unique(array_merge($spatiePermissions, $orgPermissions)));
    }


    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function attempt($credentials)
    {
        return Auth::attempt($credentials);
    }

    public function checkUser($credentials)
    {
        return User::where('email', $credentials['email'])->first();
    }

    public function findUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    public function findUserById($id)
    {
        return User::with(['organization', 'roles', 'permissions'])->find($id);
    }

    public function updateUserPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->save();
    }

    public function findTokenByEmail($email)
    {
        return DB::table('password_reset_tokens')->where('email', $email)->first();
    }
}