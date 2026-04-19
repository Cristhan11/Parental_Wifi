{{-- 
    Device Management: Create Device Form
    
    This view displays a form for creating a new device.
    Form includes fields for: name, MAC address, role, status, time allocation.
    
    Design Reference: Follow Accounts tab design pattern (Image 4)
    
    Data Flow:
    1. User visits /devices/create
    2. Form submits to DeviceController@store
    3. DeviceController validates and creates device
    4. Redirects to accounts view with success message
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway --}}
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounts.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    ADD DEVICE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('accounts.store') }}" method="POST" id="deviceForm">
                        @csrf

                        {{-- Device Name --}}
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Device Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="e.g., John's iPhone">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- MAC Address --}}
                        <div class="mb-6">
                            <label for="mac_address" class="block text-sm font-medium text-gray-700 mb-2">MAC Address *</label>
                            <input type="text" name="mac_address" id="mac_address" value="{{ old('mac_address') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono"
                                placeholder="AA:BB:CC:DD:EE:FF or AA-BB-CC-DD-EE-FF"
                                onblur="normalizeMacAddress(this)">
                            <p class="mt-1 text-sm text-gray-500">Format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX</p>
                            @error('mac_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Connected Devices Helper (optional) --}}
                        @if(isset($connectedDevices) && count($connectedDevices) > 0)
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm font-medium text-blue-900 mb-2">Available Devices on Network:</p>
                                <div class="space-y-1">
                                    @foreach($connectedDevices as $connectedDevice)
                                        <button type="button" onclick="fillMacAddress('{{ $connectedDevice['mac_address'] ?? '' }}')" 
                                            class="text-sm text-blue-600 hover:text-blue-800 underline">
                                            {{ $connectedDevice['mac_address'] ?? 'Unknown' }} - {{ $connectedDevice['ip_address'] ?? 'Unknown IP' }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Device Role --}}
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Assigned Role *</label>
                            <select name="role" id="role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                onchange="toggleTimeAllocationFields()">
                                <option value="child" {{ old('role', 'child') === 'child' ? 'selected' : '' }}>CHILD</option>
                                <option value="guest" {{ old('role', 'child') === 'guest' ? 'selected' : '' }}>GUEST</option>
                                <option value="parent" {{ old('role', 'child') === 'parent' ? 'selected' : '' }}>PARENT</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">This is the <strong>device</strong> role on the network (child / guest / parent device), not your dashboard login type. Child and guest devices use the portal only; they do not sign into the parent dashboard.</p>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Device Status --}}
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Device Status *</label>
                            <select name="status" id="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="blocked" {{ old('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                <option value="whitelisted" {{ old('status') === 'whitelisted' ? 'selected' : '' }}>Whitelisted</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">
                                Active: Device can access internet (subject to time limits)<br>
                                Blocked: Device is blocked from internet access<br>
                                Whitelisted: Device bypasses all restrictions (unlimited access)
                            </p>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Time Allocation (hidden for parent devices; not subject to child time limits) --}}
                        <div id="time-allocation-fields" class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="remaining_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">Initial Time Allocation (minutes)</label>
                                <input type="number" name="remaining_time_minutes" id="remaining_time_minutes" value="{{ old('remaining_time_minutes', 15) }}" min="0" max="9999"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="mt-1 text-sm text-gray-500">Default: 15 minutes</p>
                                @error('remaining_time_minutes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="total_time_allocated" class="block text-sm font-medium text-gray-700 mb-2">Total Time Allocated (minutes)</label>
                                <input type="number" name="total_time_allocated" id="total_time_allocated" value="{{ old('total_time_allocated') }}" min="0" max="9999"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="mt-1 text-sm text-gray-500">Optional: For tracking purposes</p>
                                @error('total_time_allocated')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('accounts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                Save Device
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        /**
         * Normalize MAC address to standard format (XX:XX:XX:XX:XX:XX, uppercase)
         * Called when user leaves MAC address field (onblur event)
         */
        function normalizeMacAddress(input) {
            let mac = input.value.trim();
            // Replace hyphens with colons
            mac = mac.replace(/-/g, ':');
            // Convert to uppercase
            mac = mac.toUpperCase();
            // Update input value
            input.value = mac;
        }

        /**
         * Fill MAC address from connected device list
         * Called when user clicks on a connected device
         */
        function fillMacAddress(mac) {
            document.getElementById('mac_address').value = mac;
            normalizeMacAddress(document.getElementById('mac_address'));
        }

        /**
         * Parent devices do not use initial/total time allocation in the UI.
         */
        function toggleTimeAllocationFields() {
            const roleSelect = document.getElementById('role');
            const block = document.getElementById('time-allocation-fields');
            if (!roleSelect || !block) {
                return;
            }
            const isParent = roleSelect.value === 'parent';
            block.classList.toggle('hidden', isParent);
        }

        document.addEventListener('DOMContentLoaded', toggleTimeAllocationFields);
    </script>
    @endpush
</x-app-layout>

