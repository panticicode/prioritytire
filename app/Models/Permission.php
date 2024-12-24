<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use Gate;

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

    protected $fillable = [
        'name', 
        'description'
    ];

    public function getCheckboxAttribute() {
        $checkbox = '<input type="checkbox" class="bulk" data-id="' . $this->id . '" />';

        return $checkbox;
    }

    public function getActionAttribute(): string
    {
        $configButtons = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $buttons = [
            'show'   => $this->generateButton('permission_show', $configButtons['show']),
            'edit'   => $this->generateButton('permission_edit', $configButtons['edit']),
            'delete' => $this->generateButton('permission_delete', $configButtons['delete']),
        ];

        $action = '<nobr>' . implode('', $buttons) . '</nobr>';

        return preg_replace('/\s+/', ' ', trim($action));
    }

    protected function generateButton(string $permission, string $buttonTemplate): string
    {
        if (!Gate::check($permission)) 
        {
            return '';
        }

        return str_replace('<button', "<button data-id='$this->id'", $buttonTemplate);
    }
    
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                                        ->using(UserPermission::class)
                                        ->withTimestamps();
    }
}
