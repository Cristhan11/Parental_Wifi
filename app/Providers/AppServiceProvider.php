<?php

namespace App\Providers;

use App\Listeners\RecordSecurityAuditOnFailedLogin;
use App\Listeners\RecordSecurityAuditOnLockout;
use App\Listeners\RecordSecurityAuditOnLogin;
use App\Listeners\RecordSecurityAuditOnLogout;
use App\Models\Device;
use App\Models\ReportingRecipient;
use App\Observers\DeviceObserver;
use App\Observers\ReportingRecipientObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
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
        $proxies = config('remote_access.trusted_proxies');
        $proxyHeaders = config('remote_access.trusted_proxy_headers');
        if (is_string($proxies) && $proxies === '*') {
            TrustProxies::at('*');
        } elseif (is_array($proxies) && count($proxies) > 0) {
            TrustProxies::at($proxies);
        }
        if ($proxyHeaders !== null) {
            TrustProxies::withHeaders($proxyHeaders);
        }

        Event::listen(Login::class, RecordSecurityAuditOnLogin::class);
        Event::listen(Failed::class, RecordSecurityAuditOnFailedLogin::class);
        Event::listen(Logout::class, RecordSecurityAuditOnLogout::class);
        Event::listen(Lockout::class, RecordSecurityAuditOnLockout::class);

        ReportingRecipient::observe(ReportingRecipientObserver::class);
        Device::observe(DeviceObserver::class);

        // Immediate alert listeners live under app/Listeners and are auto-registered by
        // Laravel's event discovery (see Illuminate\Foundation\Support\Providers\EventServiceProvider).
        // Do not also Event::listen() them here — that would register the same handle() twice.
    }
}
