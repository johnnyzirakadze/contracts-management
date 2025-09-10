<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachedFile extends Model
{
	use HasFactory;

	public $timestamps = false;

	protected $table = 'attached_files';

	protected $fillable = [
		'row_id',
		'table_name',
		'file_name',
		'file_type',
		'file_size',
		'file_path',
		'uploaded_at',
	];
}


