<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

class Permission extends Model
{
    protected static function boot()
    {
        parent::boot();

        $request = request();

        static::saving(function($model) use ($request) {

            $model->key = Str::of(Str::title($model->name))
                ->replace('&', 'and')   // We replace & with AND
                ->replace('-', '')      // Replace hyphens
                ->lower()               // Convert to lowercase
                ->snake();
        });
    }

    protected $appends = ['checkbox', 'action'];

    public function getCheckboxAttribute() {
        $checkbox = '<input type="checkbox" class="bulk" data-id="' . $this->id . '" />';

        return $checkbox;
    }

    public function getActionAttribute() {
        $button = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $viewButton   = str_replace("<button", "<button data-id='$this->id'", $button['view']);
        $editButton   = str_replace('<button', "<button data-id='$this->id'", $button['edit']);
        $deleteButton = str_replace('<button', "<button data-id='$this->id'", $button['delete']);
        
        $action = '<nobr>' . $viewButton . $editButton . $deleteButton . '</nobr>';
        
        return preg_replace('/\s+/', ' ', trim($action));
    }
    
    protected $fillable = [
        'name', 
        'description'
    ];
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                                        ->using(UserPermission::class)
                                        ->withTimestamps();
    }
}
