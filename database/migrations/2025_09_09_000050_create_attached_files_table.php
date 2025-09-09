<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('attached_files', function (Blueprint $table): void {
			$table->id();
			$table->unsignedBigInteger('row_id');
			$table->string('table_name', 100);
			$table->string('file_name', 255);
			$table->enum('file_type', ['pdf', 'docx', 'other']);
			$table->unsignedBigInteger('file_size');
			$table->string('file_path', 500);
			$table->timestamp('uploaded_at')->useCurrent();
		});

		// seed moved to AttachedFileSeeder
	}

	public function down(): void
	{
		Schema::dropIfExists('attached_files');
	}
};


