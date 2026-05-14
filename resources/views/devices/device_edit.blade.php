{{-- 
    Device Management: Edit Device Form
    
    This view displays a form for editing an existing device.
    Form includes fields for: name, MAC address, status, remaining time (child devices).
    Also shows device statistics (connection status, sessions, logs).
    
    Design Reference: Similar to create, but with additional statistics
    
    Data Flow:
    1. User visits /devices/{device}/edit
    2. Form submits to DeviceController@update
    3. DeviceController validates and updates device
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
                    EDIT DEVICE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

            <x-collapsible-instructions class="mb-6">
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>Update the <strong>name</strong>, <strong>Wi‑Fi address (MAC)</strong>, <strong>role</strong>, and <strong>status</strong> when something changes for this device.</li>
                    <li><strong>Active</strong>: normal rules apply (schedules and time limits for children, and so on).</li>
                    <li><strong>Blocked</strong>: this device cannot use the internet. <strong>Whitelisted</strong>: treated as trusted, without the usual child limits.</li>
                    <li><strong>Remaining time</strong> is in minutes and matters most for child-style devices—set it here, then tap <strong>Update device</strong>.</li>
                    <li>Red input border or an asterisk (*) in the label means required. Fill it in until it turns green.</li>
                </ul>
            </x-collapsible-instructions>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {{-- Statistics Cards --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Connection Status</h3>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($isConnected)
                            <span class="text-green-600">Connected</span>
                        @else
                            <span class="text-gray-500">Disconnected</span>
                        @endif
                    </p>
                    @if($isConnected && $deviceIp)
                        <p class="text-sm text-gray-500 mt-1">IP: {{ $deviceIp }}</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Sessions</h3>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['sessions_count'] ?? 0 }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Browsing Logs</h3>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats['logs_count'] ?? 0 }}</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('accounts.update', $device) }}" method="POST" id="deviceForm">
                        @csrf
                        @method('PUT')

                        {{-- Device Name --}}
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Device Name *</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $device->name) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- MAC Address --}}
                        <div class="mb-6">
                            <label for="mac_address" class="block text-sm font-medium text-gray-700 mb-2">MAC Address *</label>
                            <input type="text" name="mac_address" id="mac_address" value="{{ old('mac_address', $device->mac_address) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono"
                                onblur="normalizeMacAddress(this)">
                            <p class="mt-1 text-sm text-gray-500">Format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX</p>
                            @error('mac_address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Device Role --}}
                        <div class="mb-6">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Assigned Role *</label>
                            <select name="role" id="role" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="child" {{ old('role', $device->role ?? 'child') === 'child' ? 'selected' : '' }}>CHILD</option>
                                <option value="guest" {{ old('role', $device->role ?? 'child') === 'guest' ? 'selected' : '' }}>GUEST</option>
                                <option value="parent" {{ old('role', $device->role ?? 'child') === 'parent' ? 'selected' : '' }}>PARENT</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Device role on the network (not your dashboard account). Child/guest use the portal only.</p>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(($device->role ?? 'child') === 'child')
                            <div class="mb-6 p-4 rounded-md border border-yellow-200 bg-yellow-50">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Child portal</h3>
                                <p class="text-sm text-gray-600 mb-4">Assign quizzes and videos to this device on their pages. Optionally pin what appears first on the portal.</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="preferred_quiz_id" class="block text-sm font-medium text-gray-700 mb-2">Preferred quiz on portal (optional)</label>
                                        <select name="preferred_quiz_id" id="preferred_quiz_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                            <option value="">— None —</option>
                                            @foreach($portalFavoriteQuizzes as $pq)
                                                <option value="{{ $pq->id }}" {{ (int) old('preferred_quiz_id', $device->preferred_quiz_id) === (int) $pq->id ? 'selected' : '' }}>{{ $pq->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('preferred_quiz_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="preferred_video_id" class="block text-sm font-medium text-gray-700 mb-2">Preferred video on portal (optional)</label>
                                        <select name="preferred_video_id" id="preferred_video_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                            <option value="">— None —</option>
                                            @foreach($portalFavoriteVideos as $pv)
                                                <option value="{{ $pv->id }}" {{ (int) old('preferred_video_id', $device->preferred_video_id) === (int) $pv->id ? 'selected' : '' }}>{{ $pv->title }}</option>
                                            @endforeach
                                        </select>
                                        @error('preferred_video_id')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Device Status --}}
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Device Status *</label>
                            <select name="status" id="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="active" {{ old('status', $device->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="blocked" {{ old('status', $device->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                <option value="whitelisted" {{ old('status', $device->status) === 'whitelisted' ? 'selected' : '' }}>Whitelisted</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remaining time (parents adjust quota here; total_time_allocated stays in DB for grants/reporting) --}}
                        <div class="mb-6">
                            <label for="remaining_time_minutes" class="block text-sm font-medium text-gray-700 mb-2">Remaining Time (minutes)</label>
                            <input type="number" name="remaining_time_minutes" id="remaining_time_minutes" value="{{ old('remaining_time_minutes', $device->remaining_time_minutes) }}" min="0" max="9999"
                                class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('remaining_time_minutes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Additional Statistics --}}
                        <div class="mb-6 p-4 bg-gray-50 rounded-md">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Additional Statistics</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Active Sessions:</span>
                                    <span class="font-semibold text-gray-900 ml-2">{{ $stats['active_sessions_count'] ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Access Attempts:</span>
                                    <span class="font-semibold text-gray-900 ml-2">{{ $stats['access_attempts_count'] ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Quiz Attempts:</span>
                                    <span class="font-semibold text-gray-900 ml-2">{{ $stats['quiz_attempts_count'] ?? 0 }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Video Completions:</span>
                                    <span class="font-semibold text-gray-900 ml-2">{{ $stats['video_completions_count'] ?? 0 }}</span>
                                </div>
                            </div>
                            @if($device->last_seen_at)
                                <div class="mt-3 text-sm">
                                    <span class="text-gray-500">Last Seen:</span>
                                    <span class="font-semibold text-gray-900 ml-2">{{ $device->last_seen_at->format('M d, Y H:i') }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('accounts.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                Update Device
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
            bindRequiredFieldFeedback();
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

