<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Gate;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'row',
        'order_date',
        'channel',
        'sku',
        'item_description',
        'origin',
        'so_num',
        'cost',
        'shipping_cost',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date'    => 'date',
        'cost'          => 'float',
        'shipping_cost' => 'float',
        'total_price'   => 'float',
    ];

    /**
     * Accessor for the "order_date" attribute.
     *
     * This accessor formats the "order_date" attribute from its original
     * database format into a more user-friendly format "d/m/Y" (e.g., 12/12/2021).
     *
     * @param  string  $value  The original value of the "order_date" attribute from the database.
     * @return string          The formatted date in "d/m/Y" format.
     */
    public function getOrderDateAttribute($value): string
    {
        return Carbon::parse($value)->format('d/m/Y');
    }

    /**
     * Mutator for the "order_date" attribute.
     *
     * This mutator ensures the order_date attribute is stored in the correct format.
     *
     * @param  string  $value  The value to be set to the "order_date" attribute.
     * @return void
     */
    public function setOrderDateAttribute($value): void
    {
       $this->attributes['order_date'] = Carbon::createFromFormat('d.m.Y', $value)->format('Y-m-d');
    }

    /**
     * Get the action buttons HTML for the order.
     *
     * This accessor generates the HTML for action buttons (show and delete) based on
     * user permissions. It checks if the current user has access to the specific actions
     * and renders the corresponding buttons accordingly.
     *
     * @return string The action buttons HTML.
     */

    public function getActionAttribute(): string
    {
        $configButtons = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $buttons = [
            'show'   => $this->generateButton('imported_data_show', $configButtons['show']),
            'delete' => $this->generateButton('imported_data_delete', $configButtons['delete']),
        ];

        $action = '<nobr>' . implode('', $buttons) . '</nobr>';

        return preg_replace('/\s+/', ' ', trim($action));
    }

    /**
     * Generate the HTML for a button based on the permission.
     *
     * This helper method generates the HTML for a button and checks if the current user
     * has permission to perform the associated action (show and delete). If the user
     * doesn't have the required permission, an empty string is returned.
     *
     * @param string $permission The permission name.
     * @param string $buttonTemplate The button template HTML.
     * @return string The generated button HTML or an empty string if the user lacks the permission.
     */

    protected function generateButton(string $permission, string $buttonTemplate): string
    {
        if (!Gate::check($permission) && !Gate::check('import_orders_access')) 
        {
            return '';
        }

        return str_replace('<button', "<button data-id='$this->id'", $buttonTemplate);
    }

    /**
     * Define the relationship with the User model.
     *
     * This method defines the many-to-many relationship between the `Order` model
     * and the `User` model through the `user_orders` pivot table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_orders')
                                        ->using(UserOrder::class)
                                        ->withPivot('type')
                                        ->withTimestamps();
    }

    /**
     * Establishes a one-to-many relationship with the AuditLog model.
     *
     * This method defines a relationship where the current model can have many associated AuditLog entries.
     * Each AuditLog entry is linked through the 'model_id' foreign key.
     * Additionally, the related AuditLog entries are eager loaded with their corresponding 'import' relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    
    public function audits(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(AuditLog::class, 'model_id', 'id')->with('import');
    }
}
