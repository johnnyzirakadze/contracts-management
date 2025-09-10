<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
	use HasFactory;

	protected $table = 'departments';

	protected $fillable = [
		'branch_id',
		'name',
	];

	public function branch()
	{
		return $this->belongsTo(Branch::class);
	}

	public function contracts()
	{
		return $this->hasMany(Contract::class);
	}
}


