<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractSeeder extends Seeder
{
	public function run(): void
	{
		DB::table('contracts')->insert([
			[
				'id' => 1,
				'party_name' => 'შპს ალფა',
				'party_identifier' => '12345678901',
				'contractor_id' => 1,
				'contract_number' => 'CNT-2025-0001',
				'contract_type_id' => 1,
				'subject' => 'სერვისების მიწოდება და მხარდაჭერა',
				'sign_date' => '2025-09-01',
				'expiry_date' => '2026-09-01',
				'branch_id' => 1,
				'department_id' => 1,
				'currency' => 'GEL',
				'amount' => '12000.00',
				'status' => 'დასამტკიცებელი',
				'responsible_manager_id' => null,
				'payment_type' => 'ყოველთვიური',
				'created_at' => now(),
				'updated_at' => now(),
			],
			[
				'id' => 2,
				'party_name' => 'ფიზიკური პირი ბ',
				'party_identifier' => '123456789',
				'contractor_id' => null,
				'contract_number' => 'CNT-2025-0002',
				'contract_type_id' => 3,
				'subject' => 'შრომითი ხელშეკრულება',
				'sign_date' => '2025-08-15',
				'expiry_date' => '2026-08-15',
				'branch_id' => 2,
				'department_id' => 3,
				'currency' => 'GEL',
				'amount' => '36000.00',
				'status' => 'აქტიური',
				'responsible_manager_id' => null,
				'payment_type' => 'დანაწევრებული',
				'created_at' => now(),
				'updated_at' => now(),
			],
		]);
	}
}


