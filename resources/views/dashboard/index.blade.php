<x-app-layout>
    <main class="flex-1 w-full bg-[#FFFFCC] font-sans text-gray-900 overflow-x-hidden overflow-y-hidden pl-4 pr-4 sm:pl-6 sm:pr-6 lg:pl-10 lg:pr-10 py-4 sm:py-6" style="margin-top: 0; font-family: 'Montserrat', sans-serif; max-width: 100%; height: calc(100vh - 0px); display: flex; flex-direction: column;">

        <!-- Welcome Section -->
        <section class="mb-4 flex-shrink-0">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="text-center sm:text-left">
                    <div class="text-lg sm:text-xl font-normal mb-1 font-montserrat">Welcome,</div>
                    <h1 class="font-extrabold text-xl sm:text-2xl tracking-tight font-montserrat text-black">{{ Auth::user()->name }}</h1>
                </div>
                <div id="latestLiveToast" class="hidden w-full sm:w-auto sm:max-w-md rounded-lg border px-3 py-2 text-xs sm:text-sm font-montserrat shadow-sm bg-white">
                    <span id="latestLiveToastMessage">Waiting for live notifications...</span>
                </div>
            </div>
        </section>

        <!-- Scrollable dashboard content area (stops above realtime notifications) -->
        <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar pr-1 sm:pr-2">
        <!-- Dashboard Grid - 12 Column Layout -->
        <section class="grid grid-cols-12 gap-3 sm:gap-4 w-full max-w-full pb-3 sm:pb-4" style="grid-auto-rows: 1fr;">
            
            <!-- Column 2: Time Usage Card (7 columns) -->
            <article class="col-span-12 sm:col-span-7 rounded-xl bg-white text-black border-4 border-[#FFDE15] p-4 sm:p-6 flex flex-col gap-2 min-w-0 overflow-hidden" style="min-height: 0;">
                <div class="flex items-center justify-between flex-shrink-0">
                    <h2 class="text-base sm:text-xl font-extrabold flex items-center gap-2 font-montserrat">
                        <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="clock"></i> TIME USAGE
                    </h2>
                    <time datetime="{{ now()->toIso8601String() }}" class="text-xs sm:text-sm font-semibold text-gray-600 font-montserrat">
                        {{ $currentDate }}
                    </time>
                </div>
                <p class="text-xs text-gray-600 font-montserrat flex-shrink-0 leading-snug">
                    <span class="font-semibold text-gray-700">Today’s usage</span> — time counted while this child still has <span class="font-semibold text-gray-700">granted internet time</span> (not idle Wi‑Fi after time runs out; resets at midnight).
                </p>
                {{-- Server render time: client adds live session seconds to today's usage + remaining countdown --}}
                <meta name="time-usage-rendered-at" content="{{ $timeUsageRenderedAt }}">

                <div class="text-xs sm:text-sm font-normal leading-relaxed font-montserrat space-y-2 flex-1 overflow-y-auto custom-scrollbar">
                    @forelse($timeUsageData as $index => $data)
                        <div
                            class="js-dashboard-time-row flex items-center justify-between p-2 sm:p-3 rounded-lg border-2 border-gray-100 hover:border-[#FFDE15] transition-all"
                            data-device-id="{{ $data['device']->id }}"
                            data-db-remaining-minutes="{{ $data['db_remaining_minutes'] }}"
                            data-active-session-started-at="{{ $data['active_session_started_at'] ?? '' }}"
                            data-total-usage-seconds="{{ $data['total_seconds'] }}"
                            data-is-whitelisted="{{ $data['is_whitelisted'] ? '1' : '0' }}"
                            data-is-connected="{{ $data['is_connected'] ? '1' : '0' }}"
                            data-remaining-minutes-fallback="{{ $data['remaining_minutes'] }}"
                        >
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-bold text-sm sm:text-base text-black bg-[#FFDE15] flex-shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-black font-montserrat text-xs sm:text-sm truncate">{{ $index + 1 }}. {{ $data['device']->name }}</p>
                                    @if($data['is_connected'])
                                        <span class="text-xs font-semibold text-green-600 flex items-center gap-1.5 mt-1 font-montserrat" aria-label="Device is connected">
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span> Connected
                                        </span>
                                    @else
                                        <span class="text-xs font-semibold text-gray-400 flex items-center gap-1.5 mt-1 font-montserrat" aria-label="Device is not connected">
                                            <span class="w-2 h-2 bg-gray-400 rounded-full"></span> Offline
                                        </span>
                                    @endif
                                    @if(!empty($data['active_session_started_at']))
                                        <p class="text-[10px] text-gray-500 font-montserrat mt-0.5">
                                            Current session since {{ \Carbon\Carbon::parse($data['active_session_started_at'])->timezone(config('app.timezone'))->format('g:i A') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-gray-500 font-montserrat mb-0.5">Today</p>
                                <p class="js-time-usage-usage text-lg sm:text-xl font-bold text-black mb-0.5 font-montserrat">
                                    {{ $data['hours'] }}h{{ str_pad($data['minutes'], 2, '0', STR_PAD_LEFT) }}m
                                </p>
                                <p class="text-[10px] font-semibold text-gray-500 font-montserrat mb-0.5">Time left</p>
                                <p class="js-time-usage-remaining text-xs font-semibold {{ $data['remaining_minutes'] > 0 ? 'text-gray-600' : 'text-red-600' }} font-montserrat">
                                    @if($data['is_whitelisted'])
                                        Unlimited
                                    @else
                                        {{ $data['remaining_minutes'] > 0 ? $data['remaining_minutes'] . ' min remaining' : 'Expired' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600 font-montserrat">No devices found. <a href="{{ route('accounts.create') }}" class="text-blue-600 underline focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded">Add a device</a></p>
                    @endforelse
                </div>
                
                @if(count($timeUsageData) > 0)
                    <button onclick="window.location.href='{{ route('child_devices.index') }}'"
                        class="use-loader mt-2 inline-flex items-center font-montserrat gap-2 rounded-full bg-green-600 text-white px-3 py-1.5 text-xs sm:text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit flex-shrink-0">
                        <i data-feather="eye" class="w-3 h-3 sm:w-4 sm:h-4"></i> View All Devices
                    </button>
                @endif
            </article>

            <!-- Column 3: Quiz Results Card (5 columns) -->
            <article class="col-span-12 sm:col-span-5 bg-white border-4 border-[#FFDE15] rounded-xl p-4 sm:p-6 flex flex-col gap-2 min-w-0 overflow-hidden" style="min-height: 0;">
                <h2 class="text-base sm:text-xl font-extrabold flex items-center gap-2 font-montserrat text-black flex-shrink-0">
                    <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="file-text"></i> QUIZ RESULTS
                </h2>
                
                <div class="flex-1 text-xs sm:text-sm font-normal leading-relaxed space-y-2 sm:space-y-3 font-montserrat overflow-y-auto pr-2 custom-scrollbar" style="min-height: 0;">
                    @forelse($quizResults as $quizResult)
                        <div class="border-b-2 border-gray-100 pb-3 last:border-b-0">
                            <h3 class="font-bold text-black mb-2 font-montserrat">
                                <a href="{{ route('quizzes.index') }}" 
                                   class="hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded"
                                   aria-label="View quiz: {{ $quizResult['quiz']->title }}">
                                    {{ $quizResult['quiz']->title }}
                                </a>
                            </h3>
                            <ul class="space-y-1">
                                @foreach($quizResult['attempts'] as $attemptIndex => $attempt)
                                    <li class="text-sm flex items-center justify-between font-montserrat">
                                        <span class="text-gray-700">
                                            <span class="font-semibold text-black">{{ $attemptIndex + 1 }}. {{ $attempt['device']->name }}:</span>
                                            <span class="font-bold text-black ml-1">{{ $attempt['correct_count'] }}/{{ $attempt['total_questions'] }}</span>
                                        </span>
                                        <time datetime="{{ $attempt['completed_at']->toIso8601String() }}" class="text-xs text-gray-500 font-montserrat">
                                            {{ $attempt['completed_at']->format('M j, Y') }}
                                        </time>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-gray-600 font-montserrat">No quiz attempts yet. <a href="{{ route('quizzes.create') }}" class="text-blue-600 underline focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 rounded">Create a quiz</a></p>
                    @endforelse
                </div>
            </article>

            <!-- Column 2: Graphical Representation Card (7 columns) -->
            <article class="col-span-12 sm:col-span-7 rounded-xl bg-white border-4 border-[#FFDE15] p-2 sm:p-4 flex flex-col text-black min-w-0 overflow-hidden" style="min-height: 0;">
                <h2 class="text-sm sm:text-lg font-extrabold flex items-center gap-2 font-montserrat mb-1 flex-shrink-0">
                    <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="trending-up"></i> CHILD'S DEVICE USAGE TIME
                </h2>
                <!-- Filter controls for the usage chart -->
                <div class="mb-1.5 flex items-center justify-between gap-3 flex-shrink-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-gray-600 font-montserrat">FILTER</span>
                    <div class="flex flex-wrap gap-2 justify-end">
                        <button type="button"
                            class="js-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                            data-usage-chart-range="daily">
                            Daily
                        </button>
                        <button type="button"
                            class="js-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                            data-usage-chart-range="weekly">
                            Weekly
                        </button>
                        <button type="button"
                            class="js-usage-chart-filter inline-flex items-center justify-center rounded-full border-2 border-gray-300 bg-white text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFDE15] transition"
                            data-usage-chart-range="monthly">
                            Monthly
                        </button>
                        <button type="button"
                            class="js-usage-chart-filter js-usage-chart-filter-active inline-flex items-center justify-center rounded-full border-2 border-[#FFDE15] bg-[#FFDE15] text-black px-2.5 py-1 text-[11px] sm:text-xs font-semibold font-montserrat hover:border-[#FFC107] transition"
                            data-usage-chart-range="yearly">
                            Yearly
                        </button>
                    </div>
                </div>
                {{-- 
                    Chart sizing note:
                    - Chart.js needs a real container height to render correctly.
                    - We avoid a tiny fixed height so the graph fills the card and looks “premium”.
                --}}
                <div class="flex-1 relative min-h-[60px] sm:min-h-[70px] lg:min-h-[75px]">
                    <canvas id="usageChart" role="img" aria-label="Time usage graph showing minutes used per child device"></canvas>
                </div>
            </article>

            <!-- Column 3: Quiz & Video Remaining Card (5 columns) -->
            <article class="col-span-12 sm:col-span-5 rounded-xl bg-white border-4 border-[#FFDE15] p-2 sm:p-3 flex flex-col gap-0.5 min-w-0 overflow-hidden" style="min-height: 0;">
                <h2 class="text-xs sm:text-base font-extrabold flex items-center gap-2 font-montserrat text-black flex-shrink-0">
                    <i class="w-3 h-3 sm:w-4 sm:h-4" data-feather="check-circle"></i> QUIZ & VIDEO REMAINING
                </h2>

                <!-- Quiz Remaining -->
                <div class="space-y-0.5 flex-none flex flex-col">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-black font-montserrat">QUIZ REMAINING</span>
                            <span class="text-sm font-semibold text-gray-600 font-montserrat" aria-label="Quiz remaining count">{{ $totalQuizzes }} available</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1">
                            <div class="bg-green-500 h-0.5 rounded-full transition-all duration-300" style="width: {{ $quizRemainingPercent }}%"></div>
                        </div>
                        <p class="text-[12px] text-gray-600 font-montserrat mt-0.5">{{ $quizRemainingPercent }}% Available</p>
                    </div>

                    <!-- Video Remaining -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-black font-montserrat">VIDEO REMAINING</span>
                            <span class="text-sm font-semibold text-gray-600 font-montserrat" aria-label="Video remaining count">{{ $totalVideos }} available</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1">
                            <div class="bg-green-500 h-0.5 rounded-full transition-all duration-300" style="width: {{ $videoRemainingPercent }}%"></div>
                        </div>
                        <p class="text-[12px] text-gray-600 font-montserrat mt-0.5">{{ $videoRemainingPercent }}% Available</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 mt-0.5 flex-shrink-0 pt-0">
                    <button type="button" onclick="window.location.href='{{ route('quizzes.index') }}'"
                        class="use-loader inline-flex font-montserrat items-center gap-1.5 rounded-full bg-red-600 text-white px-3 py-1.5 text-xs sm:text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                        <i data-feather="edit-2" class="w-3 h-3 sm:w-4 sm:h-4"></i> Manage Quizzes
                    </button>
                    <a href="{{ route('videos.index') }}" target="_blank"
                        class="use-loader inline-flex font-montserrat items-center gap-1.5 rounded-full bg-blue-600 text-white px-3 py-1.5 text-xs sm:text-sm font-medium shadow-sm hover:bg-opacity-90 transition w-fit">
                        <i data-feather="video" class="w-3 h-3 sm:w-4 sm:h-4"></i> Manage Videos
                    </a>
                </div>
            </article>

            <!-- Realtime Notifications Card (5th grid item, spans both columns) -->
            <article class="col-span-12 rounded-xl bg-white border-4 border-[#FFDE15] p-4 sm:p-5 min-w-0 overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm sm:text-lg font-extrabold flex items-center gap-2 font-montserrat text-black">
                        <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="bell"></i> REAL-TIME NOTIFICATIONS
                    </h2>
                    <span class="text-xs font-semibold text-gray-500 font-montserrat">Live</span>
                </div>
                <ul id="realtimeNotificationsList" class="space-y-2 text-xs sm:text-sm font-montserrat h-64 sm:h-72 overflow-y-auto custom-scrollbar pr-2">
                    <li id="realtimeNotificationsEmpty" class="text-gray-600">Waiting for live events...</li>
                </ul>
            </article>

        </section>
        </div>

    </main>

    @push('scripts')
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #F3F4F6;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #FFDE15;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #FFC107;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Multi-series dashboard usage chart (per device) driven by `/dashboard/usage-chart`.
        (function() {
            'use strict';

            // These globals allow the realtime notification script to request a chart refresh.
            window.usageChartInstance = window.usageChartInstance ?? null;
            window.currentUsageChartRange = window.currentUsageChartRange ?? 'yearly';
            window.__usageChartRefreshImpl = null;
            window.refreshUsageChart = function (reason) {
                if (typeof window.__usageChartRefreshImpl === 'function') {
                    window.__usageChartRefreshImpl(reason);
                }
            };

            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Feather Icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                const ctx = document.getElementById('usageChart');
                if (!ctx) return;

                const filterButtons = document.querySelectorAll('[data-usage-chart-range]');
                const setActiveFilter = (range) => {
                    window.currentUsageChartRange = range;
                    filterButtons.forEach((btn) => {
                        const isActive = btn.dataset.usageChartRange === range;
                        btn.classList.toggle('js-usage-chart-filter-active', isActive);
                        if (isActive) {
                            btn.classList.add('border-[#FFDE15]', 'bg-[#FFDE15]');
                            btn.classList.remove('border-gray-300', 'bg-white');
                        } else {
                            btn.classList.remove('border-[#FFDE15]', 'bg-[#FFDE15]');
                            btn.classList.add('border-gray-300', 'bg-white');
                        }
                    });
                };

                // Pick initial range from the active button (Yearly by default).
                const activeBtn = document.querySelector('.js-usage-chart-filter-active[data-usage-chart-range]');
                if (activeBtn) {
                    setActiveFilter(activeBtn.dataset.usageChartRange);
                } else {
                    setActiveFilter('yearly');
                }

                // Build visually distinct line colors for multiple child devices.
                const makeBorderColor = (index) => {
                    const hue = (index * 55) % 360;
                    return `hsl(${hue} 70% 35%)`;
                };

                const destroyChartIfNeeded = () => {
                    if (window.usageChartInstance) {
                        window.usageChartInstance.destroy();
                        window.usageChartInstance = null;
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

                    // The API returns a unit per range:
                    // - daily => minutes
                    // - weekly/monthly/yearly => hours
                    //
                    // Cap Y-axis to the realistic maximum for a *single bucket* (not the whole range).
                    //
                    // - Daily: one bucket = one hour → max 60 minutes
                    // - Weekly: one bucket = one calendar day → max 24 hours
                    // - Monthly: one bucket = up to 7 days in that month slice → max 168 hours
                    // - Yearly: one bucket = one calendar month → max 31×24 hours (longest month)
                    const maxByRangeInChartUnit = (() => {
                        if (payload.range === 'daily') return 60;
                        if (payload.range === 'weekly') return 24;
                        if (payload.range === 'monthly') return 168;
                        if (payload.range === 'yearly') return 31 * 24;
                        return null;
                    })();

                    const showLegend = series.length > 0 && series.length <= 6;
                    const datasets = buildDatasets(series);

                    window.usageChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: showLegend,
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            family: 'Montserrat',
                                            size: 11,
                                            weight: 'bold'
                                        },
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
                                        // Simple, parent-friendly label.
                                        text: payload.unit === 'hours' ? 'Time Spent (hours)' : 'Time Spent (minutes)',
                                        font: {
                                            family: 'Montserrat',
                                            size: 12,
                                            weight: 'bold'
                                        },
                                        color: '#000000',
                                        padding: { top: 6, bottom: 6 }
                                    },
                                    ticks: {
                                        font: {
                                            size: 12,
                                            weight: 'bold',
                                            family: 'Montserrat'
                                        },
                                        ...(dailyFixedMax !== null ? { stepSize: 10 } : {}),
                                        color: '#000000',
                                        padding: 8
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.1)',
                                        lineWidth: 1.3,
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: {
                                            size: 11,
                                            weight: 'bold',
                                            family: 'Montserrat'
                                        },
                                        color: '#000000',
                                        padding: 6
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            animation: {
                                duration: 600,
                                easing: 'easeInOutQuart'
                            }
                        }
                    });
                };

                const fetchUsageChart = async (range) => {
                    const url = `/dashboard/usage-chart?range=${encodeURIComponent(range)}`;
                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (!res.ok) {
                        throw new Error(`Usage chart request failed: ${res.status}`);
                    }

                    return await res.json();
                };

                // Refresh chart using whichever filter is currently selected.
                let refreshInFlight = false;
                window.__usageChartRefreshImpl = async (reason) => {
                    if (refreshInFlight) return;
                    refreshInFlight = true;
                    try {
                        const range = window.currentUsageChartRange || 'yearly';
                        const payload = await fetchUsageChart(range);
                        renderChart(payload);
                    } catch (e) {
                        console.error('Failed to refresh usage chart', reason, e);
                    } finally {
                        refreshInFlight = false;
                    }
                };

                // Wire filter buttons.
                filterButtons.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const range = btn.dataset.usageChartRange;
                        setActiveFilter(range);
                        window.__usageChartRefreshImpl('filter-change');
                    });
                });

                // Initial load.
                window.__usageChartRefreshImpl('initial-load');

                // Active-session gap:
                // Even with realtime events, active sessions may advance without a websocket tick every second.
                // We refresh only for the Daily view and only while the tab is visible.
                setInterval(() => {
                    if (window.currentUsageChartRange !== 'daily') return;
                    if (document.visibilityState !== 'visible') return;
                    window.__usageChartRefreshImpl('daily-timer');
                }, 60000);
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                if (window.usageChartInstance) {
                    window.usageChartInstance.destroy();
                    window.usageChartInstance = null;
                }
            });
        })();
    </script>
    {{-- Live time usage + remaining: 1s client tick (session-aware); WebSockets optional for syncing grants/expiry --}}
    <script>
        (function () {
            'use strict';

            function parseIsoMs(s) {
                if (!s) {
                    return NaN;
                }
                const t = Date.parse(s);
                return Number.isNaN(t) ? NaN : t;
            }

            function formatUsageSeconds(totalSec) {
                const sec = Math.max(0, Math.floor(totalSec));
                const h = Math.floor(sec / 3600);
                const m = Math.floor((sec % 3600) / 60);
                return h + 'h' + String(m).padStart(2, '0') + 'm';
            }

            function formatRemainingLabel(isWhitelisted, hasActive, remainingSec, fallbackMin, isConnected) {
                if (isWhitelisted) {
                    return 'Unlimited';
                }
                if (!hasActive) {
                    if (fallbackMin <= 0) {
                        return 'Expired';
                    }
                    // Connected but no DeviceSession row yet (e.g. scheduler hasn’t opened a session):
                    // still tick down from page load so “Time left” isn’t frozen.
                    if (isConnected) {
                        if (remainingSec <= 0) {
                            return 'Expired';
                        }
                        const m = Math.floor(remainingSec / 60);
                        const s = Math.floor(remainingSec % 60);
                        return m + ':' + String(s).padStart(2, '0') + ' left';
                    }
                    return fallbackMin + ' min remaining';
                }
                if (remainingSec <= 0) {
                    return 'Expired';
                }
                const h = Math.floor(remainingSec / 3600);
                const m = Math.floor((remainingSec % 3600) / 60);
                const s = Math.floor(remainingSec % 60);
                if (h > 0) {
                    return h + 'h ' + String(m).padStart(2, '0') + 'm ' + String(s).padStart(2, '0') + 's left';
                }
                return m + ':' + String(s).padStart(2, '0') + ' left';
            }

            function tickDashboardTimeRows() {
                const renderedAt = document.querySelector('meta[name="time-usage-rendered-at"]')?.getAttribute('content');
                const t0 = parseIsoMs(renderedAt);
                const now = Date.now();
                const deltaSec = !Number.isNaN(t0) ? Math.max(0, Math.floor((now - t0) / 1000)) : 0;

                document.querySelectorAll('.js-dashboard-time-row').forEach(function (row) {
                    const totalBase = parseInt(row.dataset.totalUsageSeconds || '0', 10) || 0;
                    const activeStart = row.dataset.activeSessionStartedAt || '';
                    const hasActive = activeStart.length > 0;
                    const usageSec = totalBase + (hasActive ? deltaSec : 0);

                    const isWhitelisted = row.dataset.isWhitelisted === '1';
                    const isConnected = row.dataset.isConnected === '1';
                    const dbRemMin = parseInt(row.dataset.dbRemainingMinutes || '0', 10) || 0;
                    const fallbackMin = parseInt(row.dataset.remainingMinutesFallback || '0', 10) || 0;

                    let remainingSec = 0;
                    if (isWhitelisted) {
                        remainingSec = 999999;
                    } else if (hasActive) {
                        const startMs = parseIsoMs(activeStart);
                        const elapsedSec = !Number.isNaN(startMs) ? Math.floor((now - startMs) / 1000) : 0;
                        remainingSec = Math.max(0, dbRemMin * 60 - elapsedSec);
                    } else if (isConnected && fallbackMin > 0) {
                        remainingSec = Math.max(0, fallbackMin * 60 - deltaSec);
                    } else {
                        remainingSec = Math.max(0, fallbackMin * 60);
                    }

                    const usageEl = row.querySelector('.js-time-usage-usage');
                    const remEl = row.querySelector('.js-time-usage-remaining');
                    if (usageEl) {
                        usageEl.textContent = formatUsageSeconds(usageSec);
                    }
                    if (remEl) {
                        remEl.textContent = formatRemainingLabel(isWhitelisted, hasActive, remainingSec, fallbackMin, isConnected);
                        const expired = !isWhitelisted && (
                            (hasActive && remainingSec <= 0) ||
                            (!hasActive && fallbackMin <= 0) ||
                            (!hasActive && isConnected && fallbackMin > 0 && remainingSec <= 0)
                        );
                        remEl.className = 'js-time-usage-remaining text-xs font-semibold font-montserrat ' +
                            (isWhitelisted ? 'text-gray-600' : (expired ? 'text-red-600' : 'text-gray-600'));
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                tickDashboardTimeRows();
                setInterval(tickDashboardTimeRows, 1000);
            });

            window.tickDashboardTimeRows = tickDashboardTimeRows;
        })();
    </script>
    <script>
        (function () {
            'use strict';

            document.addEventListener('DOMContentLoaded', function () {
                // Subscribe using auth user id from layout meta tag.
                // This must match private channel authorization in routes/channels.php.
                const userId = document.querySelector('meta[name="auth-user-id"]')?.getAttribute('content');
                const list = document.getElementById('realtimeNotificationsList');
                const empty = document.getElementById('realtimeNotificationsEmpty');
                const latestToast = document.getElementById('latestLiveToast');
                const latestToastMessage = document.getElementById('latestLiveToastMessage');
                let latestToastTimeout = null;

                // Graceful fallback: if Echo is not initialized, dashboard still works without live updates.
                if (!userId || !list || typeof window.Echo === 'undefined') {
                    return;
                }

                const toastColors = {
                    info: 'border-blue-300 text-blue-900 bg-white',
                    warning: 'border-yellow-300 text-yellow-900 bg-white',
                    danger: 'border-red-300 text-red-900 bg-white',
                    success: 'border-green-300 text-green-900 bg-white',
                };

                // Show a single popup in the welcome row; always overwrite with latest event.
                const showLatestToast = (message, type = 'info') => {
                    if (!latestToast || !latestToastMessage) {
                        return;
                    }

                    latestToast.className = `w-full sm:w-auto sm:max-w-md rounded-lg border px-3 py-2 text-xs sm:text-sm font-montserrat shadow-sm ${toastColors[type] ?? toastColors.info}`;
                    latestToastMessage.textContent = `${new Date().toLocaleTimeString()} - ${message}`;

                    if (latestToastTimeout) {
                        clearTimeout(latestToastTimeout);
                    }

                    latestToastTimeout = setTimeout(() => {
                        latestToast.classList.add('hidden');
                    }, 5000);
                };

                // Small UI helper that keeps the notification list bounded and readable.
                const addNotification = (message, type = 'info') => {
                    if (empty) {
                        empty.remove();
                    }

                    const li = document.createElement('li');
                    const colors = {
                        info: 'border-blue-200 bg-blue-50 text-blue-900',
                        warning: 'border-yellow-200 bg-yellow-50 text-yellow-900',
                        danger: 'border-red-200 bg-red-50 text-red-900',
                        success: 'border-green-200 bg-green-50 text-green-900',
                    };

                    li.className = `rounded-lg border px-3 py-2 ${colors[type] ?? colors.info}`;
                    li.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
                    list.prepend(li);

                    showLatestToast(message, type);

                };

                // Listen to backend broadcast aliases and map them to human-friendly alerts.
                // These aliases are defined in app/Events/* via broadcastAs().
                window.Echo.private(`user.${userId}`)
                    .listen('.device.connected', (event) => {
                        addNotification(`${event.device_name} connected (${event.ip_address ?? 'unknown IP'})`, 'success');
                        if (typeof window.refreshUsageChart === 'function') {
                            window.refreshUsageChart('device.connected');
                        }
                    })
                    .listen('.device.disconnected', (event) => {
                        addNotification(`${event.device_name} disconnected`, 'warning');
                        if (typeof window.refreshUsageChart === 'function') {
                            window.refreshUsageChart('device.disconnected');
                        }
                    })
                    .listen('.time.expired', (event) => {
                        addNotification(`Time expired for ${event.device_name}. Device redirected to portal.`, 'danger');
                        const row = document.querySelector('.js-dashboard-time-row[data-device-id="' + event.device_id + '"]');
                        if (row) {
                            row.dataset.dbRemainingMinutes = '0';
                            row.dataset.remainingMinutesFallback = '0';
                            row.dataset.activeSessionStartedAt = '';
                        }
                        if (typeof window.tickDashboardTimeRows === 'function') {
                            window.tickDashboardTimeRows();
                        }
                        if (typeof window.refreshUsageChart === 'function') {
                            window.refreshUsageChart('time.expired');
                        }
                    })
                    .listen('.time.granted', (event) => {
                        addNotification(`${event.minutes_granted} minutes granted to ${event.device_name} via ${event.source}.`, 'success');
                        const row = document.querySelector('.js-dashboard-time-row[data-device-id="' + event.device_id + '"]');
                        if (row && event.remaining_minutes !== undefined) {
                            row.dataset.dbRemainingMinutes = String(event.remaining_minutes);
                            row.dataset.remainingMinutesFallback = String(event.remaining_minutes);
                        }
                        if (typeof window.tickDashboardTimeRows === 'function') {
                            window.tickDashboardTimeRows();
                        }
                        if (typeof window.refreshUsageChart === 'function') {
                            window.refreshUsageChart('time.granted');
                        }
                    })
                    .listen('.website.blocked_accessed', (event) => {
                        const target = event.domain || event.url || 'blocked website';
                        addNotification(`${event.device_name} attempted blocked site: ${target}`, 'danger');
                    })
                    .listen('.website.flagged_visited', (event) => {
                        const target = event.domain || event.url || 'flagged website';
                        addNotification(`${event.device_name} visited flagged site: ${target}`, 'warning');
                    });
            });
        })();
    </script>
    @endpush
</x-app-layout>
