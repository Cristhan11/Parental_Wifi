<x-app-layout>
    <main class="flex-1 w-full bg-[#FFFFCC] font-sans text-gray-900 overflow-x-hidden pl-4 pr-4 sm:pl-6 sm:pr-6 lg:pl-10 lg:pr-10 py-4 sm:py-6" style="margin-top: 0; font-family: 'Montserrat', sans-serif; max-width: 100%; height: calc(100vh - 0px); display: flex; flex-direction: column;">

        <!-- Welcome Section -->
        <section class="text-center sm:text-left mb-4 flex-shrink-0">
            <div class="text-lg sm:text-xl font-normal mb-1 font-montserrat">Welcome,</div>
            <h1 class="font-extrabold text-xl sm:text-2xl tracking-tight font-montserrat text-black">{{ Auth::user()->name }}</h1>
        </section>

        <!-- Dashboard Grid - 12 Column Layout -->
        <section class="grid grid-cols-12 gap-3 sm:gap-4 w-full max-w-full flex-1 min-h-0" style="grid-auto-rows: 1fr;">
            
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
                
                <div class="text-xs sm:text-sm font-normal leading-relaxed font-montserrat space-y-2 flex-1 overflow-y-auto custom-scrollbar">
                    @forelse($timeUsageData as $index => $data)
                        <div class="flex items-center justify-between p-2 sm:p-3 rounded-lg border-2 border-gray-100 hover:border-[#FFDE15] transition-all">
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
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-lg sm:text-xl font-bold text-black mb-0.5 font-montserrat">
                                    {{ $data['hours'] }}h{{ str_pad($data['minutes'], 2, '0', STR_PAD_LEFT) }}m
                                </p>
                                <p class="text-xs font-semibold {{ $data['remaining_minutes'] > 0 ? 'text-gray-600' : 'text-red-600' }} font-montserrat">
                                    {{ $data['remaining_minutes'] > 0 ? $data['remaining_minutes'] . ' min remaining' : 'Expired' }}
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
                                            <span class="font-bold text-black ml-1">{{ $attempt['score'] }}/{{ $attempt['total_questions'] }}</span>
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
            <article class="col-span-12 sm:col-span-7 rounded-xl bg-white border-4 border-[#FFDE15] p-4 sm:p-6 flex flex-col text-black min-w-0 overflow-hidden" style="min-height: 0;">
                <h2 class="text-base sm:text-xl font-extrabold flex items-center gap-2 font-montserrat mb-2 flex-shrink-0">
                    <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="trending-up"></i> GRAPHICAL REPRESENTATION
                </h2>
                <div class="flex-1 relative min-h-0" style="height: 180px; max-height: 220px;">
                    <canvas id="usageChart" role="img" aria-label="Monthly internet usage graph showing hours used per month for the last 12 months"></canvas>
                </div>
            </article>

            <!-- Column 3: Quiz & Video Remaining Card (5 columns) -->
            <article class="col-span-12 sm:col-span-5 rounded-xl bg-white border-4 border-[#FFDE15] p-4 sm:p-6 flex flex-col gap-3 min-w-0 overflow-hidden" style="min-height: 0;">
                <h2 class="text-base sm:text-xl font-extrabold flex items-center gap-2 font-montserrat text-black flex-shrink-0">
                    <i class="w-4 h-4 sm:w-5 sm:h-5" data-feather="check-circle"></i> QUIZ & VIDEO REMAINING
                </h2>

                <!-- Quiz Remaining -->
                <div class="space-y-3 flex-1 flex flex-col justify-center">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-black font-montserrat">QUIZ REMAINING</span>
                            <span class="text-sm font-semibold text-gray-600 font-montserrat" aria-label="Quiz remaining count">{{ $totalQuizzes }} available</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $quizRemainingPercent }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 font-montserrat mt-1">{{ $quizRemainingPercent }}% Available</p>
                    </div>

                    <!-- Video Remaining -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-black font-montserrat">VIDEO REMAINING</span>
                            <span class="text-sm font-semibold text-gray-600 font-montserrat" aria-label="Video remaining count">{{ $totalVideos }} available</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $videoRemainingPercent }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 font-montserrat mt-1">{{ $videoRemainingPercent }}% Available</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 mt-2 flex-shrink-0">
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

        </section>

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
        // Store chart instance globally to prevent reuse errors
        (function() {
            'use strict';
            
            // Use window object to store chart instance to avoid redeclaration errors
            if (typeof window.usageChartInstance !== 'undefined' && window.usageChartInstance !== null) {
                window.usageChartInstance.destroy();
                window.usageChartInstance = null;
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Feather Icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                const ctx = document.getElementById('usageChart');
                if (!ctx) return;

                // Destroy existing chart if it exists to prevent canvas reuse error
                if (window.usageChartInstance) {
                    window.usageChartInstance.destroy();
                    window.usageChartInstance = null;
                }

                const monthlyData = @json($monthlyUsage);
                
                // Extract months and hours
                const months = monthlyData.map(item => item.month);
                const hours = monthlyData.map(item => item.hours);

                window.usageChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Hours Used',
                            data: hours,
                            borderColor: '#000000',
                            backgroundColor: 'rgba(255, 222, 21, 0.4)', // Yellow fill
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#FFDE15',
                            pointBorderColor: '#000000',
                            pointBorderWidth: 2.5,
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#FFC107',
                            pointHoverBorderColor: '#000000',
                            pointHoverBorderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#FFDE15',
                                borderWidth: 2,
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold',
                                    family: 'Montserrat'
                                },
                                bodyFont: {
                                    size: 13,
                                    family: 'Montserrat'
                                },
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: Math.max(8, Math.ceil(Math.max(...hours, 1) / 4) * 4),
                                ticks: {
                                    stepSize: 4,
                                    callback: function(value) {
                                        return value;
                                    },
                                    font: {
                                        size: 13,
                                        weight: 'bold',
                                        family: 'Montserrat'
                                    },
                                    color: '#000000',
                                    padding: 10
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)',
                                    lineWidth: 1.5,
                                    drawBorder: true,
                                    borderColor: '#000000',
                                    borderWidth: 1
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 12,
                                        weight: 'bold',
                                        family: 'Montserrat'
                                    },
                                    color: '#000000',
                                    padding: 8
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
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
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
    @endpush
</x-app-layout>
