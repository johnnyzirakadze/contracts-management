<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->constrained('roles');
            }
        });

        if (Schema::hasColumn('users', 'role')) {
            // Map existing string roles to role_id
            $roles = DB::table('roles')->pluck('id', 'key');
            $map = [
                'viewer' => $roles['viewer'] ?? null,
                'editor' => $roles['editor'] ?? null,
                'approver' => $roles['approver'] ?? null,
                'admin' => $roles['admin'] ?? null,
            ];

            foreach ($map as $key => $id) {
                if ($id) {
                    DB::table('users')->where('role', $key)->update(['role_id' => $id]);
                }
            }

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
        });
    }
};


