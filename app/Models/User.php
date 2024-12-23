<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    public function getActionAttribute() {
        $button = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $viewButton   = str_replace("<button", "<button data-id='$this->id'", $button['view']);
        $editButton   = str_replace('<button', "<button data-id='$this->id'", $button['edit']);
        $deleteButton = str_replace('<button', "<button data-id='$this->id'", $button['delete']);
        
        $action = '<nobr>' . $viewButton . $editButton . $deleteButton . '</nobr>';
        
        return preg_replace('/\s+/', ' ', trim($action));
    }
}
