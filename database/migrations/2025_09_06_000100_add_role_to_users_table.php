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
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        // Default existing users to Viewer if roles exist
        if (Schema::hasTable('roles')) {
            $viewerId = DB::table('roles')->where('key', 'viewer')->value('id');
            if ($viewerId) {
                DB::table('users')->whereNull('role_id')->update(['role_id' => $viewerId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropConstrainedForeignId('role_id');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable();
            }
        });
    }
};


