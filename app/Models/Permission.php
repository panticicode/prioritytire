<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use Gate;

class Permission extends Model
{
    /**
     * The "booting" method of the model.
     * 
     * This method is triggered when the model is booted. It listens to the `saving` event for the model,
     * where it modifies the `key` attribute by transforming the `name` attribute into a snake-case version.
     * The transformation includes replacing `&` with `and`, removing hyphens, converting to lowercase,
     * and applying the `snake` function.
     *
     * @return void
     */
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

    /**
     * The attributes that should be appended to the model's array form.
     * 
     * @var array
     */

    protected $appends = ['checkbox', 'action'];

    /**
     * The attributes that are mass assignable.
     * 
     * @var array
     */

    protected $fillable = [
        'name', 
        'description'
    ];

    /**
     * Get the checkbox HTML for the permission.
     * 
     * This accessor generates the HTML for a checkbox input element with a unique data-id
     * attribute set to the permission's ID, allowing bulk operations to target the permission.
     *
     * @return string The checkbox HTML.
     */

    public function getCheckboxAttribute() {
        $checkbox = '<input type="checkbox" class="bulk" data-id="' . $this->id . '" />';

        return $checkbox;
    }

    /**
     * Get the action buttons HTML for the permission.
     * 
     * This accessor generates the HTML for action buttons (show, edit, delete) based on 
     * user permissions. It checks if the current user has access to the specific actions 
     * and renders the corresponding buttons accordingly.
     *
     * @return string The action buttons HTML.
     */

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

     /**
     * Generate the HTML for a button based on the permission.
     * 
     * This helper method generates the HTML for a button and checks if the current user
     * has permission to perform the associated action (show, edit, delete). If the user 
     * doesn't have the required permission, an empty string is returned.
     *
     * @param string $permission The permission name.
     * @param string $buttonTemplate The button template HTML.
     * @return string The generated button HTML or an empty string if the user lacks the permission.
     */

    protected function generateButton(string $permission, string $buttonTemplate): string
    {
        if (!Gate::check($permission)) 
        {
            return '';
        }

        return str_replace('<button', "<button data-id='$this->id'", $buttonTemplate);
    }
    
    /**
     * Define the relationship with the User model.
     * 
     * This method defines the many-to-many relationship between the `Permission` model
     * and the `User` model through the `user_permissions` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                                        ->using(UserPermission::class)
                                        ->withTimestamps();
    }
}
