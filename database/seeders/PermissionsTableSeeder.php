<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        Permission::query()->delete();

        $userIds = User::admin(true)->pluck('id')->toArray();

        $permissions = [
            //User Management Module
            [
                'name'        => 'User Management Access',
                'description' => 'Access to User Management module',
            ],
            //Users Module
            [
                'name'        => 'Users Access',
                'description' => 'Access to Users module',
            ],
            [
                'name'        => 'Users Export',
                'description' => 'Export selected users',
            ],
            [
                'name'        => 'User Create',
                'description' => 'Create new User',
            ],
            [
                'name'        => 'User Show',
                'description' => 'Show selected User',
            ],
            [
                'name'        => 'User Edit',
                'description' => 'Edit User',
            ],
            [
                'name'        => 'User Delete',
                'description' => 'Delete User or selected Users',
            ],
            //Permissions
            [
                'name'        => 'Permissions Access',
                'description' => 'Access to Permissions',
            ],
            [
                'name'        => 'Permissions Export',
                'description' => 'Export selected permissions',
            ],
            [
                'name'        => 'Permission Create',
                'description' => 'Create new Permission',
            ],
            [
                'name'        => 'Permission Assign',
                'description' => 'Assign Permission to the User',
            ],
            [
                'name'        => 'Permission Remove',
                'description' => 'Remove Permission from the User',
            ],
            [
                'name'        => 'Permission Show',
                'description' => 'Show selected Permission',
            ],
            [
                'name'        => 'Permission Edit',
                'description' => 'Edit Permission',
            ],
            [
                'name'        => 'Permission Delete',
                'description' => 'Delete Permission or selected Permissions',
            ],
            //Data Import Module
            [
                'name'        => 'Data Import Access',
                'description' => 'Access to Data Import',
            ],
            //Imported Data Module
            [
                'name'        => 'Imported Data Access',
                'description' => 'Access to Imported Data',
            ],
            [
                'name'        => 'Import Orders Access',
                'description' => 'Access to Import Orders',
            ],
            [
                'name'        => 'Imported Data Show',
                'description' => 'View details of changes: import, row, column, and old/new values',
            ],
            [
                'name'        => 'Imported Data Delete',
                'description' => 'Delete a specific row from imported data',
            ],
            //Imports Module
            [
                'name'        => 'Imports Access',
                'description' => 'Access to Imports',
            ],
        ];
   
        foreach ($permissions as $data) 
        {
            $permission = Permission::create($data);

            $permission->users()->attach($userIds);
        }
    }
}
