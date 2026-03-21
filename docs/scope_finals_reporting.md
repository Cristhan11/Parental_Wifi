# Reporting and Email Config Scope

**Handoff / progress reference:** see **`docs/scope_finals_reporting_handoff.md`** (manual tests, commands, troubleshooting, what to do next).

## 1. Purpose

This document is the implementation reference for `reporting-and-email-config` in `docs/scope_todo_finals.md`.

Goal:
- provide parent-scoped reporting configuration,
- send immediate alert emails for high-value events,
- send scheduled digest emails (daily, weekly, monthly),
- and keep a dispatch audit trail for verification.

## 2. Locked Report Scope

### 2.1 Immediate alerts

- `blocked_website_attempt`
- `flagged_website_visit`

### 2.2 Digests

- `daily`
- `weekly`
- `monthly`

### 2.3 Digest sections

- `violations_summary`
- `top_visited_domains`
- `time_usage_and_grants`
- **Per-device (email layout):** each registered child device gets its own block with the same three section types, plus email-safe horizontal bar charts comparing usage minutes and granted minutes across devices in the period. Family-level totals remain for dispatch logging.

### 2.4 Empty-period behavior

- Skip digest send when the selected period has no activity.

## 3. Implemented Architecture

### 3.1 Data model

- `reporting_preferences`
  - per-user toggles for immediate alerts and digest frequencies
  - timezone and skip-empty behavior (default timezone for new rows: **`Asia/Manila`** — Philippines, UTC+8; override with `REPORTING_DEFAULT_TIMEZONE` in `.env` or `config/reporting.php`)
- `reporting_recipients`
  - per-user recipient list
  - enable/disable support
- `report_dispatch_logs`
  - tracks `sent`, `failed`, `skipped`
  - includes report type, frequency, recipient, period, and error details

### 3.2 Backend flow

- Immediate pipeline:
  - `AccessAttempt` model emits `BlockedWebsiteAccessed` / `FlaggedWebsiteVisited`.
  - New listeners send emails to enabled recipients when immediate alerts are enabled.
  - Every attempt writes a row to `report_dispatch_logs`.
- Digest pipeline:
  - Scheduler triggers `reporting:send-digest` for each frequency.
  - Command dispatches `DispatchDigestReportJob` per opted-in parent.
  - Job builds payload through `ReportingDigestService`.
  - Job enforces skip-empty preference and logs all outcomes.

### 3.3 UI

- New Reports page:
  - manage preferences (timezone is a grouped `<select>` of IANA zones; **Recommended** lists Philippines `Asia/Manila` first, then UTC)
  - manage recipients
  - queue test daily digest
  - review latest dispatch history
- Sidebar Reports item now points to reports config route.

## 4. Locked Subject Lines and Templates

### 4.1 Immediate blocked

- Subject format: `[Parental WiFi][Alert][Blocked] {device_name} attempted {domain}`
- Template: HTML email (`immediate-blocked.blade.php`) — red/critical styling (accent bar, “Access denied” badge, summary card, table of event fields, primary CTA).

### 4.2 Immediate flagged

- Subject format: `[Parental WiFi][Alert][Flagged] {device_name} visited {domain}`
- Template: HTML email (`immediate-flagged.blade.php`) — amber/warning styling (accent bar, “Review suggested” badge, summary card, event table, primary CTA).

### 4.3 Daily digest

- Subject format: `[Parental WiFi][Daily Digest] {period_start} - {period_end} ({timezone})`
- Body sections:
  - period label
  - violations summary
  - top visited domains
  - time usage and grants
  - generated timestamp and dashboard link

### 4.4 Weekly digest

- Subject format: `[Parental WiFi][Weekly Digest] Week of {period_start} ({timezone})`
- Body sections:
  - period label
  - violations summary
  - top visited domains
  - time usage and grants
  - generated timestamp and dashboard link

### 4.5 Monthly digest

- Subject format: `[Parental WiFi][Monthly Digest] {month_name} {year} ({timezone})`
- Body sections:
  - period label
  - violations summary
  - top visited domains
  - time usage and grants
  - generated timestamp and dashboard link

## 5. Files Added/Updated

- Backend:
  - `app/Http/Controllers/ReportsController.php`
  - `app/Services/ReportingDigestService.php`
  - `app/Listeners/SendImmediateBlockedWebsiteAlert.php`
  - `app/Listeners/SendImmediateFlaggedWebsiteAlert.php`
  - `app/Jobs/DispatchDigestReportJob.php`
  - `app/Console/Commands/SendDigestReports.php`
  - `app/Console/Commands/SendTestReport.php`
  - `app/Mail/*DigestReportMail.php`
  - `app/Mail/Immediate*AlertMail.php`
  - `app/Models/ReportingPreference.php`
  - `app/Models/ReportingRecipient.php`
  - `app/Models/ReportDispatchLog.php`
  - `app/Models/User.php` (new reporting relationships)
  - `app/Providers/AppServiceProvider.php` (no manual `Event::listen` for immediate alerts — Laravel event discovery registers `app/Listeners/*` once)
  - `routes/web.php` and `routes/console.php`
- Database:
  - `database/migrations/2026_03_13_100001_create_reporting_preferences_table.php`
  - `database/migrations/2026_03_13_100002_create_reporting_recipients_table.php`
  - `database/migrations/2026_03_13_100003_create_report_dispatch_logs_table.php`
- Views:
  - `resources/views/reports/index.blade.php`
  - `resources/views/emails/reports/*.blade.php`
  - `resources/views/layouts/sidebar.blade.php` (Reports link)
- Tests:
  - `tests/Feature/ReportingEmailConfigTest.php`

## 6. Verification Commands

Run after migration:

- `php artisan migrate`
- `php artisan test --filter=ReportingEmailConfigTest`
- `php artisan reporting:send-digest daily`
- `php artisan reporting:send-test <PARENT_USER_ID>`
- **Dummy / preview email (fake numbers, same template as real digests):**
  - `php artisan reporting:send-dummy-digest <PARENT_USER_ID>` (defaults to daily)
  - `php artisan reporting:send-dummy-digest <PARENT_USER_ID> weekly`
  - `php artisan reporting:send-dummy-digest <PARENT_USER_ID> monthly`  
  Subject line includes `[Preview]` and “Sample data — not real activity”. Does not write to `report_dispatch_logs`.
- **Dummy immediate alerts (blocked + flagged templates):**
  - `php artisan reporting:send-dummy-immediate-alerts <PARENT_USER_ID>`  
  Sends two emails per recipient with sample blocked/flagged content; subjects include `[Preview]` and “not a real event”. Does not write to `report_dispatch_logs`.

## 7. Completion Mapping

- `report-01`: Reporting configuration UI for preferences and recipients -> implemented.
- `report-02`: Immediate alert pipeline for important violations -> implemented (blocked + flagged).
- `report-03`: Scheduled daily/weekly/monthly digest jobs -> implemented.
- `report-04`: Frequency-specific templates with required sections -> implemented.
- `report-05`: Per-parent opt controls + dispatch audit trail -> implemented.

