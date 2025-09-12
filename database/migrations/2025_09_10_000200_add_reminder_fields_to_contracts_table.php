<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('contracts', function (Blueprint $table): void {
			$table->foreignId('initiator_id')->nullable()->after('responsible_manager_id')->constrained('users')->cascadeOnUpdate()->nullOnDelete();
			$table->string('notify_group_email', 255)->nullable()->after('initiator_id');

			$table->timestamp('reminder_60_sent_at')->nullable()->after('updated_at');
			$table->unsignedTinyInteger('reminder_60_attempts')->default(0)->after('reminder_60_sent_at');
			$table->timestamp('reminder_60_last_attempt_at')->nullable()->after('reminder_60_attempts');

			$table->timestamp('reminder_30_sent_at')->nullable()->after('reminder_60_last_attempt_at');
			$table->unsignedTinyInteger('reminder_30_attempts')->default(0)->after('reminder_30_sent_at');
			$table->timestamp('reminder_30_last_attempt_at')->nullable()->after('reminder_30_attempts');
		});
	}

	public function down(): void
	{
		Schema::table('contracts', function (Blueprint $table): void {
			$table->dropConstrainedForeignId('initiator_id');
			$table->dropColumn('notify_group_email');
			$table->dropColumn(['reminder_60_sent_at','reminder_60_attempts','reminder_60_last_attempt_at','reminder_30_sent_at','reminder_30_attempts','reminder_30_last_attempt_at']);
		});
	}
};
