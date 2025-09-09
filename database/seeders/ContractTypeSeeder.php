<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractTypeSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('contract_types')->insert([
			['id' => 1, 'name' => 'მომსახურება', 'created_at' => '2025-09-09 19:24:25', 'updated_at' => '2025-09-09 19:24:25'],
			['id' => 2, 'name' => 'ნასყიდობა', 'created_at' => '2025-09-09 19:24:25', 'updated_at' => '2025-09-09 19:24:25'],
			['id' => 3, 'name' => 'შრომითი', 'created_at' => '2025-09-09 19:24:25', 'updated_at' => '2025-09-09 19:24:25'],
			['id' => 4, 'name' => 'ქირავნობა', 'created_at' => '2025-09-09 19:24:25', 'updated_at' => '2025-09-09 19:24:25'],
			['id' => 5, 'name' => 'იჯარა', 'created_at' => '2025-09-09 19:24:25', 'updated_at' => '2025-09-09 19:24:25'],
		]);
	}
}


