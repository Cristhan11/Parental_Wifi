# Reporting & Email — Progress Reference (Handoff)

Use this document as a **quick reference** for what was built, verified, and how to re-test or operate reporting email in production.

**Related:** `docs/scope_finals_reporting.md` (full technical scope), `docs/scope_todo_finals.md` → group **reporting-and-email-config** (all items checked).

---

## 1. Status snapshot

| Area | Status | Notes |
|------|--------|--------|
| Immediate alerts (blocked / flagged) | ✅ | HTML emails; dispatch logs per send |
| Daily / weekly / monthly digests | ✅ | Per-device sections + family overview in digest HTML |
| Reports UI (preferences, recipients, dispatch history) | ✅ | Timezone = grouped select; Philippines default `Asia/Manila` |
| Queue (`database` driver) | ✅ | `DispatchDigestReportJob` queued; worker must run |
| Gmail SMTP | ✅ | Verified sends received by real recipient |
| Manual tests (weekly / monthly) | ✅ | User confirmed email received and read |

---

## 2. Architecture reminders

- **Immediate alerts:** `AccessAttempt` → events `BlockedWebsiteAccessed` / `FlaggedWebsiteVisited` → listeners under `app/Listeners/`.  
  **Do not** register the same listener again in `AppServiceProvider` — Laravel **event discovery** already registers `app/Listeners/*` once (`Listener@handle`). Duplicate registration caused **double emails** until removed.
- **Digests:** `routes/console.php` schedules `reporting:send-digest` (daily/weekly/monthly). Manual runs: `php artisan reporting:send-digest {frequency} --user_id=ID`.
- **Default timezone (new rows):** `config/reporting.php` → `Asia/Manila` (Philippines, UTC+8). Override: `REPORTING_DEFAULT_TIMEZONE` in `.env`.

---

## 3. Manual test commands (PowerShell)

From project root. Replace `2` with the **parent user id** if different.

### Queue worker (keep running in one terminal)

```powershell
php artisan queue:work -v
```

### Targeted digest (other terminal)

```powershell
php artisan reporting:send-digest weekly --user_id=2
php artisan reporting:send-digest monthly --user_id=2
```

### If digest is skipped (“no activity”)

Temporarily disable skip-empty, then re-run the digest command, then restore:

```powershell
php artisan tinker --execute "DB::table('reporting_preferences')->where('user_id',2)->update(['skip_empty_digests'=>0]);"
```

```powershell
php artisan tinker --execute "DB::table('reporting_preferences')->where('user_id',2)->update(['skip_empty_digests'=>1]);"
```

### Test daily digest from UI

**Reports → Send Test Daily Digest** (queues job; requires worker).

### Preview-only (no `report_dispatch_logs` row)

```powershell
php artisan reporting:send-dummy-digest 2 weekly
php artisan reporting:send-dummy-digest 2 monthly
php artisan reporting:send-dummy-immediate-alerts 2
```

---

## 4. Verification checklist

- [ ] `.env` mail settings correct (`MAIL_*`), `QUEUE_CONNECTION=database`
- [ ] `php artisan migrate` applied for reporting tables
- [ ] At least one **enabled** recipient on **Reports**
- [ ] Worker running when testing queued jobs
- [ ] **Dispatch history** on Reports page matches expected `sent` / `skipped` / `failed`
- [ ] After template changes: `php artisan view:clear` and restart `queue:work` if emails look stale

---

## 5. Troubleshooting

| Symptom | Likely cause | Action |
|--------|----------------|--------|
| Duplicate immediate emails | Double listener registration | Only `app/Listeners` + discovery; no duplicate `Event::listen` in `AppServiceProvider` |
| Digest not sent | Skip empty + no activity | Add activity in window or turn off skip-empty for test |
| Job not running | No worker | `php artisan queue:work` or `queue:work --once` after enqueue |
| Old email HTML | View cache / worker | `php artisan view:clear`, restart worker |
| `BroadcastException` on `AccessAttempt::create` | Reverb not running | `BROADCAST_CONNECTION=log` for local tests, or run Reverb |

---

## 6. What to do next (project order)

From `docs/scope_todo_finals.md` **Execution Order**, the next major groups after **reporting-and-email-config** (done) are:

1. **account-separation-and-access-model** — `acct-01` … `acct-05` (roles, permissions, navigation, backend enforcement).
2. **remote-dashboard-access** — `remote-01` … `remote-04` (secure remote access, TLS, audit).
3. **validation-and-done-criteria** — project-wide acceptance checks.
4. **documentation-alignment** — last.

**Optional reporting follow-ups (not blocking):**

- Production cron: `* * * * * php artisan schedule:run` so scheduled digests run without manual `send-digest`.
- Monitor `report_dispatch_logs` for `failed` and alert ops.
- Rotate/regenerate app passwords if `.env` was ever shared.

---

## 7. Change log (high level)

- Duplicate immediate alerts fixed (event discovery vs manual `Event::listen`).
- Digest emails redesigned: family overview, per-device blocks, email-safe usage/grant bars.
- Immediate blocked/flagged emails: professional HTML styling.
- Reports recipients table: Label / Email / Status / Actions columns aligned; edit via `form` attribute pattern.
- `ReportingTimezoneOptions` + Philippines default; `config/reporting.php`.
- Dummy preview commands: `reporting:send-dummy-digest`, `reporting:send-dummy-immediate-alerts`.

---

*Last updated for handoff after weekly/monthly manual verification and recipient email read confirmation.*
