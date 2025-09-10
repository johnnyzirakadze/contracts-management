<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
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

        // Create or update initial admin (password: admin123)
        $adminRoleId = Role::where('key', 'admin')->value('id');
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'admin123',
                'role_id' => $adminRoleId,
            ]
        );

        // Domain seeds (order matters for FKs)
        $this->call([
            ContractTypeSeeder::class,
            BranchSeeder::class,
            DepartmentSeeder::class,
            ContractorSeeder::class,
            ContractSeeder::class,
            AttachedFileSeeder::class,
        ]);

        // Link contracts' responsible_manager_id to existing admin user
        $adminId = User::where('email', 'admin@example.com')->value('id');
        if ($adminId) {
            DB::table('contracts')->whereIn('id', [1, 2])->update([
                'responsible_manager_id' => $adminId,
                'updated_at' => now(),
            ]);
        }
    }
}
