<?php

namespace App\Http\Controllers;

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
use Illuminate\View\View;

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

        $recipients = ReportingRecipient::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_enabled')
            ->orderBy('email')
            ->get();

        $dispatchLogs = ReportDispatchLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('reports.index', [
            'preferences' => $preferences,
            'recipients' => $recipients,
            'dispatchLogs' => $dispatchLogs,
            'timezoneGroups' => ReportingTimezoneOptions::grouped(),
        ]);
    }

    public function updatePreferences(UpdateReportingPreferencesRequest $request): RedirectResponse
    {
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

        // Checkboxes are absent when unchecked, so we coerce explicit booleans.
        $preferences->update([
            'immediate_alerts_enabled' => $request->boolean('immediate_alerts_enabled'),
            'daily_digest_enabled' => $request->boolean('daily_digest_enabled'),
            'weekly_digest_enabled' => $request->boolean('weekly_digest_enabled'),
            'monthly_digest_enabled' => $request->boolean('monthly_digest_enabled'),
            'skip_empty_digests' => $request->boolean('skip_empty_digests'),
            'timezone' => $request->string('timezone')->toString(),
        ]);

        return redirect()
            ->route('reports.index')
            ->with('success', 'Reporting preferences updated.');
    }

    public function storeRecipient(StoreReportingRecipientRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanManageReports($user->role);

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

        DispatchDigestReportJob::dispatch($user->id, 'daily');

        return redirect()
            ->route('reports.index')
            ->with('success', 'Test daily digest queued.');
    }

    private function ensureCanManageReports(string $role): void
    {
        abort_unless(in_array($role, ['parent', 'admin'], true), 403);
    }
}

