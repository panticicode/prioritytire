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

        $userIds = User::whereNull('parent_id')->pluck('id')->toArray();

        $permissions = [
            //Profile
            // [
            //     'name'        => 'Auth Profile Edit',
            //     'description' => 'Access to Logged in Profile. Allow / Disallow User to change their profile.',
            // ],
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
            //Imports Module
            [
                'name'        => 'Imports Access',
                'description' => 'Access to Imports',
            ],
        ];

        $timestamp = ['created_at' => now(), 'updated_at' => now()];

        foreach ($permissions as $data) 
        {
            $permission = Permission::create($data);

            $permission->users()->attach($userIds, $timestamp);
        }
    }
}
