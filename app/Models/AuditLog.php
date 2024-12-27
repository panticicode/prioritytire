<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'import_id',
        'model_id',
        'model',
        'row',
        'column',
        'old_value',
        'new_value'
    ];

    public function import(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
