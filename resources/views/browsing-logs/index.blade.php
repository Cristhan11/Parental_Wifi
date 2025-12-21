{{-- 
    Browsing Logs: Index View
    
    This view displays all browsing history (website visits) for the authenticated user's devices.
    Browsing logs are automatically created by the ParseNetworkLogs background job, which
    parses network traffic logs and stores website visits in the database.
    
    What are Browsing Logs?
    - A browsing log is a record of every website a child device visits
    - Contains: URL, domain, timestamp, bandwidth usage, and device information
    - Logs are created automatically (no manual entry needed)
    - Parents can view these logs to monitor their children's internet activity
    
    View Structure:
    - Header: Yellow bar with "BROWSING LOGS" title and back button
    - Info Banner: Explains what browsing logs are and how they're collected
    - Filters: Device dropdown, date range picker, search input
    - Table: Displays browsing logs with device, URL, domain, visited at, bandwidth
    - Pagination: Links to navigate through multiple pages of results
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway --}}
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                {{-- Back button - links back to Child Devices page --}}
                <a href="{{ route('child_devices.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                {{-- Eye icon (for browsing logs/viewing) --}}
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    BROWSING LOGS
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success/Error Messages --}}
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

            {{-- Info Banner: Explains what browsing logs are --}}
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>What are Browsing Logs?</strong> Browsing logs are automatically created records of every website 
                    your child's device visits. They are collected by the ParseNetworkLogs background job (runs every 10 minutes) 
                    which parses network traffic logs. You can use these logs to monitor your child's internet activity and 
                    ensure they're following your rules.
                </p>
            </div>

            {{-- Filters Section: Device, Date Range, Search --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mb-4 p-4">
                <form method="GET" action="{{ route('browsing-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Device Filter: Dropdown to select specific device --}}
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
                    {{-- Date Range Filter: From Date --}}
                    <div>
                        <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    {{-- Date Range Filter: To Date --}}
                    <div>
                        <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    {{-- Search Filter: Search by domain or URL --}}
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            placeholder="Domain or URL..." 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    {{-- Filter Button: Submit the form to apply filters --}}
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            {{-- Browsing Logs Table: Displays all browsing history --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    @if($browsingLogs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visited At</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bandwidth</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($browsingLogs as $log)
                                        <tr>
                                            {{-- Device Name: Shows which device visited this site --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->device->name }}
                                            </td>
                                            {{-- URL: Clickable link to the visited website (truncated if too long) --}}
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <a href="{{ $log->url }}" target="_blank" class="text-blue-600 hover:underline" title="{{ $log->url }}">
                                                    {{ Str::limit($log->url, 50) }}
                                                </a>
                                            </td>
                                            {{-- Domain: The base domain of the website (e.g., example.com) --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="font-mono">{{ $log->domain }}</span>
                                            </td>
                                            {{-- Visited At: Formatted timestamp showing when the site was visited --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $log->visited_at->format('M d, Y H:i') }}
                                            </td>
                                            {{-- Bandwidth: Total data transferred (formatted as KB/MB/GB) --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $log->getTotalBandwidthFormatted() }}
                                            </td>
                                            {{-- User Agent: Browser/device information (truncated if too long) --}}
                                            <td class="px-6 py-4 text-sm text-gray-500" title="{{ $log->user_agent }}">
                                                {{ $log->user_agent ? Str::limit($log->user_agent, 40) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- Pagination Links: Navigate through multiple pages of results --}}
                        <div class="mt-4">
                            {{ $browsingLogs->links() }}
                        </div>
                    @else
                        {{-- Empty State: No browsing logs found --}}
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">No browsing logs found.</p>
                            <p class="text-sm text-gray-400">Browsing logs are automatically created when devices visit websites. 
                            If you don't see any logs, make sure the ParseNetworkLogs job is running and devices have visited websites.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

