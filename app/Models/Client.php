<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Gate;

class Client extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'email',
        'phone',
    ];

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
        if (!Gate::check($permission) && !Gate::check('import_clients_and_sales_access')) 
        {
            return '';
        }

        return str_replace('<button', "<button data-id='$this->id'", $buttonTemplate);
    }
}
