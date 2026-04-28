<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkSaveReportingRecipientsRequest;
use App\Http\Requests\StoreReportingRecipientRequest;
use App\Http\Requests\UpdateReportingPreferencesRequest;
use App\Http\Requests\UpdateReportingRecipientRequest;
use App\Jobs\DispatchDigestReportJob;
use App\Models\ReportDispatchLog;
use App\Models\ReportingPreference;
use App\Models\ReportingRecipient;
use App\Support\ReportingTimezoneOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Web UI for reporting: preferences, recipients, dispatch history, and “send test daily digest” (queues a job).
 *
 * Flow (high level):
 * 1. Parent saves preferences → {@see ReportingPreference} updated (timezone, toggles).
 * 2. Parent manages recipients → {@see ReportingRecipient} CRUD.
 * 3. “Send test daily” → {@see DispatchDigestReportJob} queued (requires queue worker + SMTP).
 * 4. Dispatch logs are read-only display of {@see ReportDispatchLog}.
 *
 * Related routes: `routes/web.php` → `reports.*` middleware `role.parent` (and admin where applicable).
 */
class ReportsController extends Controller
{
    /**
     * Reporting configuration screen for parent/admin users.
     *
     * Why this page exists:
     * - one place to manage recipients, cadence, and delivery behavior.
     * - keeps reporting scope auditable via dispatch logs shown in the same screen.
     */
    public function index(Request $request): View
    {
        // `user()` returns the logged-in User model (thanks to `auth` middleware on the route group).
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

        // `firstOrCreate`: if a row exists for this user_id, load it; otherwise INSERT defaults and return the new model.
        $preferences = ReportingPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'immediate_alerts_enabled' => true,
                'daily_digest_enabled' => true,
                'weekly_digest_enabled' => true,
                'monthly_digest_enabled' => true,
                'timezone' => config('reporting.default_timezone'),
                'skip_empty_digests' => true,
            ]
        );

        $recipients = ReportingRecipient::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_enabled')
            ->orderBy('email')
            ->get();

        // Recent email audit rows (newest first) — keep the list short for performance.
        $dispatchLogs = ReportDispatchLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        // `view()` renders resources/views/reports/index.blade.php and passes these variables into it.
        return view('reports.index', [
            'preferences' => $preferences,
            'recipients' => $recipients,
            'dispatchLogs' => $dispatchLogs,
            'timezoneGroups' => ReportingTimezoneOptions::grouped(),
        ]);
    }

    public function updatePreferences(UpdateReportingPreferencesRequest $request): RedirectResponse
    {
        // FormRequest already ran validation rules — if we are here, input is valid.
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

        $preferences = ReportingPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'immediate_alerts_enabled' => true,
                'daily_digest_enabled' => true,
                'weekly_digest_enabled' => true,
                'monthly_digest_enabled' => true,
                'timezone' => config('reporting.default_timezone'),
                'skip_empty_digests' => true,
            ]
        );

        // HTML checkboxes: checked = "1" sent; unchecked = field missing. `boolean()` maps missing → false.
        $preferences->update([
            'immediate_alerts_enabled' => $request->boolean('immediate_alerts_enabled'),
            'daily_digest_enabled' => $request->boolean('daily_digest_enabled'),
            'weekly_digest_enabled' => $request->boolean('weekly_digest_enabled'),
            'monthly_digest_enabled' => $request->boolean('monthly_digest_enabled'),
            'skip_empty_digests' => $request->boolean('skip_empty_digests'),
            'timezone' => $request->string('timezone')->toString(),
        ]);

        // Flash `success` into session — the Blade view shows it once on the next request.
        return redirect()
            ->route('reports.index')
            ->with('success', 'Reporting preferences updated.');
    }

    /**
     * Replace the signed-in user’s recipient list with the submitted rows (updates, creates, and deletes missing ids).
     */
    public function bulkSaveRecipients(BulkSaveReportingRecipientsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

        /** @var array<int, array{id?: int|null, label?: string|null, email: string, is_enabled?: bool}> $rows */
        $rows = array_values($request->validated('recipients'));

        DB::transaction(function () use ($user, $rows): void {
            $keepIds = collect($rows)
                ->pluck('id')
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $deleteQuery = ReportingRecipient::query()->where('user_id', $user->id);
            if ($keepIds !== []) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }
            $deleteQuery->delete();

            foreach ($rows as $row) {
                $id = isset($row['id']) && $row['id'] !== null ? (int) $row['id'] : null;
                $payload = [
                    'label' => $row['label'] ?? null,
                    'email' => $row['email'],
                    'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                ];

                if ($id) {
                    $recipient = ReportingRecipient::query()
                        ->where('user_id', $user->id)
                        ->where('id', $id)
                        ->firstOrFail();
                    $recipient->update($payload);
                } else {
                    ReportingRecipient::create(array_merge($payload, ['user_id' => $user->id]));
                }
            }
        });

        return redirect()
            ->route('reports.index')
            ->with('success', 'Recipients saved.');
    }

    public function storeRecipient(StoreReportingRecipientRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

        // Second arg `true` to `boolean()` = default when checkbox missing (treat as enabled).
        ReportingRecipient::create([
            'user_id' => $user->id,
            'label' => $request->input('label'),
            'email' => $request->input('email'),
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Recipient added.');
    }

    public function updateRecipient(UpdateReportingRecipientRequest $request, ReportingRecipient $recipient): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);
        // Route could contain another user’s id if tampered — never trust the URL alone.
        abort_if($recipient->user_id !== $user->id, 403);

        $recipient->update([
            'label' => $request->input('label'),
            'email' => $request->input('email'),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Recipient updated.');
    }

    public function destroyRecipient(Request $request, ReportingRecipient $recipient): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);
        abort_if($recipient->user_id !== $user->id, 403);

        $recipient->delete();

        return redirect()
            ->route('reports.index')
            ->with('success', 'Recipient removed.');
    }

    public function sendTestDigest(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

        // Does not send email in this request — only pushes a job. Requires `queue:work` + valid MAIL_*.
        // Third arg: unique subject suffix so Gmail does not thread multiple test sends as one conversation.
        DispatchDigestReportJob::dispatch($user->id, 'daily', isManualTest: true);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Test daily digest queued.');
    }

    /**
     * Central guard: only parent and admin roles may open reporting routes (defense in depth with middleware).
     * `abort_unless(condition, 403)` throws HttpException if condition is false → browser shows 403 Forbidden.
     */
    private function ensureCanManageReports(string $role): void
    {
        abort_unless(in_array($role, ['parent', 'admin', 'parent_admin'], true), 403);
    }
}
