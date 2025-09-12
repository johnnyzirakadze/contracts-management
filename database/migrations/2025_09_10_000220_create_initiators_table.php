<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('initiators', function (Blueprint $table): void {
			$table->id();
			$table->string('name', 255);
			$table->string('email', 255)->nullable();
			$table->string('phone', 50)->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('initiators');
	}
};
