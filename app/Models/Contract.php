<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
	use HasFactory;

	protected $table = 'contracts';

	protected $fillable = [
		'party_name',
		'party_identifier',
		'contractor_id',
		'contract_number',
		'contract_type_id',
		'subject',
		'sign_date',
		'expiry_date',
		'branch_id',
		'department_id',
		'currency',
		'amount',
		'status',
		'responsible_manager_id',
		'payment_type',
	];

	protected $casts = [
		'sign_date' => 'date',
		'expiry_date' => 'date',
		'amount' => 'decimal:2',
	];

	public function type()
	{
		return $this->belongsTo(ContractType::class, 'contract_type_id');
	}

	public function contractor()
	{
		return $this->belongsTo(Contractor::class);
	}

	public function branch()
	{
		return $this->belongsTo(Branch::class);
	}

	public function department()
	{
		return $this->belongsTo(Department::class);
	}

	public function responsibleManager()
	{
		return $this->belongsTo(User::class, 'responsible_manager_id');
	}
}


