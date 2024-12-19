<?php

use App\Http\Controllers\Dashboard\MainController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\PermissionsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [MainController::class, 'home'])->name('home');

Route::group(['middleware' => ['auth'], 'prefix' => 'dashboard', 'as' => 'dashboard.'], function(){
    Route::get('/', [MainController::class, 'dashboard'])->name('dashboard');
    Route::resource('users', UsersController::class);
    Route::resource('permissions', PermissionsController::class);
});

Auth::routes(['register' => false]);
