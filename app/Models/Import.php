<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Gate;

class Import extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'filename',
        'status',
    ];

    /**
     * Formats column names for the select method.
     *
     * @param array $columns
     * @return array
     */
    public function formatColumnsForSelect(array $columns): Array
    {
        return array_map(function ($column) {
            return $column === "user" ? "user_id" : $column;
        }, $columns);
    }

    /**
     * Get the action buttons HTML for the order.
     *
     * This accessor generates the HTML for action button show logs based on
     * user permissions. It checks if the current user has access to the specific actions
     * and renders the corresponding button accordingly.
     *
     * @return string The action buttons HTML.
     */

    public function getActionAttribute(): string
    {
        $configButtons = array_map('trim', config('adminlte.plugins.Datatables.actions.buttons'));

        $buttons = [
            'show' => $this->generateButton('import_logs_show', $configButtons['show']),
        ];

        $action = '<nobr>' . implode('', $buttons) . '</nobr>';

        return preg_replace('/\s+/', ' ', trim($action));
    }

    /**
     * Generate the HTML for a button based on the permission.
     *
     * This helper method generates the HTML for a button and checks if the current user
     * has permission to perform the associated action show. If the user
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(ImportLog::class, 'type', 'type')->with('user');
    }
}
