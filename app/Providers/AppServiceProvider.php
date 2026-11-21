<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Freno a fuerza bruta de claves: 30 intentos/min por IP + UUID
        RateLimiter::for('client-auth-guard', fn (Request $r) => Limit::perMinute(30)->by($r->ip().'|'.$r->route('uuid')));

        Gate::define('viewLaravelLogs', fn ($user) => $user?->isAdmin() === true);
    }
}
