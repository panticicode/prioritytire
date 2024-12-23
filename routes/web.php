<?php

use App\Http\Controllers\Dashboard\MainController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\PermissionsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [MainController::class, 'home'])->name('home');

Route::group(['middleware' => ['auth'], 'prefix' => 'dashboard', 'as' => 'dashboard.'], function(){
    Route::get('/', [MainController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UsersController::class, ['except' => ['create', 'edit']]);
    Route::delete('/users/{ids}/bulk', [UsersController::class, 'bulk_delete']);
    Route::resource('permissions', PermissionsController::class);
    Route::post('user/{user_ids}/permission/{permission_ids}/assign', [PermissionsController::class, 'assign_user_permissions']);
    Route::delete('/permissions/{ids}/bulk', [PermissionsController::class, 'bulk_delete']);
});

Auth::routes(['register' => false]);
