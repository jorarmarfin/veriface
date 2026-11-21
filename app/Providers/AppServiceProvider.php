<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Visor de logs (luiscamp/laravel-log-monitor): solo administradores (ADMIN_EMAILS)
        Gate::define('viewLaravelLogs', fn ($user) => $user?->isAdmin() === true);
    }
}
