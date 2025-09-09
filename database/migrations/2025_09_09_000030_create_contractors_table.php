<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('contractors', function (Blueprint $table): void {
			$table->id();
			$table->string('name', 200);
			$table->string('phone', 15)->nullable();
			$table->string('email', 255)->nullable();
			$table->timestamps();
		});

		// seed moved to ContractorSeeder
	}

	public function down(): void
	{
		Schema::dropIfExists('contractors');
	}
};


