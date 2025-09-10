<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
	use HasFactory;

	protected $table = 'contract_types';

	protected $fillable = [
		'name',
	];

	public function contracts()
	{
		return $this->hasMany(Contract::class);
	}
}


