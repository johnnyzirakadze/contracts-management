<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('branches')->insert([
			['id' => 1, 'name' => 'ცენტრალური', 'created_at' => now(), 'updated_at' => now()],
			['id' => 2, 'name' => 'ვერა', 'created_at' => now(), 'updated_at' => now()],
			['id' => 3, 'name' => 'დიღომი', 'created_at' => now(), 'updated_at' => now()],
		]);
	}
}


