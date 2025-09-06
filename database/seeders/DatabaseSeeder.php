<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles
        $roles = [
            ['name' => 'Viewer', 'key' => 'viewer'],
            ['name' => 'Editor', 'key' => 'editor'],
            ['name' => 'Approver', 'key' => 'approver'],
            ['name' => 'Admin', 'key' => 'admin'],
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['key' => $role['key']], $role);
        }

        // Create initial admin
        $adminRoleId = Role::where('key', 'admin')->value('id');
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'Admin@123456',
                'role_id' => $adminRoleId,
            ]
        );
    }
}
