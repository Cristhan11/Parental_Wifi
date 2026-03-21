<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model = one database table mapped to a PHP class.
 *
 * Table: `reporting_preferences` (see migration `2026_03_13_100001_...`).
 * Each parent account should have at most ONE row (`user_id` is unique in the migration).
 *
 * What this stores:
 * - Which email “channels” are on (immediate alerts vs daily/weekly/monthly digests).
 * - `timezone` — used to format times in emails and to interpret “yesterday / last week” windows in jobs.
 * - `skip_empty_digests` — if true, the digest job may skip sending when there was no activity in the period.
 *
 * Interconnects:
 * - UI: ReportsController reads/writes this.
 * - Jobs: DispatchDigestReportJob reads it before building/sending digests.
 * - Listeners: immediate alert listeners read `immediate_alerts_enabled` and `timezone`.
 */
class ReportingPreference extends Model
{
    /**
     * `HasFactory` lets PHPUnit / seeders build fake rows via `ReportingPreference::factory()` if you add a factory later.
     */
    use HasFactory;

    /**
     * Mass assignment guard: only these columns can be filled via `ReportingPreference::create([...])` or `->update([...])`.
     * Any other key in the array is silently ignored — helps prevent accidentally setting arbitrary DB columns.
     */
    protected $fillable = [
        'user_id',
        'immediate_alerts_enabled',
        'daily_digest_enabled',
        'weekly_digest_enabled',
        'monthly_digest_enabled',
        'timezone',            // IANA string, e.g. "Asia/Manila"
        'skip_empty_digests',
    ];

    /**
     * Casts: when you read these attributes from the model, Laravel converts DB values to PHP types.
     * Example: DB stores 0/1 but `$preference->daily_digest_enabled` is a real boolean true/false.
     *
     * @return array<string, string> attribute name => cast type
     */
    protected function casts(): array
    {
        return [
            'immediate_alerts_enabled' => 'boolean',
            'daily_digest_enabled' => 'boolean',
            'weekly_digest_enabled' => 'boolean',
            'monthly_digest_enabled' => 'boolean',
            'skip_empty_digests' => 'boolean',
        ];
    }

    /**
     * Relationship: this preference row belongs to one User (the parent account).
     * Usage: `$preference->user` returns a User model; inverse is `$user->reportingPreference`.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
