{{-- 
    Logs module view (finals scope: frontend-logs-and-filtering).
    Why this page exists:
    - present two distinct log streams without mixing semantics
    - keep one predictable filter/export surface for investigation workflows
    Connection:
    - data is orchestrated by LogsController and normalized before rendering
    - same underlying activity tables (e.g. AccessAttempt, BrowsingLog) feed digest rollups in ReportingDigestService for email reports
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
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">LOG STREAMS</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-4">
                {{-- 
                    Stream selector:
                    Why: users should not audit child events inside admin-change noise.
                    Connection: keeps the two contract streams explicit (`child_activity`, `parent_admin_changes`).
                --}}
                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex gap-2">
                        <a href="{{ route('logs.index', array_merge(request()->query(), ['stream' => 'child_activity'])) }}"
                           class="px-4 py-2 rounded-md text-sm font-semibold {{ $stream === 'child_activity' ? 'bg-yellow-400 text-black' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            Child Activity ({{ $streamCounts['child_activity'] }})
                        </a>
                        <a href="{{ route('logs.index', array_merge(request()->query(), ['stream' => 'parent_admin_changes'])) }}"
                           class="px-4 py-2 rounded-md text-sm font-semibold {{ $stream === 'parent_admin_changes' ? 'bg-yellow-400 text-black' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            Parent/Admin Changes ({{ $streamCounts['parent_admin_changes'] }})
                        </a>
                    </div>

                    <a href="{{ route('logs.export.excel', request()->query()) }}"
                       class="px-4 py-2 rounded-md bg-green-600 text-white text-sm font-semibold hover:bg-green-700">
                        Export Excel
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-4">
                {{--
                    Shared filter surface used by both streams.
                    Why: one filtering model reduces cognitive load and supports reproducible exports.
                    Connection: these fields map directly to LogsController::applySharedFilters().
                --}}
                <form method="GET" action="{{ route('logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <input type="hidden" name="stream" value="{{ $stream }}">

                    <div>
                        <label for="from" class="block text-xs font-semibold text-gray-700 mb-1">From</label>
                        <input id="from" type="datetime-local" name="from" value="{{ $filters['from'] }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label for="to" class="block text-xs font-semibold text-gray-700 mb-1">To</label>
                        <input id="to" type="datetime-local" name="to" value="{{ $filters['to'] }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label for="device_id" class="block text-xs font-semibold text-gray-700 mb-1">Device</label>
                        <select id="device_id" name="device_id" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ (string) $filters['device_id'] === (string) $device->id ? 'selected' : '' }}>
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="role" class="block text-xs font-semibold text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All roles</option>
                            @foreach(['admin', 'parent', 'child-device', 'guest'] as $role)
                                <option value="{{ $role }}" {{ $filters['role'] === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="event_type" class="block text-xs font-semibold text-gray-700 mb-1">Event Type</label>
                        <select id="event_type" name="event_type" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All event types</option>
                            @foreach(['connection', 'violation', 'policy-change', 'access-control', 'time-granted', 'configuration'] as $type)
                                <option value="{{ $type }}" {{ $filters['event_type'] === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="">All statuses</option>
                            @foreach(['info', 'warning', 'critical', 'success', 'failed'] as $level)
                                <option value="{{ $level }}" {{ $filters['status'] === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="keyword" class="block text-xs font-semibold text-gray-700 mb-1">Keyword</label>
                        <input id="keyword" type="text" name="keyword" value="{{ $filters['keyword'] }}" placeholder="device, domain, actor..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    </div>

                    <div>
                        <label for="sort" class="block text-xs font-semibold text-gray-700 mb-1">Sort</label>
                        <select id="sort" name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="desc" {{ $filters['sort'] === 'desc' ? 'selected' : '' }}>Newest first</option>
                            <option value="asc" {{ $filters['sort'] === 'asc' ? 'selected' : '' }}>Oldest first</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-semibold text-sm">
                            Apply
                        </button>
                        <a href="{{ route('logs.index', ['stream' => $stream]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    {{--
                        Normalized table:
                        Why: child and parent/admin rows come from different models.
                        Connection: controller maps all rows into one shape so blade stays simple.
                    --}}
                    @if($logs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Timestamp</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Role</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Device</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Target</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase">Summary</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($logs as $entry)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $entry['timestamp']->format('M d, Y H:i:s') }}</td>
                                            <td class="px-4 py-3 text-gray-900">{{ $entry['event_type'] }}</td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $badge = match($entry['status']) {
                                                        'critical' => 'bg-red-100 text-red-700',
                                                        'warning' => 'bg-yellow-100 text-yellow-700',
                                                        'success' => 'bg-green-100 text-green-700',
                                                        default => 'bg-blue-100 text-blue-700'
                                                    };
                                                @endphp
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">{{ $entry['status'] }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700">{{ $entry['role'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-900">{{ $entry['device_name'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-gray-700 font-mono">{{ \Illuminate\Support\Str::limit($entry['target'] ?? '-', 30) }}</td>
                                            <td class="px-4 py-3 text-gray-900">{{ $entry['summary'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $logs->links() }}
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            No logs found for the selected filters.
                        </div>
                    @endif
                </div>
            </div>

            @if($stream === 'child_activity')
                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-4">
                    {{--
                        Live panel:
                        Why: realtime visibility is required for child activity monitoring.
                        Connection: complements persisted history table; it does not replace API/database records.
                    --}}
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-bold text-gray-800">Live Child Activity Events</h3>
                        <span class="text-xs text-gray-500">WebSocket</span>
                    </div>
                    <ul id="childLiveEventsList" class="space-y-2 text-sm max-h-64 overflow-y-auto">
                        <li id="childLiveEventsEmpty" class="text-gray-500">Waiting for live events...</li>
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if($stream === 'child_activity')
        @push('scripts')
            <script>
                (function () {
                    'use strict';

                    document.addEventListener('DOMContentLoaded', function () {
                        const userId = document.querySelector('meta[name="auth-user-id"]')?.getAttribute('content');
                        const list = document.getElementById('childLiveEventsList');
                        const empty = document.getElementById('childLiveEventsEmpty');

                        if (!userId || !list || typeof window.Echo === 'undefined') {
                            return;
                        }

                        // Keep client rendering simple and prepend newest events for incident-first reading.
                        const addEvent = (message, tone = 'info') => {
                            if (empty) {
                                empty.remove();
                            }

                            const colors = {
                                info: 'border-blue-200 bg-blue-50 text-blue-900',
                                warning: 'border-yellow-200 bg-yellow-50 text-yellow-900',
                                danger: 'border-red-200 bg-red-50 text-red-900',
                                success: 'border-green-200 bg-green-50 text-green-900',
                            };

                            const item = document.createElement('li');
                            item.className = `rounded-md border px-3 py-2 ${colors[tone] || colors.info}`;
                            item.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
                            list.prepend(item);
                        };

                        // Event aliases match backend broadcastAs names from existing websocket events.
                        // This links real-time signals to the same child-activity context shown in the table.
                        window.Echo.private(`user.${userId}`)
                            .listen('.device.connected', (event) => addEvent(`${event.device_name} connected`, 'success'))
                            .listen('.device.disconnected', (event) => addEvent(`${event.device_name} disconnected`, 'warning'))
                            .listen('.time.expired', (event) => addEvent(`Time expired for ${event.device_name}`, 'danger'))
                            .listen('.time.granted', (event) => addEvent(`${event.minutes_granted} minute(s) granted to ${event.device_name}`, 'success'))
                            .listen('.website.blocked_accessed', (event) => addEvent(`${event.device_name} attempted blocked site: ${event.domain || event.url}`, 'danger'))
                            .listen('.website.flagged_visited', (event) => addEvent(`${event.device_name} visited flagged site: ${event.domain || event.url}`, 'warning'));
                    });
                })();
            </script>
        @endpush
    @endif
</x-app-layout>

