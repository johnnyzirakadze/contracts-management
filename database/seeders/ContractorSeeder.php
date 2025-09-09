<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractorSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('contractors')->insert([
			['id' => 1, 'name' => 'შპს ალფა', 'phone' => '+995595000001', 'email' => 'alpha@example.com', 'created_at' => now(), 'updated_at' => now()],
			['id' => 2, 'name' => 'შპს ბეტა', 'phone' => '+995595000002', 'email' => 'beta@example.com', 'created_at' => now(), 'updated_at' => now()],
			['id' => 3, 'name' => 'სს.gamma', 'phone' => null, 'email' => null, 'created_at' => now(), 'updated_at' => now()],
		]);
	}
}


