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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                {{-- Left arrow icon (back button) --}}
                <a href="{{ route('dashboard') }}" class="shrink-0 text-black hover:opacity-75">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                {{-- Person icon (for Accounts) --}}
                <svg class="h-6 w-6 shrink-0 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h2 class="min-w-0 text-base font-semibold leading-tight text-black sm:text-xl">
                    ACCOUNTS
                </h2>
            </div>
            {{-- Action buttons: Blocklist, Whitelist, + New (matching Image 4) --}}
            <div class="flex w-full min-w-0 flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-2">
                {{-- Blocklist button (white, no dropdown arrow - navigates to blocklist page) --}}
                <a href="{{ route('accounts.blocklist') }}" class="inline-flex w-full items-center justify-center rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-black hover:opacity-90 sm:w-auto sm:px-4 sm:text-base">
                    <span>Blocklist</span>
                </a>
                {{-- Whitelist button (white, no dropdown arrow - navigates to whitelist page) --}}
                <a href="{{ route('accounts.whitelist') }}" class="inline-flex w-full items-center justify-center rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-black hover:opacity-90 sm:w-auto sm:px-4 sm:text-base">
                    <span>Whitelist</span>
                </a>
                {{-- Registration button with pending-count badge --}}
                <a href="{{ route('accounts.create') }}" class="relative inline-flex w-full items-center justify-center rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base" style="background-color: #EF4444;">
                    <span>Registration</span>
                    @if(($pendingRegistrationCount ?? 0) > 0)
                        <span class="pointer-events-none z-10 inline-flex h-6 min-w-[24px] items-center justify-center rounded-full px-1.5 text-xs font-bold leading-none text-white shadow-md"
                              style="position:absolute; top:-8px; right:-8px; background-color:#2563EB;">
                            {{ $pendingRegistrationCount > 99 ? '99+' : $pendingRegistrationCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">
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

            @if(session('info'))
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    {{ session('info') }}
                </div>
            @endif

            <x-policy-apply-status />

            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>This page shows all connected devices in your home network.</li>
                    <li>Use <strong>Edit</strong> to update the device name, role, and settings.</li>
                    <li>Use <strong>Blocklist</strong> for devices you want to stop from being connected to the internet.</li>
                    <li>Use <strong>Whitelist</strong> for trusted devices that should stay allowed to connect to the internet.</li>
                    <li>Use <strong>Registration</strong> to review child device registration requests.</li>
                    <li>The blue badge on <strong>Registration</strong> shows how many pending requests need action.</li>
                </ul>
            </x-collapsible-instructions>

            {{-- Main device management table (matching Image 4 design) --}}
            <div class="min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="min-w-0 p-4 sm:p-6">
                    @if($devices->count() > 0)
                        {{-- Device table: displays MAC addresses, roles, and names --}}
                        <div class="-mx-4 min-w-0 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                            <table class="min-w-[640px] w-full table-fixed divide-y divide-gray-200 sm:min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        {{-- Table headers matching Image 4 --}}
                                        <th class="w-[28%] px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500 sm:w-auto sm:px-6 sm:py-3 sm:text-xs sm:tracking-wider">
                                            <span class="sm:hidden">MAC</span><span class="hidden sm:inline">DEVICES MAC ADDRESS</span>
                                        </th>
                                        <th class="w-[14%] px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500 sm:w-auto sm:px-6 sm:py-3 sm:text-xs sm:tracking-wider">
                                            <span class="sm:hidden">Role</span><span class="hidden sm:inline">ASSIGNED ROLES</span>
                                        </th>
                                        <th class="w-[18%] px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500 sm:w-auto sm:px-6 sm:py-3 sm:text-xs sm:tracking-wider">
                                            Name
                                        </th>
                                        <th class="w-[16%] px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500 sm:w-auto sm:px-6 sm:py-3 sm:text-xs sm:tracking-wider">
                                            Status
                                        </th>
                                        <th class="w-[24%] px-2 py-2 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500 sm:w-auto sm:px-6 sm:py-3 sm:text-xs sm:tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($devices as $device)
                                        <tr class="hover:bg-gray-50">
                                            {{-- MAC Address column --}}
                                            <td class="px-2 py-3 align-top sm:px-6 sm:py-4">
                                                <div class="break-all font-mono text-xs text-gray-900 sm:text-sm">
                                                    {{ $device->mac_address }}
                                                </div>
                                            </td>
                                            {{-- Assigned Roles column - Simple text display --}}
                                            <td class="px-2 py-3 align-top sm:px-6 sm:py-4">
                                                <span class="text-xs font-medium text-gray-900 sm:text-sm">
                                                    {{ strtoupper($device->role ?? 'CHILD') }}
                                                </span>
                                            </td>
                                            {{-- Name column --}}
                                            <td class="px-2 py-3 align-top sm:px-6 sm:py-4">
                                                <div class="break-words text-xs font-medium text-gray-900 sm:text-sm">
                                                    {{ $device->name }}
                                                </div>
                                            </td>
                                            {{-- Status column --}}
                                            <td class="px-2 py-3 align-top sm:px-6 sm:py-4">
                                                @if($device->status === 'active')
                                                    <span class="inline-block rounded-full px-2 py-1 text-[10px] font-semibold sm:text-xs" style="background-color: #10B981; color: white;">Active</span>
                                                @elseif($device->status === 'blocked')
                                                    <span class="inline-block rounded-full px-2 py-1 text-[10px] font-semibold sm:text-xs" style="background-color: #EF4444; color: white;">Blocked</span>
                                                @elseif($device->status === 'whitelisted')
                                                    <span class="inline-block rounded-full px-2 py-1 text-[10px] font-semibold sm:text-xs" style="background-color: #3B82F6; color: white;">Whitelisted</span>
                                                @endif
                                            </td>
                                            {{-- Actions column --}}
                                            <td class="px-2 py-3 align-top text-xs font-medium sm:px-6 sm:py-4 sm:text-sm">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2">
                                                    {{-- Edit button --}}
                                                    <a href="{{ route('accounts.edit', $device) }}" class="inline-flex justify-center rounded px-2 py-1 text-center text-white hover:opacity-90 sm:px-3" style="background-color: #3B82F6;">
                                                        Edit
                                                    </a>
                                                    {{-- Delete button --}}
                                                    <form action="{{ route('accounts.destroy', $device) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this device?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="w-full rounded px-2 py-1 text-white hover:opacity-90 sm:w-auto sm:px-3" style="background-color: #EF4444;">
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
                                <a href="{{ route('accounts.create') }}" class="relative inline-flex items-center px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                    Registration
                                    @if(($pendingRegistrationCount ?? 0) > 0)
                                        <span class="pointer-events-none z-10 inline-flex h-6 min-w-[24px] items-center justify-center rounded-full px-1.5 text-xs font-bold leading-none text-white shadow-md"
                                              style="position:absolute; top:-8px; right:-8px; background-color:#2563EB;">
                                            {{ $pendingRegistrationCount > 99 ? '99+' : $pendingRegistrationCount }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[action*="registration-requests"]').forEach(function (form) {
            const roleSelect = form.querySelector('select[name="assigned_role"]');
            const approveBtn = form.querySelector('button[type="submit"]');
            if (!roleSelect || !approveBtn) return;

            const sync = function () {
                const hasRole = roleSelect.value !== '';
                approveBtn.disabled = !hasRole;
                approveBtn.classList.toggle('opacity-50', !hasRole);
                approveBtn.classList.toggle('cursor-not-allowed', !hasRole);
            };

            roleSelect.addEventListener('change', sync);
            sync();
        });
    });
</script>
@endpush

