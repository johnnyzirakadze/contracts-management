<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
	use HasFactory;

	protected $table = 'branches';

	protected $fillable = [
		'name',
	];

	public function departments()
	{
		return $this->hasMany(Department::class);
	}

	public function contracts()
	{
		return $this->hasMany(Contract::class);
	}
}


