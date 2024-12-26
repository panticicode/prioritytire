<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
    	'user_id',
		'import_type',
		'row_number',
		'column',
		'invalid_value',
		'validation_message'
    ];
}
