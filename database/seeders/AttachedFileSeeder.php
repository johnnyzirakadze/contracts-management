<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttachedFileSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('attached_files')->insert([
			[
				'id' => 1,
				'row_id' => 1,
				'table_name' => 'contracts',
				'file_name' => 'contract-0001.pdf',
				'file_type' => 'pdf',
				'file_size' => 524288,
				'file_path' => '/storage/contracts/contract-0001.pdf',
				'uploaded_at' => now(),
			],
			[
				'id' => 2,
				'row_id' => 2,
				'table_name' => 'contracts',
				'file_name' => 'contract-0002.docx',
				'file_type' => 'docx',
				'file_size' => 1048576,
				'file_path' => '/storage/contracts/contract-0002.docx',
				'uploaded_at' => now(),
			],
		]);
	}
}


