{{-- 
    Access Attempts: Index View
    
    This view displays all security events (access attempts) for the authenticated user's devices.
    Access attempts are automatically created by the system when:
    1. A child tries to access a blocked website (type: 'blocked_website') - access denied
    2. A child visits a flagged website (type: 'flagged_website') - access allowed but logged
    
    What are Access Attempts?
    - Access attempts are security events that track when children interact with blocked/flagged websites
    - Blocked Website Attempt: Child tried to access a blocked site (access denied, parent notified)
    - Flagged Website Visit: Child visited a flagged site (access allowed, but logged for parent review)
    - These events help parents monitor compliance and security
    
    View Structure:
    - Header: Yellow bar with "ACCESS ATTEMPTS" title and back button
    - Info Banner: Explains what access attempts are and why parents should monitor them
    - Filters: Device dropdown, type dropdown, date range picker, search input
    - Table: Displays access attempts with device, type badge, URL, domain, attempted at
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
                {{-- Shield icon (for security/access attempts) --}}
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    ACCESS ATTEMPTS
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

            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    @if(request()->filled('device_id'))
                        <li>The list below is filtered to <strong>one child</strong>. Use the <strong>Device</strong> filter to pick another child or <strong>All devices</strong>.</li>
                    @endif
                    <li>This page shows when a child tried a <strong>blocked</strong> site or visited a <strong>flagged</strong> site you are watching.</li>
                    <li><strong>Blocked</strong>: the site did not open. <strong>Flagged</strong>: the site opened and the visit is saved for your reports.</li>
                    <li>Use <strong>Device</strong>, <strong>Type</strong>, dates, and <strong>Search</strong> to narrow the list, then tap <strong>Filter</strong>.</li>
                </ul>
            </x-collapsible-instructions>

            {{-- Filters Section: Device, Type, Date Range, Search --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mb-4 p-4">
                <form method="GET" action="{{ route('access-attempts.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                    {{-- Type Filter: Dropdown to filter by attempt type (blocked or flagged) --}}
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Types</option>
                            <option value="blocked_website" {{ request('type') == 'blocked_website' ? 'selected' : '' }}>Blocked Website</option>
                            <option value="flagged_website" {{ request('type') == 'flagged_website' ? 'selected' : '' }}>Flagged Website</option>
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

            {{-- Access Attempts Table: Displays all security events --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    @if($accessAttempts->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempted At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($accessAttempts as $attempt)
                                        <tr>
                                            {{-- Device Name: Shows which device made this attempt --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $attempt->device->name }}
                                            </td>
                                            {{-- Type Badge: Visual indicator of attempt type (red for blocked, yellow for flagged) --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($attempt->type === 'blocked_website')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #EF4444; color: white;">
                                                        Blocked
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #F59E0B; color: white;">
                                                        Flagged
                                                    </span>
                                                @endif
                                            </td>
                                            {{-- URL: Clickable link to the attempted website (truncated if too long) --}}
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <a href="{{ $attempt->url }}" target="_blank" class="text-blue-600 hover:underline" title="{{ $attempt->url }}">
                                                    {{ Str::limit($attempt->url, 50) }}
                                                </a>
                                            </td>
                                            {{-- Domain: The base domain of the website (e.g., example.com) --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="font-mono">{{ $attempt->domain }}</span>
                                            </td>
                                            {{-- Attempted At: Formatted timestamp showing when the attempt occurred --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $attempt->attempted_at->format('M d, Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{-- Pagination Links: Navigate through multiple pages of results --}}
                        <div class="mt-4">
                            {{ $accessAttempts->links() }}
                        </div>
                    @else
                        {{-- Empty State: No access attempts found --}}
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">No access attempts found.</p>
                            <p class="text-sm text-gray-400">Access attempts are automatically created when children try to access 
                            blocked websites or visit flagged websites. If you don't see any attempts, it means your children 
                            haven't interacted with blocked or flagged sites yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

