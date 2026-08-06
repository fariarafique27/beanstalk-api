<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get the PermissionRegistrar service from Laravel's service container.
        //Clear the cached roles and permissions so the application uses fresh data.
       app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();


       //Create an array containing the names of the permissions that you want to add to the system. By itself, this array does not insert anything into the database
        $permissions = [
            'manage_organizations',
            'manage_users',
            'manage_roles',
            'view_reports',
            'org-admins.invite', 
            'employees.manage',
            'organization.read',
            // Attendance CRUD Permissions
            'attendances.view',
            'attendances.create',
            'attendances.update',
            'attendances.delete',
            'device.manage',
        ];

       // firstOrCreate → Look for a record. If it doesn't exist, create it.
       //Permission  ---- > database eloquent model
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $orgAdminRole = Role::firstOrCreate(['name' => 'Org Admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

       // Permission::all() ----> Fetch all rows from the permissions table
       //syncPermissions()----> belongs to the Spatie package , Make this role have exactly these permissions. 
        $superAdminRole->syncPermissions(Permission::all());

        // 3. Create Root / Super Admin User
        $rootUser = User::firstOrCreate(
            //This is the search condition.Is there a user whose email is root@beanstalk.com?
            //Case 1: User exists---Laravel finds the user.It does not create a new one.It simply returns the existing user
            //Case 2: User does not exist----Nothing is found.Now Laravel uses the second array to create a new user.
            ['email' => 'root@beanstalk.com'],
            [
                'name' => 'Root System Admin',
                'password' => Hash::make('password123'),
                'is_root' => true,
                'organization_id' => null, // Global access across all orgs
            ]
        );

        //This method comes from the Spatie Permission package.Assign this role to this user
        $rootUser->assignRole($superAdminRole);
    }
}
