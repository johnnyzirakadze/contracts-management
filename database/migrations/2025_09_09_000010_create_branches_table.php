<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('branches', function (Blueprint $table): void {
			$table->id();
			$table->string('name', 100)->unique();
			$table->timestamps();
		});

		// seed moved to BranchSeeder
	}

	public function down(): void
	{
		Schema::dropIfExists('branches');
	}
};


