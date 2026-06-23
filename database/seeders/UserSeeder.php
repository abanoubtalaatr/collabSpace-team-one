<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
        ], [
            'display_name' => 'Administrator',
        ]);

        $projectManagerRole = Role::firstOrCreate([
            'name' => 'project_manager',
        ], [
            'display_name' => 'Project Manager',
        ]);

        $memberRole = Role::firstOrCreate([
            'name' => 'member',
        ], [
            'display_name' => 'Member',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        $projectManager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Project Manager',
                'password' => Hash::make('password'),
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Team Member',
                'password' => Hash::make('password'),
            ]
        );

        // Laratrust
        $admin->addRole($adminRole);
        $projectManager->addRole($projectManagerRole);
        $member->addRole($memberRole);
    }
}