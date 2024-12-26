<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'user_id',
        'import_type',
        'file_name',
        'status',
    ];

    // Definisanje relacije sa korisnicima
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
