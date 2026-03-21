<?php

namespace App\Providers;

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
        // Immediate alert listeners live under app/Listeners and are auto-registered by
        // Laravel's event discovery (see Illuminate\Foundation\Support\Providers\EventServiceProvider).
        // Do not also Event::listen() them here — that would register the same handle() twice.
    }
}
