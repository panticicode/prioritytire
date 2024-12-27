<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
    	'user_id',
		'type',
		'row',
		'column',
		'value',
		'message'
    ];

    /**
     * Define the relationship with the User model.
     *
     * This method defines the belongsTo relationship between the `ImportLog` model
     * and the `User` model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
