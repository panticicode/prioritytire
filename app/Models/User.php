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

    const ADMIN  = 'Admin';
    const MEMBER = 'Member';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
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

    protected $appends = ['checkbox', 'action'];

    public function getCheckboxAttribute() {
        $checkbox = '<input type="checkbox" class="bulk" data-id="' . $this->id . '" />';

        return $checkbox;
    }

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

    protected function generateButton(string $permission, string $buttonTemplate): string
    {
        if (!Gate::check($permission)) 
        {
            return '';
        }

        return str_replace('<button', "<button data-id='$this->id'", $buttonTemplate);
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                                        ->using(UserPermission::class)
                                        ->withTimestamps();
    }

    public function hasUserPermission($permissionKey)
    {   
        return $this->permissions()->where('key', $permissionKey)->exists();
    }
}
