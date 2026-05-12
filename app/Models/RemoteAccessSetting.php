<?php

namespace App\Models;

use App\Services\TailscaleDashboardUrlResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy deployment row for optional reporting dashboard URL override (no longer editable in the UI).
 *
 * @property int $id
 * @property string|null $reporting_dashboard_url Full URL for "Open dashboard" in reporting emails.
 */
class RemoteAccessSetting extends Model
{
    protected $fillable = [
        'reporting_dashboard_url',
    ];

    /**
     * Resolve {@see config('reporting.email_dashboard_url')} with this precedence:
     *  1. Legacy DB row (`remote_access_settings.reporting_dashboard_url`).
     *  2. `REPORTING_DASHBOARD_URL` env (e.g. an explicit MagicDNS hostname).
     *  3. Pi's current Tailscale IPv4 via `tailscale ip -4` (auto-detected, cached) — preferred
     *     so reporting emails point parents to a URL that works off home Wi-Fi.
     *  4. `APP_URL` + `/dashboard` (LAN fallback).
     *
     * Called from {@see \App\Providers\AppServiceProvider::boot()}.
     */
    public static function applyReportingDashboardUrlToConfig(): void
    {
        if (Schema::hasTable('remote_access_settings')) {
            $dashboardUrl = static::query()->value('reporting_dashboard_url');
            if (is_string($dashboardUrl) && $dashboardUrl !== '') {
                config(['reporting.email_dashboard_url' => $dashboardUrl]);

                return;
            }
        }

        $envUrl = config('reporting.env_reporting_dashboard_url');
        if (is_string($envUrl) && $envUrl !== '') {
            config(['reporting.email_dashboard_url' => $envUrl]);

            return;
        }

        if (config('reporting.tailscale_auto_detect', true)) {
            $tailscaleUrl = app(TailscaleDashboardUrlResolver::class)->resolve();
            if (is_string($tailscaleUrl) && $tailscaleUrl !== '') {
                config(['reporting.email_dashboard_url' => $tailscaleUrl]);

                return;
            }
        }

        $appUrl = config('app.url');
        if (is_string($appUrl) && $appUrl !== '') {
            config(['reporting.email_dashboard_url' => rtrim($appUrl, '/').'/dashboard']);

            return;
        }

        config(['reporting.email_dashboard_url' => 'http://localhost/dashboard']);
    }

    /**
     * Returns the singleton settings row, creating an empty row when missing.
     */
    public static function instance(): self
    {
        return static::query()->firstOrCreate([], []);
    }
}
