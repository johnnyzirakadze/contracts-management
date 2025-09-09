<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('departments')->insert([
			['id' => 1, 'branch_id' => 1, 'name' => 'ფინანსები', 'created_at' => now(), 'updated_at' => now()],
			['id' => 2, 'branch_id' => 1, 'name' => 'იურიდიული', 'created_at' => now(), 'updated_at' => now()],
			['id' => 3, 'branch_id' => 2, 'name' => 'ბუღალტერია', 'created_at' => now(), 'updated_at' => now()],
			['id' => 4, 'branch_id' => 3, 'name' => 'შესყიდვები', 'created_at' => now(), 'updated_at' => now()],
		]);
	}
}


