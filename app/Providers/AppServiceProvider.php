<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method is used to bind any application services into the 
     * service container. You can use it to register things like 
     * bindings, singletons, or other services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * This method is used to perform any actions that need to be run 
     * after all services are registered. This is where you can 
     * define any bootstrapping logic, such as setting up gates, 
     * middleware, or events. It is called after the `register` method.
     *
     * @return void
     */
    public function boot(): void
    {
        /**
         * Register a global Gate callback to check permissions.
         *
         * This sets up a Gate callback that runs before any other
         * Gate checks. It uses the `hasUserPermission` method on the 
         * authenticated user to determine if they are authorized to
         * perform the requested action based on the ability.
         *
         * @param  \App\Models\User  $user
         * @param  string  $ability
         * @return bool|null
         */
        Gate::before(function ($user, $ability) {
            return $user->hasUserPermission($ability);
        });
    }
}
