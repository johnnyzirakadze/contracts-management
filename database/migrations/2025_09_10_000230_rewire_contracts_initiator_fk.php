<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('contracts', function (Blueprint $table): void {
			$table->dropConstrainedForeignId('initiator_id');
			$table->foreignId('initiator_id')->nullable()->after('responsible_manager_id')->constrained('initiators')->cascadeOnUpdate()->nullOnDelete();
		});
	}

	public function down(): void
	{
		Schema::table('contracts', function (Blueprint $table): void {
			$table->dropConstrainedForeignId('initiator_id');
			$table->foreignId('initiator_id')->nullable()->after('responsible_manager_id')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
		});
	}
};
