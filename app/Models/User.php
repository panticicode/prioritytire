<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Gate;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * This defines the attributes that can be mass-assigned during
     * model creation or update.
     *
     * @var list<string>
     */
    protected $fillable = [
        'is_admin',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * These attributes are not included when the model is serialized
     * to JSON or arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * This method casts certain model attributes to their desired
     * data types, such as converting the `email_verified_at`
     * field to a `datetime` instance.
     *
     * @return array<string, string>
     */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */

    protected $appends = ['checkbox', 'action'];

    /**
     * Get the checkbox HTML for the user.
     *
     * This accessor generates the HTML for a checkbox input element with a unique
     * data-id attribute set to the user's ID, allowing bulk operations to target the user.
     *
     * @return string The checkbox HTML.
     */

    public function getCheckboxAttribute() {
        $checkbox = '<input type="checkbox" class="bulk" data-id="' . $this->id . '" />';

        return $checkbox;
    }

    /**
     * Get the action buttons HTML for the user.
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
            'show'   => $this->generateButton('user_show', $configButtons['show']),
            'edit'   => $this->generateButton('user_edit', $configButtons['edit']),
            'delete' => $this->generateButton('user_delete', $configButtons['delete']),
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
     * Define the relationship with the Permission model.
     *
     * This method defines the many-to-many relationship between the `User` model
     * and the `Permission` model through the `user_permissions` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                                        ->using(UserPermission::class)
                                        ->withTimestamps();
    }

    /**
     * Define the relationship with the Order model.
     *
     * This method defines the many-to-many relationship between the `User` model
     * and the `Order` model through the `user_orders` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */

    public function orders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'user_orders')
                                        ->using(UserOrder::class)
                                        ->withPivot('type')
                                        ->withTimestamps();
    }

    /**
     * Check if the user has a specific permission.
     *
     * This method checks whether the user has a permission with the given key by querying
     * the permissions relationship.
     *
     * @param string $permissionKey The key of the permission to check.
     * @return bool True if the user has the permission, otherwise false.
     */
    
    public function hasUserPermission($permissionKey)
    {   
        return $this->permissions()->where('key', $permissionKey)->exists();
    }

    /**
     * Check if the user is an admin.
     *
     * This method is a query scope that checks if a user has the `is_admin` attribute set to true. 
     * It uses the `admin` scope to filter users by their admin status and checks if any matching record exists.
     *
     * @return bool True if the user is an admin, otherwise false.
     */
    public function scopeIsAdmin()
    {
        return $this->admin(true)->exists();
    }

    /**
     * Scope for filtering users by admin status.
     *
     * This scope filters users based on the `is_admin` field, allowing the query 
     * to retrieve users who are either admins or non-admins, depending on the 
     * value of the `$role` parameter.
     * 
     * The `$role` parameter should be a boolean value (true for admin, false for non-admin).
     * This method is applied to the `User` model.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    
    public function scopeAdmin($query, $role)
    {
        return $query->where('is_admin', $role); 
    }
}
