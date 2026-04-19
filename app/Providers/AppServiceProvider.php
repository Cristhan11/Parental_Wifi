<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\ReportingRecipient;
use App\Observers\DeviceObserver;
use App\Observers\ReportingRecipientObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Global app bootstrap. Reporting immediate-alert listeners are **not** registered here — they live under
 * `app/Listeners` and are picked up by Laravel’s event discovery to avoid duplicate handler registration.
 */
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
        ReportingRecipient::observe(ReportingRecipientObserver::class);
        Device::observe(DeviceObserver::class);

        // Immediate alert listeners live under app/Listeners and are auto-registered by
        // Laravel's event discovery (see Illuminate\Foundation\Support\Providers\EventServiceProvider).
        // Do not also Event::listen() them here — that would register the same handle() twice.
    }
}
