<?php

use App\Http\Controllers\Dashboard\MainController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\PermissionsController;
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
});

/**
 * Authentication routes.
 *
 * This includes the routes for user authentication, except for the 'register'
 * route, which is disabled.
 */

Auth::routes(['register' => false]);
