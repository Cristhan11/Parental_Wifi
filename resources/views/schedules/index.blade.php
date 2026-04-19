{{-- 
    Device Schedules: Index View
    
    This view displays all schedules for the authenticated user's devices.
    Schedules define time-based internet access rules.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <a href="{{ route('dashboard') }}" class="shrink-0 text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <svg class="h-6 w-6 shrink-0 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="min-w-0 font-semibold text-base leading-tight text-black sm:text-xl">
                    DEVICE SCHEDULES
                </h2>
            </div>
            <div class="flex shrink-0 items-center justify-end sm:pl-2">
                <a href="{{ route('schedules.create') }}" class="inline-flex items-center gap-1 rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:px-4 sm:text-base whitespace-nowrap" style="background-color: #EF4444;">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Create Schedule</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 rounded-lg" style="background-color: #10B981; color: white;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 rounded-lg" style="background-color: #EF4444; color: white;">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Info Banner --}}
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>Note:</strong> Schedules define when devices can access the internet. The system automatically 
                    enforces these schedules every minute. Devices are blocked when outside allowed time windows or when 
                    daily duration limits are reached.
                </p>
            </div>

            {{-- Filters --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mb-4 p-4">
                <form method="GET" action="{{ route('schedules.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="device_id" class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                        <select name="device_id" id="device_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="day_of_week" class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                        <select name="day_of_week" id="day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Days</option>
                            <option value="monday" {{ request('day_of_week') == 'monday' ? 'selected' : '' }}>Monday</option>
                            <option value="tuesday" {{ request('day_of_week') == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="wednesday" {{ request('day_of_week') == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="thursday" {{ request('day_of_week') == 'thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="friday" {{ request('day_of_week') == 'friday' ? 'selected' : '' }}>Friday</option>
                            <option value="saturday" {{ request('day_of_week') == 'saturday' ? 'selected' : '' }}>Saturday</option>
                            <option value="sunday" {{ request('day_of_week') == 'sunday' ? 'selected' : '' }}>Sunday</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="is_active" id="is_active" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            {{-- Schedules Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    @if($schedules->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Window</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration Limit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($schedules as $schedule)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $schedule->device->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ ucfirst($schedule->day_of_week) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $schedule->start_time->format('g:i A') }} - 
                                                {{ $schedule->end_time->format('g:i A') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($schedule->duration_limit_minutes)
                                                    {{ floor($schedule->duration_limit_minutes / 60) }}h {{ $schedule->duration_limit_minutes % 60 }}m
                                                @else
                                                    <span class="text-gray-400">No limit</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($schedule->is_active)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-400 text-white">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('schedules.edit', $schedule) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                                <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $schedules->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">No schedules found.</p>
                            <a href="{{ route('schedules.create') }}" class="inline-block px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                                Create Your First Schedule
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

