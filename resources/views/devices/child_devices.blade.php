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
    - Card 2: QUIZ SCORE (list of quiz scores)
    - Card 3: WEBSITE HISTORY (list of visited websites)
    
    Data Flow:
    1. DeviceController@index fetches device data and statistics
    2. Passes $devices, $device, $quizScores, $websiteHistory; chart data loads via GET child_devices/{id}/usage-chart
    3. View displays statistics in cards matching Image 3 design
    
    Design Reference: Image 3 - "CHILD DEVICES" tab
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway (Image 3) --}}
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                {{-- Left arrow icon (back button) --}}
                <a href="{{ route('dashboard') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                {{-- Smartphone icon (for Child Devices) --}}
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    CHILD DEVICES
                </h2>
            </div>
            {{-- Action Buttons: Browsing History and Access Attempts --}}
            {{-- These buttons link to the detailed views with the selected device pre-filtered --}}
            @if($device)
                <div class="flex space-x-2">
                    {{-- Browsing History Button: Links to browsing logs page with device_id parameter --}}
                    <a href="{{ route('browsing-logs.index', ['device_id' => $device->id]) }}" 
                       class="px-4 py-2 rounded text-white font-medium hover:opacity-90 flex items-center space-x-1" 
                       style="background-color: #3B82F6;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>Browsing History</span>
                    </a>
                    {{-- Access Attempts Button: Links to access attempts page with device_id parameter --}}
                    <a href="{{ route('access-attempts.index', ['device_id' => $device->id]) }}" 
                       class="px-4 py-2 rounded text-white font-medium hover:opacity-90 flex items-center space-x-1" 
                       style="background-color: #EF4444;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>Access Attempts</span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Child dropdown selector (matching Image 3) --}}
            @if($devices->count() > 0)
                <div class="mb-6">
                    <form method="GET" action="{{ route('child_devices.index') }}" class="flex items-center space-x-3">
                        <label for="device" class="text-sm font-medium text-gray-700">CHILD:</label>
                        <select name="device" id="device" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($devices as $d)
                                <option value="{{ $d->id }}" {{ $device && $device->id === $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            @if($device)
                <div class="grid grid-cols-1 gap-6">
                    {{-- Card 1: TIME USAGE (matching Image 3) --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">TIME USAGE</h3>
                                <div class="flex flex-col items-stretch sm:items-end gap-2">
                                    <span class="text-[11px] sm:text-xs font-semibold text-gray-600 font-montserrat">FILTER</span>
                                    <div class="flex flex-wrap gap-2 justify-start sm:justify-end">
                                        <button type="button"
                                            class="js-child-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
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
                                            class="js-child-usage-chart-filter js-child-usage-chart-filter-active inline-flex items-center justify-center rounded-full border-2 border-[#FFDE15] bg-[#FFDE15] text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFC107] transition"
                                            data-child-usage-chart-range="yearly">
                                            Yearly
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Full-width chart (same data source as dashboard usage graph) --}}
                            <div class="relative w-full min-h-[280px] sm:min-h-[320px] lg:min-h-[380px]">
                                <canvas id="timeUsageChart" class="w-full h-full" role="img" aria-label="Time usage for selected child device"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: QUIZ SCORE (matching Image 3) --}}
                    <div class="bg-gray-100 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">QUIZ SCORE</h3>
                            
                            @if(count($quizScores) > 0)
                                <div class="space-y-2">
                                    @foreach($quizScores as $index => $quizScore)
                                        <div class="flex items-center justify-between p-3 bg-white rounded">
                                            <span class="text-sm font-medium text-gray-900">
                                                QUIZ {{ $index + 1 }}: {{ $quizScore['correct'] }}/{{ $quizScore['total'] }}
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

                    {{-- Card 3: WEBSITE HISTORY (matching Image 3) --}}
                    {{-- This section displays recent browsing logs for the selected device --}}
                    {{-- Browsing logs are automatically created by the ParseNetworkLogs background job --}}
                    <div class="bg-gray-100 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">WEBSITE HISTORY</h3>
                                {{-- View All Link: Links to full browsing logs page with device pre-filtered --}}
                                @if($websiteHistory->count() > 0)
                                    <a href="{{ route('browsing-logs.index', ['device_id' => $device->id]) }}" 
                                       class="text-sm text-blue-600 hover:underline">
                                        View All
                                    </a>
                                @endif
                            </div>
                            
                            {{-- Display recent browsing logs (limit 10-15 from controller) --}}
                            @if($websiteHistory->count() > 0)
                                <div class="space-y-2">
                                    @foreach($websiteHistory as $log)
                                        <div class="p-3 bg-white rounded">
                                            {{-- Domain and URL: Shows which website was visited --}}
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <span class="text-sm font-medium text-gray-900">{{ $log->domain }}</span>
                                                    {{-- URL: Truncated if too long, shows full URL on hover --}}
                                                    <p class="text-xs text-gray-500 mt-1" title="{{ $log->url }}">
                                                        {{ Str::limit($log->url, 60) }}
                                                    </p>
                                                </div>
                                                {{-- Visited At: Timestamp showing when the site was visited --}}
                                                <span class="text-xs text-gray-400 ml-2">
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            'use strict';

            const chartUrlBase = @json(route('child_devices.usage-chart', $device));

            window.childTimeUsageChartInstance = window.childTimeUsageChartInstance ?? null;
            window.currentChildUsageChartRange = window.currentChildUsageChartRange ?? 'yearly';
            window.__childUsageChartRefreshImpl = null;

            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('timeUsageChart');
                if (!ctx || typeof Chart === 'undefined') return;

                const filterButtons = document.querySelectorAll('[data-child-usage-chart-range]');
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

                const activeBtn = document.querySelector('.js-child-usage-chart-filter-active[data-child-usage-chart-range]');
                if (activeBtn) {
                    setActiveFilter(activeBtn.dataset.childUsageChartRange);
                } else {
                    setActiveFilter('yearly');
                }

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

                const buildDatasets = (series) => {
                    return series.map((item, idx) => {
                        const borderColor = makeBorderColor(idx);
                        return {
                            label: item.device_name,
                            data: item.values,
                            borderColor,
                            backgroundColor: 'rgba(255, 222, 21, 0.10)',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#FFDE15',
                            pointBorderColor: borderColor,
                            pointBorderWidth: 2
                        };
                    });
                };

                const renderChart = (payload) => {
                    const labels = payload.labels ?? [];
                    const series = payload.series ?? [];

                    destroyChartIfNeeded();

                    const allValues = series.flatMap((s) => Array.isArray(s.values) ? s.values : []);
                    const maxVal = Math.max(...allValues, 0);
                    const suggestedMax = maxVal > 0 ? maxVal * 1.2 : 10;
                    const dailyFixedMax = payload.range === 'daily' ? 60 : null;

                    // Per-bucket caps (same as dashboard): match max usable time in one bucket, not whole chart.
                    const maxByRangeInChartUnit = (() => {
                        if (payload.range === 'daily') return 60;
                        if (payload.range === 'weekly') return 24;
                        if (payload.range === 'monthly') return 168;
                        if (payload.range === 'yearly') return 31 * 24;
                        return null;
                    })();

                    const showLegend = series.length > 0 && series.length <= 6;
                    const datasets = buildDatasets(series);

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
                                        font: { family: 'Montserrat', size: 11, weight: 'bold' },
                                        boxWidth: 10
                                    }
                                },
                                tooltip: {
                                    enabled: true,
                                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                    titleColor: '#fff',
                                    bodyColor: '#fff',
                                    borderColor: '#FFDE15',
                                    borderWidth: 2,
                                    padding: 12,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: (context) => {
                                            const label = context.dataset?.label ? context.dataset.label + ': ' : '';
                                            const unit = payload.unit === 'hours' ? ' hr' : ' min';
                                            return label + context.parsed.y + unit;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ...(dailyFixedMax !== null
                                        ? { max: dailyFixedMax }
                                        : (maxByRangeInChartUnit !== null ? { max: maxByRangeInChartUnit } : { suggestedMax })),
                                    title: {
                                        display: true,
                                        text: payload.unit === 'hours' ? 'Time Spent (hours)' : 'Time Spent (minutes)',
                                        font: { family: 'Montserrat', size: 12, weight: 'bold' },
                                        color: '#000000',
                                        padding: { top: 6, bottom: 6 }
                                    },
                                    ticks: {
                                        font: { size: 12, weight: 'bold', family: 'Montserrat' },
                                        ...(dailyFixedMax !== null ? { stepSize: 10 } : {}),
                                        color: '#000000',
                                        padding: 8
                                    },
                                    grid: { color: 'rgba(0, 0, 0, 0.1)', lineWidth: 1.3 }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 11, weight: 'bold', family: 'Montserrat' },
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

                const fetchChildUsageChart = async (range) => {
                    const url = chartUrlBase + (chartUrlBase.includes('?') ? '&' : '?') + 'range=' + encodeURIComponent(range);
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) {
                        throw new Error('Usage chart request failed: ' + res.status);
                    }
                    return await res.json();
                };

                let refreshInFlight = false;
                window.__childUsageChartRefreshImpl = async (reason) => {
                    if (refreshInFlight) return;
                    refreshInFlight = true;
                    try {
                        const range = window.currentChildUsageChartRange || 'yearly';
                        const payload = await fetchChildUsageChart(range);
                        renderChart(payload);
                    } catch (e) {
                        console.error('Failed to refresh child device usage chart', reason, e);
                    } finally {
                        refreshInFlight = false;
                    }
                };

                filterButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        setActiveFilter(btn.dataset.childUsageChartRange);
                        window.__childUsageChartRefreshImpl('filter-change');
                    });
                });

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

