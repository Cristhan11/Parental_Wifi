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

                        @if(empty($advancedMode))
                            {{-- Success/Error flash messages (same pattern as /accounts) --}}
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

                            <x-collapsible-instructions class="mb-4">
                                <p class="mb-2 font-semibold">Instructions</p>
                                <ul class="list-inside list-disc space-y-1">
                                    <li><strong>Device name</strong>: You can change the name before you approve a request.</li>
                                    <li><strong>Trust</strong>: <em>Seen on Home Wi-Fi</em> means the request came from your home Wi‑Fi. <em>Unverified</em> means it did not come from your home Wi‑Fi.</li>
                                    <li><strong>Initial time</strong>: For a <strong>Child device</strong>, this is the starting internet time in minutes. Parent and guest devices do not use this the same way.</li>
                                    <li><strong>Assigned role</strong>: Pick a role for each row before you approve.</li>
                                    <li><strong>Approve</strong>: Adds the device to your account and clears it from this pending list.</li>
                                    <li><strong>Disapprove</strong>: Turns down the request without adding a device.</li>
                                    <li>To type a Device's MAC address yourself, open <strong>Advanced Options</strong> at the bottom of this page.</li>
                                </ul>
                            </x-collapsible-instructions>

                            <div id="pending-device-requests" class="mb-6 rounded-lg border border-yellow-200 bg-gradient-to-b from-yellow-50 to-white p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-base font-semibold text-gray-900">Pending Device Requests</h3>
                                    <span class="text-xs text-gray-600">{{ isset($pendingRegistrationRequests) ? $pendingRegistrationRequests->count() : 0 }} pending</span>
                                </div>
                                <p class="mb-4 text-sm text-gray-600">Review each row: check trust, set the name if needed, pick initial time and role, then <strong>Approve</strong> or <strong>Disapprove</strong>. When there are no requests, ask the device owner to submit <strong>Request to Register</strong> from the portal first.</p>

                                @if(isset($pendingRegistrationRequests) && $pendingRegistrationRequests->count() > 0)
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full table-fixed divide-y divide-gray-200 bg-white rounded-lg overflow-hidden shadow-sm">
                                            <thead class="bg-gray-50 text-gray-600">
                                                <tr>
                                                    <th class="w-[42%] px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Device Name</th>
                                                    <th class="w-[12%] px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Trust</th>
                                                    <th class="w-[16%] px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Initial Time</th>
                                                    <th class="w-[18%] px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Assigned Role</th>
                                                    <th class="w-[12%] px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach($pendingRegistrationRequests as $pending)
                                                    <tr>
                                                        <td class="w-[42%] px-3 py-3">
                                                            @php $approveFormId = 'approve-request-'.$pending->id; @endphp
                                                                <input type="text"
                                                                       form="{{ $approveFormId }}"
                                                                       name="device_name"
                                                                       value="{{ old('device_name', $pending->device_name) }}"
                                                                       class="w-full rounded-md border-gray-300 px-3 py-2 text-sm font-medium text-gray-900 focus:border-yellow-500 focus:ring-yellow-500"
                                                                       required>
                                                        </td>
                                                        <td class="w-[12%] px-3 py-3 text-sm">
                                                            @if($pending->seen_on_home_wifi)
                                                                <span class="inline-block whitespace-nowrap rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">Seen on Home Wi-Fi</span>
                                                            @else
                                                                <span class="inline-block whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">Unverified</span>
                                                            @endif
                                                        </td>
                                                        <td class="w-[16%] px-3 py-3">
                                                                <select form="{{ $approveFormId }}" name="initial_time_minutes" class="w-full rounded-md border-gray-300 pr-10 text-xs leading-5 focus:border-yellow-500 focus:ring-yellow-500">
                                                                    @for($minutes = 5; $minutes <= 480; $minutes += 5)
                                                                        @php
                                                                            $hours = intdiv($minutes, 60);
                                                                            $remaining = $minutes % 60;
                                                                            $label = $hours > 0
                                                                                ? ($remaining === 0 ? "{$hours} hr" . ($hours > 1 ? 's' : '') : "{$hours} hr {$remaining} min")
                                                                                : "{$minutes} min";
                                                                        @endphp
                                                                        <option value="{{ $minutes }}" {{ $minutes === 60 ? 'selected' : '' }}>
                                                                            {{ $label }} ({{ $minutes }} min)
                                                                        </option>
                                                                    @endfor
                                                                </select>
                                                        </td>
                                                        <td class="w-[18%] px-3 py-3">
                                                                <select form="{{ $approveFormId }}" required name="assigned_role" class="w-full rounded-md border-gray-300 pr-10 text-xs leading-5 focus:border-yellow-500 focus:ring-yellow-500">
                                                                    <option value="">Select role</option>
                                                                    <option value="child">Child Device</option>
                                                                    <option value="parent">Parent Account</option>
                                                                    <option value="guest">Guest Account</option>
                                                                </select>
                                                        </td>
                                                        <td class="w-[12%] px-3 py-3">
                                                                <div class="flex flex-col gap-2">
                                                                    <form id="{{ $approveFormId }}" action="{{ route('accounts.registration-requests.approve', $pending) }}" method="POST" class="pending-request-form">
                                                                        @csrf
                                                                        <button type="submit" class="approve-btn w-24 rounded-md bg-blue-600 px-2 py-2 text-xs font-medium text-white hover:bg-blue-700 transition">
                                                                        Approve
                                                                        </button>
                                                                    </form>
                                                                    <form action="{{ route('accounts.registration-requests.reject', $pending) }}" method="POST">
                                                                        @csrf
                                                                        <button type="submit" class="w-24 rounded-md border border-gray-300 bg-white px-2 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                                                                            Disapprove
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
                                    <p class="rounded-md border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-600">
                                        No pending device requests yet. Ask a child device to tap <strong>Request to Register</strong> from the device portal.
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if(!empty($advancedMode))
                        <form action="{{ route('accounts.store') }}" method="POST" id="deviceForm">
                        @csrf
                        <input type="hidden" name="advanced_mode" value="1">

                        <x-collapsible-instructions class="mb-6">
                            <p class="mb-2 font-semibold">Instructions</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>Use this form to add a device by hand when you know its Wi‑Fi address (MAC) and name.</li>
                                <li>Pick the <strong>role</strong> and <strong>status</strong> that match how this device should behave on your network.</li>
                                <li>For child devices, set <strong>Initial time</strong> and <strong>Total time</strong> in minutes if you use time limits.</li>
                                <li>Tap <strong>Save device</strong> when you are finished.</li>
                            </ul>
                        </x-collapsible-instructions>

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

                        {{-- Connected Devices Helper (advanced/debug only) --}}
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
                        @else
                            <div class="flex justify-end">
                                <a href="{{ route('accounts.create.advanced') }}"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-gray-500 text-sm hover:bg-gray-50"
                                   title="Advanced/debug manual MAC entry">
                                    Advanced Options
                                </a>
                            </div>
                        @endif
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
            const field = document.getElementById('mac_address');
            if (!field) {
                return;
            }
            field.value = mac;
            normalizeMacAddress(field);
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

        function setRequiredFieldState(field) {
            if (!field || field.type === 'hidden' || field.disabled) return;
            if (!field.hasAttribute('required')) return;

            const hasValue = String(field.value ?? '').trim() !== '';
            if (hasValue) {
                field.style.borderColor = '#16A34A';
                field.style.boxShadow = '0 0 0 1px #16A34A';
            } else {
                field.style.borderColor = '#DC2626';
                field.style.boxShadow = '0 0 0 1px #DC2626';
            }
        }

        function bindRequiredFieldFeedback(scope = document) {
            const fields = scope.querySelectorAll('input[required], select[required], textarea[required]');
            fields.forEach((field) => {
                if (field.dataset.requiredBound === '1') return;
                field.dataset.requiredBound = '1';
                setRequiredFieldState(field);
                field.addEventListener('input', () => setRequiredFieldState(field));
                field.addEventListener('change', () => setRequiredFieldState(field));
                field.addEventListener('blur', () => setRequiredFieldState(field));
            });
        }

        function initializeRequiredFeedback() {
            toggleTimeAllocationFields();
            bindRequiredFieldFeedback();
            bindPendingRequestApprovalState();
        }

        function bindPendingRequestApprovalState() {
            document.querySelectorAll('.pending-request-form').forEach((form) => {
                const formId = form.getAttribute('id');
                const roleSelect = formId
                    ? document.querySelector(`select[name="assigned_role"][form="${formId}"]`)
                    : form.querySelector('select[name="assigned_role"]');
                const approveBtn = form.querySelector('.approve-btn');
                if (!roleSelect || !approveBtn) return;

                const sync = () => {
                    const hasRole = roleSelect.value !== '';
                    approveBtn.disabled = !hasRole;
                    approveBtn.classList.toggle('opacity-50', !hasRole);
                    approveBtn.classList.toggle('cursor-not-allowed', !hasRole);
                };

                roleSelect.addEventListener('change', sync);
                sync();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeRequiredFeedback);
        } else {
            initializeRequiredFeedback();
        }
        window.addEventListener('pageshow', initializeRequiredFeedback);
    </script>
    @endpush
</x-app-layout>

