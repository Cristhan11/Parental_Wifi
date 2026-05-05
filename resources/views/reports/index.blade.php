{{--
  Parent-facing Reporting & email config UI.

  Backed by: App\Http\Controllers\ReportsController, routes name prefix `reports.*`.
  Data: reporting_preferences, reporting_recipients, report_dispatch_logs (read-only history).
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">REPORTING AND EMAIL CONFIG</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>This page is where you choose which <strong>email addresses</strong> should get updates about your children’s internet activity.</li>
                    <li>Add or change addresses in the list below, then tap <strong>Save recipients</strong> once you’re finished. Use <strong>Add another email</strong> for more addresses, or <strong>Remove</strong> to take a row off the list before saving.</li>
                    <li>The <strong>Dispatch history</strong> section at the bottom is a simple log of when email for the reports were sent</li>
                    <li>Want to change how often summary emails go out, turn quick heads-up messages on or off, or set the “home clock” for those summaries? Open <strong>Advanced options</strong> below the email list.</li>
                </ul>
            </x-collapsible-instructions>

            @php
                $showReportingAdvanced = old('_reporting_section') === 'preferences';
                $useOldRecipientsBulk = old('_form') === 'recipients_bulk' && is_array(old('recipients'));
                if ($useOldRecipientsBulk) {
                    $recipientRows = collect(old('recipients'))->map(function ($r) {
                        if (! is_array($r)) {
                            return ['id' => null, 'label' => '', 'email' => '', 'is_enabled' => true];
                        }

                        return [
                            'id' => filled($r['id'] ?? null) ? (int) $r['id'] : null,
                            'label' => (string) ($r['label'] ?? ''),
                            'email' => (string) ($r['email'] ?? ''),
                            'is_enabled' => filter_var($r['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                    })->values();
                } else {
                    $recipientRows = $recipients->map(fn ($r) => [
                        'id' => $r->id,
                        'label' => (string) ($r->label ?? ''),
                        'email' => (string) $r->email,
                        'is_enabled' => (bool) $r->is_enabled,
                    ])->values();
                }
                $recipientNextIndex = $recipientRows->count();
            @endphp

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Recipients</h3>
                <p class="mb-4 text-xs text-gray-500">
                    Add or edit addresses here, then tap <strong class="font-semibold text-gray-700">Save recipients</strong>. For a full walkthrough, tap <strong class="font-semibold text-gray-700">Show instructions</strong> above.
                </p>

                @error('recipients')
                    <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('reports.recipients.bulk-save') }}">
                    @csrf
                    <input type="hidden" name="_form" value="recipients_bulk">

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-[24%] px-3 py-2 text-left font-semibold text-gray-700">Label</th>
                                    <th class="w-[40%] px-3 py-2 text-left font-semibold text-gray-700">Email</th>
                                    <th class="w-[16%] px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                                    <th class="w-[20%] px-3 py-2 text-left font-semibold text-gray-700">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="recipients-tbody">
                                @forelse ($recipientRows as $i => $row)
                                    <tr class="recipient-row border-t border-gray-200 align-top">
                                        <td class="px-3 py-3">
                                            <input type="hidden" name="recipients[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                            <label for="recipient-label-{{ $i }}" class="sr-only">Label</label>
                                            <input
                                                id="recipient-label-{{ $i }}"
                                                type="text"
                                                name="recipients[{{ $i }}][label]"
                                                value="{{ $row['label'] }}"
                                                class="w-full min-w-[8rem] max-w-xs rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                                                placeholder="Optional name"
                                                autocomplete="off"
                                            >
                                            @error('recipients.'.$i.'.label')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-3 py-3">
                                            <label for="recipient-email-{{ $i }}" class="sr-only">Email</label>
                                            <input
                                                id="recipient-email-{{ $i }}"
                                                type="email"
                                                name="recipients[{{ $i }}][email]"
                                                value="{{ $row['email'] }}"
                                                class="w-full min-w-[12rem] max-w-md rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                                                placeholder="you@example.com"
                                                autocomplete="email"
                                            >
                                            @error('recipients.'.$i.'.email')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-3 py-3">
                                            <input type="hidden" name="recipients[{{ $i }}][is_enabled]" value="0">
                                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                                <input
                                                    type="checkbox"
                                                    name="recipients[{{ $i }}][is_enabled]"
                                                    value="1"
                                                    class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-400"
                                                    @checked($row['is_enabled'])
                                                >
                                                <span>Enabled</span>
                                            </label>
                                        </td>
                                        <td class="px-3 py-3">
                                            <button
                                                type="button"
                                                class="recipient-remove-row rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="border-t border-gray-200" data-empty-hint="1">
                                        <td colspan="4" class="px-3 py-4 text-gray-500">No recipients yet. Use <span class="font-medium text-gray-700">Add another email</span>, then <span class="font-medium text-gray-700">Save recipients</span>.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-gray-200 bg-gray-50">
                                    <td colspan="4" class="px-3 py-3">
                                        <button
                                            type="button"
                                            id="recipient-add-row-btn"
                                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"
                                        >
                                            Add another email
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="rounded-md bg-yellow-400 px-4 py-2 text-sm font-semibold text-black hover:bg-yellow-500">
                            Save recipients
                        </button>
                    </div>
                </form>
            </div>

            <details class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5" @if($showReportingAdvanced) open @endif>
                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 select-none [&::-webkit-details-marker]:hidden">
                    <span class="font-medium">Advanced options</span>
                    <span class="text-xs text-gray-400">(alerts, summary emails, clock for summaries)</span>
                </summary>
                <div class="mt-5 space-y-5 border-t border-gray-100 pt-5">
                    <x-collapsible-instructions class="mb-0" innerClass="mt-3 rounded-md border border-yellow-100 bg-yellow-50 px-4 py-3 text-sm text-gray-700">
                        <p class="mb-2 font-semibold">Instructions for these options</p>
                        <ul class="list-inside list-disc space-y-2">
                            <li><strong>Digest</strong> = one email that bundles recent activity into a single read. Daily, weekly, and monthly here mean how often that bundled email is sent.</li>
                            <li><strong>Email Report options</strong>is a simple chart of what your report emails can cover: the left side is <strong>same-day notices</strong> (blocked site try, flagged site visit). The right side is <strong>how often</strong> one roundup email may be sent (daily, weekly, or monthly).</li>
                            <li><strong>Preferences</strong>is where you choose what to use: turn those same-day notices and scheduled roundups on or off, optionally skip quiet days when there is nothing new, and set your timezone.</li>
                            <li>Tap <strong>Save preferences</strong> when done. <strong>Send Test Daily Digest</strong> sends one sample daily bundle. (check spam or junk if it is missing)</li>
                        </ul>
                    </x-collapsible-instructions>

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Email Report options</h3>
                        <div class="grid grid-cols-1 gap-4 text-sm text-gray-700 md:grid-cols-2">
                            <div>
                                <p class="font-semibold">Immediate alerts</p>
                                <ul class="ml-5 list-disc">
                                    <li>Blocked website attempt</li>
                                    <li>Flagged website visit</li>
                                </ul>
                            </div>
                            <div>
                                <p class="font-semibold">Frequency of email reports</p>
                                <ul class="ml-5 list-disc">
                                    <li>Daily</li>
                                    <li>Weekly</li>
                                    <li>Monthly</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Preferences</h3>
                        <form method="POST" action="{{ route('reports.preferences.update') }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_reporting_section" value="preferences">

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:gap-8">
                                <div class="flex flex-col gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="daily_digest_enabled" value="1" {{ $preferences->daily_digest_enabled ? 'checked' : '' }}>
                                        Daily digest enabled
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="weekly_digest_enabled" value="1" {{ $preferences->weekly_digest_enabled ? 'checked' : '' }}>
                                        Weekly digest enabled
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="monthly_digest_enabled" value="1" {{ $preferences->monthly_digest_enabled ? 'checked' : '' }}>
                                        Monthly digest enabled
                                    </label>
                                </div>
                                <div class="flex flex-col gap-4">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="immediate_alerts_enabled" value="1" {{ $preferences->immediate_alerts_enabled ? 'checked' : '' }}>
                                        Immediate alerts enabled
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="skip_empty_digests" value="1" {{ $preferences->skip_empty_digests ? 'checked' : '' }}>
                                        Skip empty digest periods
                                    </label>
                                </div>
                            </div>

                            @php
                                $tzCurrent = old('timezone', $preferences->timezone);
                                $tzFlat = \App\Support\ReportingTimezoneOptions::flat();
                                $tzUnknown = $tzCurrent !== null && $tzCurrent !== '' && ! array_key_exists($tzCurrent, $tzFlat);
                            @endphp
                            <div>
                                <label for="timezone" class="mb-1 block text-sm font-semibold text-gray-700">Timezone</label>
                                <select id="timezone" name="timezone" required
                                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm md:max-w-xl">
                                    @if ($tzUnknown)
                                        <option value="{{ $tzCurrent }}" selected>{{ $tzCurrent }} (saved value — pick another if incorrect)</option>
                                    @endif
                                    @foreach ($timezoneGroups as $groupLabel => $zones)
                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach ($zones as $value => $label)
                                                <option value="{{ $value }}" @selected($tzCurrent === $value)>{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Default for new accounts: <strong class="text-gray-700">Philippines — Asia/Manila</strong> (UTC+8). Choose the zone that matches where you read reports.
                                </p>
                                @error('timezone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="rounded-md bg-yellow-400 px-4 py-2 text-sm font-semibold text-black hover:bg-yellow-500">
                                    Save Preferences
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('reports.send-test-digest') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Send Test Daily Digest
                            </button>
                        </form>
                    </div>
                </div>
            </details>

            @push('scripts')
            <script>
                (function () {
                    const tbody = document.getElementById('recipients-tbody');
                    const btn = document.getElementById('recipient-add-row-btn');
                    if (!tbody || !btn) return;

                    let nextIndex = {{ (int) $recipientNextIndex }};

                    function bindRemove(tr) {
                        const b = tr.querySelector('.recipient-remove-row');
                        if (b) {
                            b.addEventListener('click', function () {
                                tr.remove();
                                const hasDataRow = tbody.querySelector('tr.recipient-row');
                                if (!hasDataRow && !tbody.querySelector('tr[data-empty-hint="1"]')) {
                                    tbody.innerHTML = '<tr class="border-t border-gray-200" data-empty-hint="1"><td colspan="4" class="px-3 py-4 text-gray-500">No recipients yet. Use <span class="font-medium text-gray-700">Add another email</span>, then <span class="font-medium text-gray-700">Save recipients</span>.</td></tr>';
                                }
                            });
                        }
                    }

                    tbody.querySelectorAll('tr.recipient-row').forEach(bindRemove);

                    btn.addEventListener('click', function () {
                        const emptyHint = tbody.querySelector('tr[data-empty-hint="1"]');
                        if (emptyHint) emptyHint.remove();

                        const i = nextIndex++;
                        const tr = document.createElement('tr');
                        tr.className = 'recipient-row border-t border-gray-200 align-top';
                        tr.innerHTML =
                            '<td class="px-3 py-3">' +
                            '<input type="hidden" name="recipients[' + i + '][id]" value="">' +
                            '<label class="sr-only" for="recipient-label-' + i + '">Label</label>' +
                            '<input id="recipient-label-' + i + '" type="text" name="recipients[' + i + '][label]" class="w-full min-w-[8rem] max-w-xs rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="Optional name" autocomplete="off">' +
                            '</td>' +
                            '<td class="px-3 py-3">' +
                            '<label class="sr-only" for="recipient-email-' + i + '">Email</label>' +
                            '<input id="recipient-email-' + i + '" type="email" name="recipients[' + i + '][email]" class="w-full min-w-[12rem] max-w-md rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="you@example.com" autocomplete="email">' +
                            '</td>' +
                            '<td class="px-3 py-3">' +
                            '<input type="hidden" name="recipients[' + i + '][is_enabled]" value="0">' +
                            '<label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">' +
                            '<input type="checkbox" name="recipients[' + i + '][is_enabled]" value="1" checked class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-400">' +
                            '<span>Enabled</span></label></td>' +
                            '<td class="px-3 py-3"><button type="button" class="recipient-remove-row rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100">Remove</button></td>';

                        tbody.appendChild(tr);
                        bindRemove(tr);
                    });
                })();
            </script>
            @endpush

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Dispatch History (Latest 20)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">When</th>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Frequency</th>
                                <th class="px-3 py-2 text-left">Recipient</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dispatchLogs as $log)
                                <tr class="border-t border-gray-200">
                                    <td class="px-3 py-2">{{ $log->created_at?->format('M d, Y H:i:s') }}</td>
                                    <td class="px-3 py-2">{{ $log->report_type }}</td>
                                    <td class="px-3 py-2">{{ $log->frequency ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $log->recipient_email ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $log->status }}</td>
                                    <td class="px-3 py-2">{{ $log->error_message ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-gray-200">
                                    <td colspan="6" class="px-3 py-3 text-gray-500">No dispatch history yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

