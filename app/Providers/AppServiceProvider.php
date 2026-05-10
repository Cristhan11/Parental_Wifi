<?php

namespace App\Providers;

use App\Listeners\RecordSecurityAuditOnFailedLogin;
use App\Listeners\RecordSecurityAuditOnLockout;
use App\Listeners\RecordSecurityAuditOnLogin;
use App\Listeners\RecordSecurityAuditOnLogout;
use App\Models\Device;
use App\Models\RemoteAccessSetting;
use App\Models\ReportingRecipient;
use App\Observers\DeviceObserver;
use App\Observers\ReportingRecipientObserver;
use App\PolicyApplyFlags;
use App\Services\PolicyApplyDebouncer;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->singleton(PolicyApplyDebouncer::class, function () {
            return PolicyApplyDebouncer::fromConfig();
        });
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

        RateLimiter::for('tailscale-auth-link', function ($request) {
            $userPart = $request->user()?->getAuthIdentifier() ?? 'guest';
            $ipPart = $request->ip() ?? '0.0.0.0';

            return Limit::perMinutes(10, 3)->by($userPart.'|'.$ipPart);
        });

        RemoteAccessSetting::applyReportingDashboardUrlToConfig();

        Event::listen(Login::class, RecordSecurityAuditOnLogin::class);
        Event::listen(Failed::class, RecordSecurityAuditOnFailedLogin::class);
        Event::listen(Logout::class, RecordSecurityAuditOnLogout::class);
        Event::listen(Lockout::class, RecordSecurityAuditOnLockout::class);

        ReportingRecipient::observe(ReportingRecipientObserver::class);
        Device::observe(DeviceObserver::class);

        Event::listen(Registered::class, function (Registered $event): void {
            /** @var \App\Models\User|null $user */
            $user = $event->user;
            if (! $user || ! $user->getKey()) {
                return;
            }

            app(PolicyApplyDebouncer::class)->requestApply((int) $user->getKey(), PolicyApplyFlags::DhcpBypass);
        });

        // Immediate alert listeners live under app/Listeners and are auto-registered by
        // Laravel's event discovery (see Illuminate\Foundation\Support\Providers\EventServiceProvider).
        // Do not also Event::listen() them here — that would register the same handle() twice.
    }
}
