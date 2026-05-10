<?php

namespace App\Models;

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
     * Resolve {@see config('reporting.email_dashboard_url')} after env: legacy DB row, then
     * REPORTING_DASHBOARD_URL, then APP_URL + /dashboard, then a last-resort default.
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

        $appUrl = config('app.url');
        if (is_string($appUrl) && $appUrl !== '') {
            config(['reporting.email_dashboard_url' => rtrim($appUrl, '/').'/dashboard']);

            return;
        }

        config(['reporting.email_dashboard_url' => 'http://100.102.52.117/dashboard']);
    }

    /**
     * Returns the singleton settings row, creating an empty row when missing.
     */
    public static function instance(): self
    {
        return static::query()->firstOrCreate([], []);
    }
}
