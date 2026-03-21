# Reporting & logs — code map (tutorial prep)

This file complements **inline comments** in the codebase (commands, job, mailables, models, `ReportsController`, listeners, etc.). Those comments explain Laravel concepts (FormRequest, `boolean()`, queues, `firstOrCreate`, …) for newcomers.

Use this doc as a checklist when writing the Raspberry Pi / SMTP tutorial.

## End-to-end digest (scheduled email)

1. **Schedule** — `routes/console.php` runs `reporting:send-digest {frequency}` at fixed clock times (server local time unless you change it).
2. **Command** — `app/Console/Commands/SendDigestReports.php` finds opted-in parents and queues one job per user.
3. **Job** — `app/Jobs/DispatchDigestReportJob.php` builds the period window, calls `ReportingDigestService`, sends mail, writes `report_dispatch_logs`. Optional `isManualTest` (UI test button / `reporting:send-test`) appends a unique `[Test …]` subject suffix so Gmail does not merge sends into one thread.
4. **Data** — `app/Services/ReportingDigestService.php` reads `AccessAttempt`, `BrowsingLog`, sessions, grants, etc.
5. **Templates** — `resources/views/emails/reports/*-digest.blade.php` include `_digest-body.blade.php`.

**Pi requirements:** `php artisan schedule:run` (cron every minute) + `php artisan queue:work` + correct `.env` `MAIL_*`.

## Immediate alerts (event → SMTP)

1. **Events** — `BlockedWebsiteAccessed`, `FlaggedWebsiteVisited` (also broadcast for the dashboard).
2. **Listeners** — `SendImmediateBlockedWebsiteAlert`, `SendImmediateFlaggedWebsiteAlert` (registered via Laravel event discovery; see `AppServiceProvider` note — no duplicate `Event::listen`).
3. **Mail** — `ImmediateBlockedWebsiteAlertMail`, `ImmediateFlaggedWebsiteAlertMail` → Blade under `resources/views/emails/reports/immediate-*.blade.php`.
4. **Policy** — `reporting_preferences.immediate_alerts_enabled` + at least one enabled `reporting_recipients` row.

## Web UI

- **Routes** — `routes/web.php` prefix `reports.*` → `ReportsController`.
- **Screen** — `resources/views/reports/index.blade.php`.
- **Validation** — `UpdateReportingPreferencesRequest`, `StoreReportingRecipientRequest`, `UpdateReportingRecipientRequest`.

## Logs UI vs email

- **Interactive logs** — `LogsController` + `resources/views/logs/index.blade.php` (investigation, export).
- **Email digests** — same *kinds* of underlying activity (e.g. attempts, browsing) summarized by `ReportingDigestService`; different presentation and scheduling.

## Dev / QA commands

| Command | Purpose |
|--------|---------|
| `reporting:send-test {user_id}` | Queue daily digest for one parent (manual-test subject suffix) |
| `reporting:send-dummy-digest …` | Fake numbers, preview templates (no dispatch log rows) |
| `reporting:send-dummy-immediate-alerts …` | Preview immediate templates |
| `php scripts/debug_listeners.php` | Inspect registered listeners (duplicate-handler debugging) |

## Raspberry Pi — post-deploy check

After `composer install`, `.env`, `php artisan migrate --force`, cron + queue worker:

```bash
chmod +x scripts/pi_verify_reporting_and_logs.sh
./scripts/pi_verify_reporting_and_logs.sh
```

This runs `ReportingEmailConfigTest` + `PiCriticalLogsReportingTest` (SQLite in-memory via `phpunit.xml`, no real SMTP). Optional live SMTP/queue test: set `PI_LIVE_DIGEST_TEST=1` and `PI_PARENT_USER_ID`.

## Tests

- `tests/Feature/ReportingEmailConfigTest.php`

## Longer scope docs (if present)

- `docs/scope_finals_reporting.md`
- `docs/scope_finals_reporting_handoff.md`
