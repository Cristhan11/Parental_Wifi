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

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Locked Report Scope</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold">Immediate alerts</p>
                        <ul class="list-disc ml-5">
                            <li>Blocked website attempt</li>
                            <li>Flagged website visit</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold">Digest reports</p>
                        <ul class="list-disc ml-5">
                            <li>Daily</li>
                            <li>Weekly</li>
                            <li>Monthly</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Preferences</h3>
                <form method="POST" action="{{ route('reports.preferences.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="immediate_alerts_enabled" value="1" {{ $preferences->immediate_alerts_enabled ? 'checked' : '' }}>
                            Immediate alerts enabled
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="skip_empty_digests" value="1" {{ $preferences->skip_empty_digests ? 'checked' : '' }}>
                            Skip empty digest periods
                        </label>
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

                    @php
                        $tzCurrent = old('timezone', $preferences->timezone);
                        $tzFlat = \App\Support\ReportingTimezoneOptions::flat();
                        $tzUnknown = $tzCurrent !== null && $tzCurrent !== '' && ! array_key_exists($tzCurrent, $tzFlat);
                    @endphp
                    <div>
                        <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-1">Timezone</label>
                        <select id="timezone" name="timezone" required
                                class="w-full md:max-w-xl px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
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
                        <p class="text-xs text-gray-500 mt-1">
                            Default for new accounts: <strong class="text-gray-700">Philippines — Asia/Manila</strong> (UTC+8). Choose the zone that matches where you read reports.
                        </p>
                        @error('timezone')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-yellow-400 text-black text-sm font-semibold hover:bg-yellow-500">
                            Save Preferences
                        </button>
                    </div>
                </form>
                <form method="POST" action="{{ route('reports.send-test-digest') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                        Send Test Daily Digest
                    </button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recipients</h3>

                <form method="POST" action="{{ route('reports.recipients.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end mb-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Label (optional)</label>
                        <input type="text" name="label" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="Parent email">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" required>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="is_enabled" value="1" checked>
                            Enabled
                        </label>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-green-600 text-white text-sm font-semibold hover:bg-green-700">
                        Add Recipient
                    </button>
                </form>

                <p class="text-xs text-gray-500 mb-2">
                    Edit <span class="font-semibold text-gray-700">Label</span>, <span class="font-semibold text-gray-700">Email</span>, and <span class="font-semibold text-gray-700">Status</span> in their columns, then use <span class="font-semibold text-gray-700">Save</span> or <span class="font-semibold text-gray-700">Delete</span> in Actions.
                </p>
                {{-- Forms live outside the table so tbody only contains <tr> (valid HTML). Inputs use form="id" to associate. --}}
                @foreach ($recipients as $recipient)
                    <form id="recipient-update-{{ $recipient->id }}" method="POST" action="{{ route('reports.recipients.update', $recipient) }}" class="hidden" aria-hidden="true">
                        @csrf
                        @method('PUT')
                    </form>
                @endforeach
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-[22%]">Label</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-[38%]">Email</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-[18%]">Status</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-[22%]">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recipients as $recipient)
                                <tr class="border-t border-gray-200 align-top">
                                    <td class="px-3 py-3">
                                        <label for="recipient-label-{{ $recipient->id }}" class="sr-only">Label for {{ $recipient->email }}</label>
                                        <input
                                            id="recipient-label-{{ $recipient->id }}"
                                            type="text"
                                            name="label"
                                            form="recipient-update-{{ $recipient->id }}"
                                            value="{{ old('label', $recipient->label) }}"
                                            class="w-full min-w-[8rem] max-w-xs px-2 py-1.5 border border-gray-300 rounded-md text-sm"
                                            placeholder="Optional name"
                                            autocomplete="off"
                                        >
                                    </td>
                                    <td class="px-3 py-3">
                                        <label for="recipient-email-{{ $recipient->id }}" class="sr-only">Email</label>
                                        <input
                                            id="recipient-email-{{ $recipient->id }}"
                                            type="email"
                                            name="email"
                                            form="recipient-update-{{ $recipient->id }}"
                                            value="{{ old('email', $recipient->email) }}"
                                            required
                                            class="w-full min-w-[12rem] max-w-md px-2 py-1.5 border border-gray-300 rounded-md text-sm"
                                            autocomplete="email"
                                        >
                                    </td>
                                    <td class="px-3 py-3">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="is_enabled"
                                                form="recipient-update-{{ $recipient->id }}"
                                                value="1"
                                                {{ old('is_enabled', $recipient->is_enabled) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-400"
                                            >
                                            <span>Enabled</span>
                                        </label>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="submit"
                                                form="recipient-update-{{ $recipient->id }}"
                                                class="px-3 py-1.5 rounded-md bg-yellow-400 text-black text-xs font-semibold hover:bg-yellow-500 whitespace-nowrap"
                                            >
                                                Save
                                            </button>
                                            <form method="POST" action="{{ route('reports.recipients.destroy', $recipient) }}" class="inline" onsubmit="return confirm('Remove this recipient?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 whitespace-nowrap">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr class="border-t border-gray-200">
                                    <td colspan="4" class="px-3 py-3 text-gray-500">No recipients configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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

