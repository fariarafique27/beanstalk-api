<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Http\Resources\ResponseUser;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Create a new user for organization
     * 
     * @param array $data
     * @return array
     */
    public function createUser(array $data)
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($data['email']);
        
        if ($existingUser) {
            throw new \Exception('User with this email already exists');
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set password_set_at to indicate password was manually set by admin
        $data['password_set_at'] = now();

        // Create user
        $user = $this->userRepository->create($data);

        // Assign role (optional - default is 'employee')
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        } else {
            $user->assignRole('employee');
        }

        // Get permissions for user (optional)
        $permissions = $this->getUserPermissions($user);

        // Return formatted response using existing ResponseUser class
        return (new ResponseUser(null))->prepareUserResponse($user, null, $permissions);
    }

    /**
     * Get permissions for user
     * 
     * @param $user
     * @return array
     */
    private function getUserPermissions($user): array
    {
        try {
            // Get user roles and their permissions
            $roles = $user->getRoleNames();
            $permissions = [];

            foreach ($roles as $role) {
                $rolePermissions = \Spatie\Permission\Models\Role::where('name', $role)
                    ->first()
                    ->permissions()
                    ->pluck('name')
                    ->toArray();
                
                $permissions = array_merge($permissions, $rolePermissions);
            }

            return array_unique($permissions);
        } catch (\Exception $e) {
            logger()->warning('Failed to fetch user permissions', ['error' => $e->getMessage()]);
            return [];
        }
    }
}