<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('departments', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('branch_id')->constrained('branches')->cascadeOnUpdate()->restrictOnDelete();
			$table->string('name', 200);
			$table->timestamps();
		});

		// seed moved to DepartmentSeeder
	}

	public function down(): void
	{
		Schema::dropIfExists('departments');
	}
};


