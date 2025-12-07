{{-- 
    Device Management: Child Devices Stats View (Image 3)
    
    This view displays statistics for a selected device:
    - TIME USAGE graph (line graph showing hours per month)
    - QUIZ SCORE list (all quiz attempts with scores)
    - WEBSITE HISTORY list (recently visited websites)
    
    Based on design reference Image 3: "CHILD DEVICES" tab with stats.
    
    Layout Structure (from Image 3):
    - Yellow header bar with left arrow icon and "CHILD DEVICES" title (with smartphone icon)
    - Child dropdown selector (filter by device)
    - Card 1: TIME USAGE (line graph + time offline/online table)
    - Card 2: QUIZ SCORE (list of quiz scores)
    - Card 3: WEBSITE HISTORY (list of visited websites)
    
    Data Flow:
    1. DeviceController@index fetches device data and statistics
    2. Passes $devices, $device, $timeUsageData, $quizScores, $websiteHistory to this view
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">TIME USAGE</h3>
                            
                            {{-- Time usage graph and table --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Line graph (simplified - in production, use Chart.js or similar) --}}
                                <div>
                                    <canvas id="timeUsageChart" width="400" height="200"></canvas>
                                </div>
                                
                                {{-- Time offline/online table (matching Image 3) --}}
                                <div>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">TIME OFFLINE</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">TIME ONLINE</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            {{-- Example time ranges (in production, calculate from DeviceSession data) --}}
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">8:00 AM - 9:00 AM</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">9:00 AM - 10:00 AM</td>
                                            </tr>
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">10:00 AM - 11:00 AM</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">11:00 AM - 12:00 PM</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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
                    <div class="bg-gray-100 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">WEBSITE HISTORY</h3>
                            
                            @if(count($websiteHistory) > 0)
                                <div class="space-y-2">
                                    @foreach($websiteHistory as $domain)
                                        <div class="p-3 bg-white rounded">
                                            <span class="text-sm text-gray-900">{{ $domain }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No website history yet.</p>
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

    {{-- Chart.js script for time usage graph --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        @if($device && isset($timeUsageData))
        // Time usage graph (matching Image 3 design)
        (function() {
            const timeUsageCanvas = document.getElementById('timeUsageChart');
            if (timeUsageCanvas && typeof Chart !== 'undefined') {
                // Destroy existing chart if it exists
                if (window.timeUsageChartInstance) {
                    window.timeUsageChartInstance.destroy();
                }
                
                window.timeUsageChartInstance = new Chart(timeUsageCanvas, {
                    type: 'line',
                    data: {
                        labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
                        datasets: [{
                            label: 'Hours',
                            data: [
                                {{ $timeUsageData['JAN'] ?? 0 }},
                                {{ $timeUsageData['FEB'] ?? 0 }},
                                {{ $timeUsageData['MAR'] ?? 0 }},
                                {{ $timeUsageData['APR'] ?? 0 }},
                                {{ $timeUsageData['MAY'] ?? 0 }},
                                {{ $timeUsageData['JUN'] ?? 0 }},
                                {{ $timeUsageData['JUL'] ?? 0 }},
                                {{ $timeUsageData['AUG'] ?? 0 }},
                                {{ $timeUsageData['SEP'] ?? 0 }},
                                {{ $timeUsageData['OCT'] ?? 0 }},
                                {{ $timeUsageData['NOV'] ?? 0 }},
                                {{ $timeUsageData['DEC'] ?? 0 }}
                            ],
                            borderColor: '#FFDE15',
                            backgroundColor: 'rgba(255, 222, 21, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 8,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        })();
        @endif
    </script>
    @endpush
</x-app-layout>

