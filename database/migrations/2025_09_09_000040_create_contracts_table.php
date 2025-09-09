<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('contracts', function (Blueprint $table): void {
			$table->id();
			$table->string('party_name', 200);
			$table->string('party_identifier', 11);
			$table->foreignId('contractor_id')->nullable()->constrained('contractors')->cascadeOnUpdate()->nullOnDelete();
			$table->string('contract_number', 50)->nullable()->unique();
			$table->foreignId('contract_type_id')->constrained('contract_types')->cascadeOnUpdate()->restrictOnDelete();
			$table->text('subject');
			$table->date('sign_date');
			$table->date('expiry_date')->nullable();
			$table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
			$table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnUpdate()->nullOnDelete();
			$table->char('currency', 3);
			$table->decimal('amount', 18, 2)->nullable();
			$table->enum('status', ['აქტიური', 'დასამტკიცებელი', 'შეჩერებული', 'დახურული', 'დასრულებული'])->default('დასამტკიცებელი');
			$table->foreignId('responsible_manager_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
			$table->enum('payment_type', ['ყოველთვიური', 'ერთჯერადი', 'კვარტალური', 'დანაწევრებული']);
			$table->timestamps();
		});

		// DB-level constraints
		DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_party_identifier_format CHECK (party_identifier ~ '^[0-9]{9,11}$')");
		DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_subject_length CHECK (char_length(subject) <= 2000)");
		DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_expiry_date_check CHECK (expiry_date IS NULL OR expiry_date >= sign_date)");
		DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_currency_length CHECK (char_length(currency) = 3)");

		// seed moved to ContractSeeder
	}

	public function down(): void
	{
		Schema::dropIfExists('contracts');
	}
};


