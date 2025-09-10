<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For Postgres: align sequences with current MAX(id)
        $tables = [
            'users',
            'roles',
            'branches',
            'departments',
            'contract_types',
            'contractors',
            'contracts',
            'attached_files',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('".$table."','id'), COALESCE((SELECT MAX(id) FROM " . $table . "), 0))"
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};


