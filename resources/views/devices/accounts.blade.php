{{-- 
    Device Management: Accounts View (Image 4)
    
    This view displays the main device management table showing all devices.
    Based on design reference Image 4: "ACCOUNTS" tab with device management table.
    
    Layout Structure (from Image 4):
    - Yellow header bar with left arrow icon and "ACCOUNTS" title (with person icon)
    - Action buttons (top right): Blocklist, Whitelist, + New
    - Main table with columns: DEVICES MAC ADDRESS, ASSIGNED ROLES, NAME
    - Table rows showing MAC addresses, role buttons, and device names
    
    Data Flow:
    1. DeviceController@accounts fetches devices from database
    2. Passes $devices collection to this view
    3. View loops through devices and displays in table
    4. Action buttons link to create, edit, delete routes
    
    Design Reference: Image 4 - "ACCOUNTS" tab
--}}
<style>
    [x-cloak] { 
        display: none !important; 
    }
</style>
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway (Image 4) --}}
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                {{-- Left arrow icon (back button) --}}
                <a href="{{ route('dashboard') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                {{-- Person icon (for Accounts) --}}
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    ACCOUNTS
                </h2>
            </div>
            {{-- Action buttons: Blocklist, Whitelist, + New (matching Image 4) --}}
            <div class="flex space-x-2">
                {{-- Blocklist button (white, no dropdown arrow - navigates to blocklist page) --}}
                <a href="{{ route('accounts.blocklist') }}" class="px-4 py-2 rounded text-black font-medium hover:opacity-90 bg-white border border-gray-300">
                    <span>Blocklist</span>
                </a>
                {{-- Whitelist button (white, no dropdown arrow - navigates to whitelist page) --}}
                <a href="{{ route('accounts.whitelist') }}" class="px-4 py-2 rounded text-black font-medium hover:opacity-90 bg-white border border-gray-300">
                    <span>Whitelist</span>
                </a>
                {{-- + New button (red, with plus icon) --}}
                <a href="{{ route('accounts.create') }}" class="px-4 py-2 rounded text-white font-medium hover:opacity-90 flex items-center space-x-1" style="background-color: #EF4444;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>New</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success/Error flash messages --}}
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

            {{-- Main device management table (matching Image 4 design) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    @if($devices->count() > 0)
                        {{-- Device table: displays MAC addresses, roles, and names --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        {{-- Table headers matching Image 4 --}}
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            DEVICES MAC ADDRESS
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ASSIGNED ROLES
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            NAME
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            STATUS
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ACTIONS
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($devices as $device)
                                        <tr class="hover:bg-gray-50">
                                            {{-- MAC Address column --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-mono text-gray-900">
                                                    {{ $device->mac_address }}
                                                </div>
                                            </td>
                                            {{-- Assigned Roles column - Simple text display --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ strtoupper($device->role ?? 'CHILD') }}
                                                </span>
                                            </td>
                                            {{-- Name column --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $device->name }}
                                                </div>
                                            </td>
                                            {{-- Status column --}}
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($device->status === 'active')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">Active</span>
                                                @elseif($device->status === 'blocked')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #EF4444; color: white;">Blocked</span>
                                                @elseif($device->status === 'whitelisted')
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #3B82F6; color: white;">Whitelisted</span>
                                                @endif
                                            </td>
                                            {{-- Actions column --}}
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-4">
                                                    {{-- Edit button --}}
                                                    <a href="{{ route('accounts.edit', $device) }}" class="px-3 py-1 rounded text-white font-medium hover:opacity-90" style="background-color: #3B82F6;">
                                                        Edit
                                                    </a>
                                                    {{-- Delete button --}}
                                                    <form action="{{ route('accounts.destroy', $device) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this device?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- Empty state: no devices found --}}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No devices</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new device.</p>
                            <div class="mt-6">
                                <a href="{{ route('accounts.create') }}" class="inline-flex items-center px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Device
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

