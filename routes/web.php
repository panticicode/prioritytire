<?php

use App\Http\Controllers\Dashboard\MainController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\PermissionsController;
use App\Http\Controllers\Dashboard\DataImportController;
use App\Http\Controllers\Dashboard\ImportedDataController;
use App\Http\Controllers\Dashboard\ImportsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Route for the home page
Route::get('/', [MainController::class, 'home'])->name('home');

/**
 * The route group for the dashboard, only accessible to authenticated users.
 *
 * This route group contains all the routes that are prefixed with 'dashboard'
 * and require authentication. It includes routes for the main dashboard page,
 * user management, and permission management.
 */

Route::group(['middleware' => ['auth'], 'prefix' => 'dashboard', 'as' => 'dashboard.'], function(){

     /**
     * Dashboard home route.
     *
     * This route returns the dashboard view for the authenticated user.
     * It maps to the `dashboard` method in the `MainController`.
     *
     * @return \Illuminate\View\View
     */

    Route::get('/', [MainController::class, 'dashboard'])->name('dashboard');

    /**
     * Resource route for user management.
     *
     * This defines a resource route for the `UsersController`, excluding 
     * the 'create' and 'edit' methods. It automatically generates routes 
     * for index, show, store, update, and destroy actions.
     *
     * @return \Illuminate\Routing\Router
     */

    Route::resource('users', UsersController::class, ['except' => ['create', 'edit']]);

    /**
     * Bulk delete route for users.
     *
     * This route allows the deletion of multiple users at once by providing
     * an array of user IDs in the URL. It maps to the `bulk_delete` method 
     * in the `UsersController`.
     *
     * @param  array  $ids
     * @return \Illuminate\Http\RedirectResponse
     */

    Route::delete('/users/{ids}/bulk', [UsersController::class, 'bulk_delete']);

     /**
     * Resource route for permission management.
     *
     * This defines a resource route for the `PermissionsController`, providing
     * CRUD functionality for managing permissions.
     *
     * @return \Illuminate\Routing\Router
     */

    Route::resource('permissions', PermissionsController::class);

    /**
     * Handle user permission changes.
     *
     * This route handles assigning or removing permissions from a user.
     * It maps to the `handle_user_permissions` method in the 
     * `PermissionsController` and accepts user IDs, permission IDs, and 
     * an action (e.g., grant or revoke).
     *
     * @param  array  $user_ids
     * @param  array  $permission_ids
     * @param  string  $action
     * @return \Illuminate\Http\RedirectResponse
     */

    Route::post('user/{user_ids}/permission/{permission_ids}/{action}', [PermissionsController::class, 'handle_user_permissions']);

    /**
     * Bulk delete route for permissions.
     *
     * This route allows the deletion of multiple permissions at once by providing
     * an array of permission IDs in the URL. It maps to the `bulk_delete` method 
     * in the `PermissionsController`.
     *
     * @param  array  $ids
     * @return \Illuminate\Http\RedirectResponse
     */

    Route::delete('/permissions/{ids}/bulk', [PermissionsController::class, 'bulk_delete']);


     /**
      * Index route for data import.
      *
      * This route displays the data import page, allowing users to view the interface 
      * for importing data. It maps to the `index` method in the `DataImportController`.
      *
      * @return \Illuminate\View\View
      */

     Route::get('/data-import', [DataImportController::class, 'index'])->name('data-import.index');

     /**
      * Import route for data import.
      *
      * This route handles the import of data submitted by the user. It processes 
      * the uploaded file and maps to the `import` method in the `DataImportController`.
      *
      * @param  \Illuminate\Http\Request  $request
      * @return \Illuminate\Http\RedirectResponse
      */

     Route::post('/data-import', [DataImportController::class, 'import'])->name('data-import.import');

     /**
      * Resource route for displaying the list of imported data.
      *
      * This route handles the retrieval of a list of imported data based on the specified
      * model and type. It filters the data according to the provided model (e.g., orders, users)
      * and type (e.g., file1, file2) to return the relevant records.
      * 
      * - `index` (GET): Displays the list of imported data, filtered by the provided model and type.
      *
      * @see ImportedDataController
      */

     Route::get('/imported-data/{model}/{type}', [ImportedDataController::class, 'index'])->name('imported-data.index');

     /**
      * Resource route for showing the details of a specific imported data entry.
      *
      * This route is used to display the details of a single imported data entry, identified by
      * its ID, model, and type. It allows for a detailed view of the data that has been imported
      * based on the specified parameters.
      * 
      * - `show` (GET): Shows details of a specific import based on the model, type, and entry ID.
      *
      * @see ImportedDataController
      */

     Route::get('/imported-data/{model}/{type}/{id}', [ImportedDataController::class, 'show'])->name('imported-data.show');

     /**
      * Resource route for deleting an imported data entry.
      *
      * This route allows for the deletion of an imported data entry, identified by the model,
      * type, and entry ID. It permanently removes the selected data from the system.
      * 
      * - `destroy` (DELETE): Deletes an imported data entry based on the model, type, and entry ID.
      *
      * @see ImportedDataController
      */

     Route::delete('/imported-data/{model}/{type}/{id}', [ImportedDataController::class, 'destroy'])->name('imported-data.destroy');

     /**
      * Index route for import logs.
      *
      * This route displays the import logs page, allowing users to view a list of 
      * logs related to previous import operations. It maps to the `index` method 
      * in the `ImportsController`.
      *
      * @return \Illuminate\View\View
      */
     
     Route::get('/imports', [ImportsController::class, 'index'])->name('imports.index');
});

/**
 * Authentication routes.
 *
 * This includes the routes for user authentication, except for the 'register'
 * route, which is disabled.
 */

Auth::routes(['register' => false]);
