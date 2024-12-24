<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class UserPermission extends Pivot
{
    /**
     * The table associated with the model.
     *
     * This defines the name of the database table that the model 
     * interacts with. In this case, it is 'user_permissions'.
     *
     * @var string
     */
    protected $table = 'user_permissions';
}
