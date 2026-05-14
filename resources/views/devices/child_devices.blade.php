{{-- 
    Device Management: Child Devices Stats View (Image 3)
    
    This view displays statistics for a selected device:
    - TIME USAGE graph (line graph: daily / weekly / monthly / yearly, same API rules as dashboard)
    - QUIZ SCORE list (all quiz attempts with scores)
    - WEBSITE HISTORY list (recently visited websites)
    
    Based on design reference Image 3: "CHILD DEVICES" tab with stats.
    
    Layout Structure (from Image 3):
    - Yellow header bar with left arrow icon and "CHILD DEVICES" title (with smartphone icon)
    - Child dropdown selector (filter by device)
    - Card 1: TIME USAGE (full-width line graph + range filters)
    - Card 2: ASSIGNED QUIZ & VIDEO (table of content assigned to this device)
    - Card 3: QUIZ SCORE (list of quiz scores)
    - Card 4: WEBSITE HISTORY (list of visited websites)
    
    Data Flow:
    1. DeviceController@index fetches device data and statistics
    2. Passes $devices, $device (with quizzes/videos loaded), $quizScores, $websiteHistory; chart via GET child_devices/{id}/usage-chart
    3. View displays statistics in cards matching Image 3 design
    
    Design Reference: Image 3 - "CHILD DEVICES" tab
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway (Image 3) --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                {{-- Left arrow icon (back button) --}}
                <a href="{{ route('dashboard') }}" class="shrink-0 text-black hover:opacity-75">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                {{-- Smartphone icon (for Child Devices) --}}
                <svg class="h-6 w-6 shrink-0 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <h2 class="min-w-0 text-base font-semibold leading-tight text-black sm:text-xl">
                    CHILD DEVICES
                </h2>
            </div>
            {{-- Action Buttons: Browsing History and Access Attempts --}}
            {{-- These buttons link to the detailed views with the selected device pre-filtered --}}
            @if($device)
                <div class="flex w-full min-w-0 flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-2">
                    {{-- Browsing History Button: Links to browsing logs page with device_id parameter --}}
                    <a href="{{ route('browsing-logs.index', ['device_id' => $device->id]) }}"
                       class="inline-flex w-full min-w-0 items-center justify-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base"
                       style="background-color: #3B82F6;"
                       aria-label="Browsing history for this child device">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span class="truncate sm:whitespace-nowrap"><span class="sm:hidden">History</span><span class="hidden sm:inline">Browsing History</span></span>
                    </a>
                    {{-- Access Attempts Button: Links to access attempts page with device_id parameter --}}
                    <a href="{{ route('access-attempts.index', ['device_id' => $device->id]) }}"
                       class="inline-flex w-full min-w-0 items-center justify-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base"
                       style="background-color: #EF4444;"
                       aria-label="Access attempts for this child device">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span class="truncate sm:whitespace-nowrap"><span class="sm:hidden">Attempts</span><span class="hidden sm:inline">Access Attempts</span></span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto min-w-0 px-4 sm:px-6 lg:px-8">
            <x-collapsible-instructions class="mb-6">
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>Pick a child from the menu to see their time on the internet, quiz scores, and recent websites.</li>
                    <li><strong>Browsing History</strong> opens the full visit list for that child; <strong>Access Attempts</strong> opens blocked and flagged site activity for that child.</li>
                    <li>On the chart, use <strong>Daily</strong>, <strong>Weekly</strong>, <strong>Monthly</strong>, or <strong>Yearly</strong> to change what the graph shows.</li>
                </ul>
            </x-collapsible-instructions>

            {{-- Child dropdown selector (matching Image 3) --}}
            @if($devices->count() > 0)
                <div class="mb-6 min-w-0">
                    <form method="GET" action="{{ route('child_devices.index') }}" class="rounded-lg border border-yellow-200 bg-white p-4 shadow-sm">
                        <label for="device" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700">Select Child Device</label>
                        <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                            <div class="pointer-events-none inline-flex items-center rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 sm:text-sm">
                                {{ $device?->name ?? 'Child' }}
                            </div>
                            <div class="relative w-full sm:w-auto sm:flex-none">
                                <select
                                    name="device"
                                    id="device"
                                    onchange="this.form.submit()"
                                    aria-label="Select child device"
                                    class="w-full sm:w-[280px] md:w-[300px] lg:w-[320px] min-w-0 rounded-md border-2 border-yellow-300 bg-white py-2.5 pl-3 pr-10 text-sm font-medium text-gray-900 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-200">
                                    <option value="" selected disabled>Click here to select another child</option>
                                    @foreach($devices as $d)
                                        @continue($device && $device->id === $d->id)
                                        <option value="{{ $d->id }}">
                                            {{ $d->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            @if($device)
                <div class="grid min-w-0 grid-cols-1 gap-6">
                    {{-- Card 1: Single graph card with metric dropdown --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <h3 id="childGraphTitle" class="text-lg font-semibold text-gray-900">TIME USAGE</h3>
                                    <select id="childGraphType"
                                        class="rounded-full border-2 border-gray-300 bg-white text-black px-3 py-1 text-[11px] sm:text-xs font-semibold font-montserrat focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                        <option value="usage" selected>Child Usage Time</option>
                                        <option value="bandwidth">Bandwidth Consumption</option>
                                    </select>
                                </div>
                                <div class="flex flex-col items-stretch sm:items-end gap-2">
                                    <span class="text-[11px] sm:text-xs font-semibold text-gray-600 font-montserrat">FILTER</span>
                                    <div class="flex flex-wrap gap-2 justify-start sm:justify-end">
                                        <button type="button"
                                            class="js-child-usage-chart-filter js-child-usage-chart-filter-active inline-flex items-center justify-center rounded-full border-2 border-[#FFDE15] bg-[#FFDE15] text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFC107] transition"
                                            data-child-usage-chart-range="daily">
                                            Daily
                                        </button>
                                        <button type="button"
                                            class="js-child-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                                            data-child-usage-chart-range="weekly">
                                            Weekly
                                        </button>
                                        <button type="button"
                                            class="js-child-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                                            data-child-usage-chart-range="monthly">
                                            Monthly
                                        </button>
                                        <button type="button"
                                            class="js-child-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                                            data-child-usage-chart-range="yearly">
                                            Yearly
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="childBandwidthUnitRow" class="mb-3 hidden flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[11px] sm:text-xs font-semibold text-gray-600 font-montserrat">BANDWIDTH UNIT</span>
                                <select id="childBandwidthUnit"
                                    class="rounded-full border-2 border-gray-300 bg-white text-black px-3 py-1 text-[11px] sm:text-xs font-semibold font-montserrat focus:outline-none focus:ring-2 focus:ring-yellow-300">
                                    <option value="gb" selected>Gigabytes (GB)</option>
                                    <option value="mb">Megabytes (MB)</option>
                                </select>
                            </div>
                            
                            {{-- Full-width chart (same data source as dashboard usage graph) --}}
                            <div class="relative min-h-[280px] w-full min-w-0 overflow-x-auto sm:min-h-[320px] lg:min-h-[380px]">
                                <canvas id="timeUsageChart" class="h-full min-w-[280px] w-full" role="img" aria-label="Time usage for selected child device"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Assigned quiz & video (read-only; two columns, scrollable lists) --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-semibold text-blue-900">ASSIGNED QUIZ &amp; VIDEO</h3>

                            @if($device->quizzes->isEmpty() && $device->videos->isEmpty())
                                <p class="text-sm text-gray-500">No quizzes or videos assigned to this child yet.</p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Assign content to this device in Accounts so it appears in the child portal.
                                </p>
                            @else
                                <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2 md:items-start">
                                    {{-- Quizzes column --}}
                                    <div class="min-w-0 flex flex-col">
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-700">Quizzes</p>
                                        <div class="max-h-80 min-h-0 overflow-y-auto overflow-x-auto rounded-lg border border-gray-200">
                                            @if($device->quizzes->isEmpty())
                                                <p class="p-4 text-sm text-gray-500">No quizzes assigned.</p>
                                            @else
                                                <table class="min-w-full border-collapse text-sm">
                                                    <thead class="sticky top-0 z-10 bg-gray-50">
                                                        <tr class="border-b border-gray-200">
                                                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">Title</th>
                                                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white">
                                                        @foreach($device->quizzes as $quiz)
                                                            <tr class="border-b border-gray-100 last:border-b-0">
                                                                <td class="min-w-0 px-3 py-2.5 text-gray-800">{{ $quiz->title }}</td>
                                                                <td class="whitespace-nowrap px-3 py-2.5">
                                                                    @if($quiz->is_active)
                                                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                                                    @else
                                                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">Inactive</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </div>
                                    {{-- Videos column --}}
                                    <div class="min-w-0 flex flex-col">
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-700">Videos</p>
                                        <div class="max-h-80 min-h-0 overflow-y-auto overflow-x-auto rounded-lg border border-gray-200">
                                            @if($device->videos->isEmpty())
                                                <p class="p-4 text-sm text-gray-500">No videos assigned.</p>
                                            @else
                                                <table class="min-w-full border-collapse text-sm">
                                                    <thead class="sticky top-0 z-10 bg-gray-50">
                                                        <tr class="border-b border-gray-200">
                                                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">Title</th>
                                                            <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white">
                                                        @foreach($device->videos as $video)
                                                            <tr class="border-b border-gray-100 last:border-b-0">
                                                                <td class="min-w-0 px-3 py-2.5 text-gray-800">{{ $video->title }}</td>
                                                                <td class="whitespace-nowrap px-3 py-2.5">
                                                                    @if($video->is_active)
                                                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                                                    @else
                                                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">Inactive</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Card: QUIZ SCORE (matching Image 3) --}}
                    <div class="bg-gray-100 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">QUIZ SCORE</h3>
                            
                            @if(count($quizScores) > 0)
                                <div class="space-y-2">
                                    @foreach($quizScores as $quizScore)
                                        <div class="flex min-w-0 flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between rounded bg-white">
                                            <span class="min-w-0 text-sm font-medium text-gray-900">
                                                {{ $quizScore['quiz_title'] ?? 'Quiz' }}: {{ $quizScore['correct'] }}/{{ $quizScore['total'] }}
                                            </span>
                                            @if($quizScore['passed'])
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">Passed</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #EF4444; color: white;">Failed</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No quiz attempts yet.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Card: WEBSITE HISTORY (matching Image 3) --}}
                    {{-- This section displays recent browsing logs for the selected device --}}
                    {{-- Browsing logs are automatically created by the ParseNetworkLogs background job --}}
                    <div class="bg-gray-100 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="mb-4 flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">WEBSITE HISTORY</h3>
                                {{-- View All Link: Links to full browsing logs page with device pre-filtered --}}
                                @if($websiteHistory->count() > 0)
                                    <a href="{{ route('browsing-logs.index', ['device_id' => $device->id]) }}"
                                       class="shrink-0 text-sm text-blue-600 hover:underline sm:text-right">
                                        View All
                                    </a>
                                @endif
                            </div>
                            
                            {{-- Display recent browsing logs (limit 10-15 from controller) --}}
                            @if($websiteHistory->count() > 0)
                                <div class="space-y-2">
                                    @foreach($websiteHistory as $log)
                                        <div class="min-w-0 rounded bg-white p-3">
                                            {{-- Domain and URL: Shows which website was visited --}}
                                            <div class="flex min-w-0 flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-sm font-medium text-gray-900">{{ $log->domain }}</span>
                                                    {{-- URL: Truncated if too long, shows full URL on hover --}}
                                                    <p class="mt-1 break-words text-xs text-gray-500" title="{{ $log->url }}">
                                                        {{ Str::limit($log->url, 60) }}
                                                    </p>
                                                </div>
                                                {{-- Visited At: Timestamp showing when the site was visited --}}
                                                <span class="shrink-0 text-xs text-gray-400 sm:ml-2 sm:text-right">
                                                    {{ $log->visited_at->format('M d, H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- Empty State: No browsing logs found --}}
                                <p class="text-sm text-gray-500">No website history yet.</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Browsing logs are automatically created when devices visit websites. 
                                    If you don't see any logs, make sure the ParseNetworkLogs job is running.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                {{-- Empty state: no device selected or no devices --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">No device selected. Please select a device from the dropdown above.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Chart.js + usage-chart fetch (same ranges/units as dashboard graph) --}}
    @push('scripts')
    @if($device)
    <script>
        (function() {
            'use strict';

            const chartUrlBase = @json(route('child_devices.usage-chart', $device));
            const bandwidthChartUrlBase = @json(route('child_devices.bandwidth-chart', $device));

            window.childTimeUsageChartInstance = window.childTimeUsageChartInstance ?? null;
            window.currentChildUsageChartRange = window.currentChildUsageChartRange ?? 'daily';
            window.currentChildGraphType = window.currentChildGraphType ?? 'usage';
            window.currentChildBandwidthUnit = window.currentChildBandwidthUnit ?? 'gb';
            window.__childUsageChartRefreshImpl = null;

            const CHILD_CHART_LS = 'parental_wifi.child_devices.chart.';

            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('timeUsageChart');
                if (!ctx || typeof Chart === 'undefined') return;

                const filterButtons = document.querySelectorAll('[data-child-usage-chart-range]');
                const graphTypeSelect = document.getElementById('childGraphType');
                const graphTitle = document.getElementById('childGraphTitle');
                const bandwidthUnitRow = document.getElementById('childBandwidthUnitRow');
                const bandwidthUnitSelect = document.getElementById('childBandwidthUnit');

                const readChartPref = (key, fallback) => {
                    try {
                        const v = localStorage.getItem(CHILD_CHART_LS + key);
                        return v !== null && v !== '' ? v : fallback;
                    } catch (e) {
                        return fallback;
                    }
                };
                const writeChartPref = (key, value) => {
                    try {
                        localStorage.setItem(CHILD_CHART_LS + key, value);
                    } catch (e) { /* ignore */ }
                };

                const syncChildBandwidthUnitRow = () => {
                    const show = window.currentChildGraphType === 'bandwidth';
                    if (bandwidthUnitRow) {
                        bandwidthUnitRow.classList.toggle('hidden', !show);
                    }
                };
                const setActiveFilter = (range) => {
                    window.currentChildUsageChartRange = range;
                    filterButtons.forEach((btn) => {
                        const isActive = btn.dataset.childUsageChartRange === range;
                        btn.classList.toggle('js-child-usage-chart-filter-active', isActive);
                        if (isActive) {
                            btn.classList.add('border-[#FFDE15]', 'bg-[#FFDE15]');
                            btn.classList.remove('border-gray-300', 'bg-white');
                        } else {
                            btn.classList.remove('border-[#FFDE15]', 'bg-[#FFDE15]');
                            btn.classList.add('border-gray-300', 'bg-white');
                        }
                    });
                };

                const savedRange = readChartPref('range', 'daily');
                const savedGraphType = readChartPref('graph_type', 'usage');
                const savedBandwidthUnit = readChartPref('bandwidth_unit', 'gb');

                setActiveFilter(savedRange);
                window.currentChildUsageChartRange = savedRange;

                if (graphTypeSelect) {
                    graphTypeSelect.value = ['usage', 'bandwidth'].includes(savedGraphType) ? savedGraphType : 'usage';
                    window.currentChildGraphType = graphTypeSelect.value;
                }
                if (bandwidthUnitSelect) {
                    bandwidthUnitSelect.value = ['gb', 'mb'].includes(savedBandwidthUnit) ? savedBandwidthUnit : 'gb';
                    window.currentChildBandwidthUnit = bandwidthUnitSelect.value;
                }

                const updateGraphTitle = () => {
                    if (!graphTitle) return;
                    graphTitle.textContent = window.currentChildGraphType === 'bandwidth'
                        ? 'BANDWIDTH CONSUMPTION'
                        : 'TIME USAGE';
                    syncChildBandwidthUnitRow();
                };
                updateGraphTitle();

                const makeBorderColor = (index) => {
                    const hue = (index * 55) % 360;
                    return `hsl(${hue} 70% 35%)`;
                };

                const destroyChartIfNeeded = () => {
                    if (window.childTimeUsageChartInstance) {
                        window.childTimeUsageChartInstance.destroy();
                        window.childTimeUsageChartInstance = null;
                    }
                };

                const MB_DAILY_BANDWIDTH_CAPS = [10, 20, 50, 100, 300, 500, 1000, 1500, 2000, 2500];
                const GB_DAILY_BANDWIDTH_CAPS = [0.5, 1, 1.5, 2, 2.5, 3, 4, 5, 6, 7, 8, 9, 10];

                const pickDailyMbBandwidthCap = (peak) => {
                    const padded = peak > 0 ? peak * 1.12 : 0;
                    const need = Math.max(10, Math.ceil(padded));
                    for (const t of MB_DAILY_BANDWIDTH_CAPS) {
                        if (t >= need) {
                            return t;
                        }
                    }
                    return MB_DAILY_BANDWIDTH_CAPS[MB_DAILY_BANDWIDTH_CAPS.length - 1];
                };

                const pickDailyGbBandwidthCap = (peak) => {
                    const need = peak > 0 ? Math.max(0.5, peak * 1.12) : 0.5;
                    for (const t of GB_DAILY_BANDWIDTH_CAPS) {
                        if (t + 1e-9 >= need) {
                            return t;
                        }
                    }
                    return 10;
                };

                const buildDatasets = (series, isBandwidth, clampMax = null) => {
                    return series.map((item, idx) => {
                        const borderColor = makeBorderColor(idx);
                        return {
                            label: item.device_name,
                            data: Array.isArray(item.values)
                                ? item.values.map((v) => (clampMax !== null ? Math.min(Number(v), clampMax) : Number(v)))
                                : [],
                            borderColor,
                            backgroundColor: isBandwidth ? 'rgba(34, 197, 94, 0.10)' : 'rgba(255, 222, 21, 0.10)',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: isBandwidth ? '#22C55E' : '#FFDE15',
                            pointBorderColor: borderColor,
                            pointBorderWidth: 2
                        };
                    });
                };

                const renderChart = (payload) => {
                    const labels = payload.labels ?? [];
                    const series = payload.series ?? [];
                    const isBandwidth = window.currentChildGraphType === 'bandwidth';

                    destroyChartIfNeeded();

                    const allValues = series.flatMap((s) => Array.isArray(s.values) ? s.values : []);
                    const maxVal = Math.max(...allValues, 0);
                    const bwUnit = isBandwidth && (payload.unit === 'mb' || payload.unit === 'gb') ? payload.unit : 'gb';
                    const bandwidthDefaultMaxByRange = (() => {
                        if (bwUnit === 'mb') {
                            if (payload.range === 'daily') return 2500;
                            if (payload.range === 'weekly') return 15000;
                            if (payload.range === 'monthly') return 63000;
                            if (payload.range === 'yearly') return 250000;
                            return 15000;
                        }
                        if (payload.range === 'daily') return 10;
                        if (payload.range === 'weekly') return 15;
                        if (payload.range === 'monthly') return 63;
                        if (payload.range === 'yearly') return 250;
                        return 15;
                    })();
                    const suggestedMax = maxVal > 0
                        ? maxVal * 1.2
                        : (isBandwidth ? bandwidthDefaultMaxByRange : 10);
                    const dailyFixedMax = !isBandwidth && payload.range === 'daily' ? 60 : null;

                    // Per-bucket caps (same as dashboard): match max usable time in one bucket, not whole chart.
                    const maxByRangeInChartUnit = (() => {
                        if (isBandwidth) return null;
                        if (payload.range === 'daily') return 60;
                        if (payload.range === 'weekly') return 24;
                        if (payload.range === 'monthly') return 168;
                        if (payload.range === 'yearly') return 31 * 24;
                        return null;
                    })();

                    const hardCap = isBandwidth
                        ? bandwidthDefaultMaxByRange
                        : (dailyFixedMax !== null ? dailyFixedMax : maxByRangeInChartUnit);
                    const hardCapApplies = hardCap !== null && maxVal > hardCap;

                    const showLegend = series.length > 0 && series.length <= 6;
                    const datasets = buildDatasets(series, isBandwidth, hardCapApplies ? hardCap : null);

                    const useDailyMbBandwidthTicks = isBandwidth && payload.range === 'daily' && bwUnit === 'mb';
                    const useDailyGbBandwidthTicks = isBandwidth && payload.range === 'daily' && bwUnit === 'gb';
                    const peakForAxis = hardCapApplies && hardCap !== null ? Math.min(maxVal, hardCap) : maxVal;

                    const dailyMbBandwidthCap = useDailyMbBandwidthTicks ? pickDailyMbBandwidthCap(peakForAxis) : null;
                    const dailyGbBandwidthCap = useDailyGbBandwidthTicks ? pickDailyGbBandwidthCap(peakForAxis) : null;

                    const yExtent = dailyFixedMax !== null
                        ? { max: dailyFixedMax }
                        : (maxByRangeInChartUnit !== null
                            ? { max: maxByRangeInChartUnit }
                            : (useDailyMbBandwidthTicks && dailyMbBandwidthCap !== null
                                ? { max: dailyMbBandwidthCap }
                                : (useDailyGbBandwidthTicks && dailyGbBandwidthCap !== null
                                    ? { max: dailyGbBandwidthCap }
                                    : (isBandwidth && hardCapApplies ? { max: hardCap } : { suggestedMax }))));

                    const afterBuildTicksDailyMbBandwidth = (scale) => {
                        const cap = Number(scale.max);
                        let step = 2;
                        if (cap > 10) {
                            step = 5;
                        }
                        if (cap > 20) {
                            step = 10;
                        }
                        if (cap > 50) {
                            step = 20;
                        }
                        if (cap > 100) {
                            step = 50;
                        }
                        if (cap > 300) {
                            step = 100;
                        }
                        if (cap > 500) {
                            step = 250;
                        }
                        const values = [];
                        const n = Math.ceil(cap / step - 1e-9);
                        for (let i = 0; i <= n; i++) {
                            const t = Math.min(cap, Math.round(i * step * 100) / 100);
                            values.push(t);
                        }
                        if (values[values.length - 1] !== cap) {
                            values.push(cap);
                        }
                        scale.ticks = values.map((value) => ({ value }));
                    };

                    const afterBuildTicksDailyGbBandwidth = (scale) => {
                        const cap = Number(scale.max);
                        let step = 0.1;
                        if (cap > 1) {
                            step = 0.25;
                        }
                        if (cap > 2) {
                            step = 0.5;
                        }
                        if (cap > 5) {
                            step = 1;
                        }
                        const values = [];
                        const n = Math.round(cap / step);
                        for (let i = 0; i <= n; i++) {
                            const raw = i * step;
                            const t = Math.min(cap, Math.round(raw * 1000) / 1000);
                            values.push(t);
                        }
                        if (values.length === 0 || Math.abs(values[values.length - 1] - cap) > 1e-6) {
                            values.push(cap);
                        }
                        scale.ticks = values.map((value) => ({ value }));
                    };

                    const yScaleConfig = {
                        beginAtZero: true,
                        ...yExtent,
                        title: {
                            display: true,
                            text: isBandwidth
                                ? (bwUnit === 'mb' ? 'Bandwidth (MB)' : 'Bandwidth (GB)')
                                : (payload.unit === 'hours' ? 'Time Spent (hours)' : 'Time Spent (minutes)'),
                            font: { family: 'Montserrat Variable', size: 12, weight: 'bold' },
                            color: '#000000',
                            padding: { top: 6, bottom: 6 }
                        },
                        ticks: {
                            font: { size: 12, weight: 'bold', family: 'Montserrat Variable' },
                            ...(dailyFixedMax !== null && !useDailyMbBandwidthTicks && !useDailyGbBandwidthTicks ? { stepSize: 10 } : {}),
                            callback: (value) => {
                                const v = Number(value);
                                if (hardCapApplies && hardCap !== null && v === hardCap) {
                                    return '>' + hardCap;
                                }
                                if (useDailyMbBandwidthTicks) {
                                    return Number.isInteger(v) ? String(v) : String(Math.round(v));
                                }
                                if (useDailyGbBandwidthTicks) {
                                    const r = Math.round(v * 1000) / 1000;
                                    if (Math.abs(r - Math.round(r)) < 1e-5) {
                                        return String(Math.round(r));
                                    }
                                    return String(r);
                                }
                                return value;
                            },
                            color: '#000000',
                            padding: 8
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.1)', lineWidth: 1.3 }
                    };
                    if (useDailyMbBandwidthTicks) {
                        yScaleConfig.afterBuildTicks = afterBuildTicksDailyMbBandwidth;
                    } else if (useDailyGbBandwidthTicks) {
                        yScaleConfig.afterBuildTicks = afterBuildTicksDailyGbBandwidth;
                    }

                    window.childTimeUsageChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: { labels, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: showLegend,
                                    position: 'bottom',
                                    labels: {
                                        font: { family: 'Montserrat Variable', size: 11, weight: 'bold' },
                                        boxWidth: 10
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                    titleColor: '#fff',
                                    bodyColor: '#fff',
                                    borderColor: isBandwidth ? '#22C55E' : '#FFDE15',
                                    borderWidth: 2,
                                    padding: 12,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: (context) => {
                                            const label = context.dataset?.label ? context.dataset.label + ': ' : '';
                                            if (isBandwidth) {
                                                const u = bwUnit === 'mb' ? ' MB' : ' GB';
                                                return label + context.parsed.y + u;
                                            }
                                            const unit = payload.unit === 'hours' ? ' hr' : ' min';
                                            return label + context.parsed.y + unit;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: yScaleConfig,
                                x: {
                                    ticks: {
                                        font: { size: 11, weight: 'bold', family: 'Montserrat Variable' },
                                        color: '#000000',
                                        padding: 6
                                    },
                                    grid: { display: false }
                                }
                            },
                            interaction: { intersect: false, mode: 'index' },
                            animation: { duration: 600, easing: 'easeInOutQuart' }
                        }
                    });
                };

                const fetchChildChart = async (range) => {
                    const base = window.currentChildGraphType === 'bandwidth' ? bandwidthChartUrlBase : chartUrlBase;
                    const sep = base.includes('?') ? '&' : '?';
                    let url = base + sep + 'range=' + encodeURIComponent(range);
                    if (window.currentChildGraphType === 'bandwidth') {
                        const u = window.currentChildBandwidthUnit || 'gb';
                        url += '&display_unit=' + encodeURIComponent(u);
                    }
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) {
                        throw new Error('Child graph request failed: ' + res.status);
                    }
                    return await res.json();
                };

                let refreshInFlight = false;
                window.__childUsageChartRefreshImpl = async (reason) => {
                    if (refreshInFlight) return;
                    refreshInFlight = true;
                    try {
                        const range = window.currentChildUsageChartRange || 'daily';
                        const payload = await fetchChildChart(range);
                        renderChart(payload);
                    } catch (e) {
                        console.error('Failed to refresh child graph', reason, e);
                    } finally {
                        refreshInFlight = false;
                    }
                };

                filterButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const range = btn.dataset.childUsageChartRange;
                        setActiveFilter(range);
                        writeChartPref('range', range);
                        window.__childUsageChartRefreshImpl('filter-change');
                    });
                });
                if (graphTypeSelect) {
                    graphTypeSelect.addEventListener('change', () => {
                        window.currentChildGraphType = graphTypeSelect.value || 'usage';
                        writeChartPref('graph_type', window.currentChildGraphType);
                        updateGraphTitle();
                        window.__childUsageChartRefreshImpl('graph-type-change');
                    });
                }

                if (bandwidthUnitSelect) {
                    bandwidthUnitSelect.addEventListener('change', () => {
                        window.currentChildBandwidthUnit = bandwidthUnitSelect.value || 'gb';
                        writeChartPref('bandwidth_unit', window.currentChildBandwidthUnit);
                        if (window.currentChildGraphType === 'bandwidth') {
                            window.__childUsageChartRefreshImpl('bandwidth-unit-change');
                        }
                    });
                }

                window.__childUsageChartRefreshImpl('initial-load');

                setInterval(() => {
                    if (window.currentChildUsageChartRange !== 'daily') return;
                    if (document.visibilityState !== 'visible') return;
                    window.__childUsageChartRefreshImpl('daily-timer');
                }, 60000);

                const userIdMeta = document.querySelector('meta[name="auth-user-id"]');
                const userId = userIdMeta ? userIdMeta.getAttribute('content') : '';
                if (typeof window.Echo !== 'undefined' && userId) {
                    window.Echo.private('user.' + userId)
                        .listen('.device.connected', () => {
                            if (typeof window.__childUsageChartRefreshImpl === 'function') {
                                window.__childUsageChartRefreshImpl('device.connected');
                            }
                        })
                        .listen('.device.disconnected', () => {
                            if (typeof window.__childUsageChartRefreshImpl === 'function') {
                                window.__childUsageChartRefreshImpl('device.disconnected');
                            }
                        })
                        .listen('.time.expired', () => {
                            if (typeof window.__childUsageChartRefreshImpl === 'function') {
                                window.__childUsageChartRefreshImpl('time.expired');
                            }
                        })
                        .listen('.time.granted', () => {
                            if (typeof window.__childUsageChartRefreshImpl === 'function') {
                                window.__childUsageChartRefreshImpl('time.granted');
                            }
                        });
                }
            });

            window.addEventListener('beforeunload', function() {
                if (window.childTimeUsageChartInstance) {
                    window.childTimeUsageChartInstance.destroy();
                    window.childTimeUsageChartInstance = null;
                }
            });
        })();
    </script>
    @endif
    @endpush
</x-app-layout>

