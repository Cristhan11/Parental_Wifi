{{-- 
    Device Schedules: Edit Form
    
    This view displays a form for editing an existing device schedule.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('schedules.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    EDIT SCHEDULE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 p-4 rounded-lg" style="background-color: #EF4444; color: white;">
                    <p class="font-semibold mb-2">Please fix the following errors:</p>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-collapsible-instructions class="mb-4">
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>Schedules set when this device may use the internet for the day you pick.</li>
                    <li>End time must be after start time. Daily minutes limit is optional.</li>
                    <li>Turn off <strong>Active</strong> to pause this rule without deleting it.</li>
                    <li>Red input border or an asterisk (*) in the label means required. Fill it in until it turns green.</li>
                </ul>
            </x-collapsible-instructions>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('schedules.update', $schedule) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Device Selection --}}
                        <div class="mb-6">
                            <label for="device_id" class="block text-sm font-medium text-gray-700 mb-2">Device *</label>
                            <select name="device_id" id="device_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ old('device_id', $schedule->device_id) == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }} ({{ $device->mac_address }})
                                    </option>
                                @endforeach
                            </select>
                            @error('device_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Day of Week --}}
                        <div class="mb-6">
                            <label for="day_of_week" class="block text-sm font-medium text-gray-700 mb-2">Day of Week *</label>
                            <select name="day_of_week" id="day_of_week" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="monday" {{ old('day_of_week', $schedule->day_of_week) == 'monday' ? 'selected' : '' }}>Monday</option>
                                <option value="tuesday" {{ old('day_of_week', $schedule->day_of_week) == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                                <option value="wednesday" {{ old('day_of_week', $schedule->day_of_week) == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                                <option value="thursday" {{ old('day_of_week', $schedule->day_of_week) == 'thursday' ? 'selected' : '' }}>Thursday</option>
                                <option value="friday" {{ old('day_of_week', $schedule->day_of_week) == 'friday' ? 'selected' : '' }}>Friday</option>
                                <option value="saturday" {{ old('day_of_week', $schedule->day_of_week) == 'saturday' ? 'selected' : '' }}>Saturday</option>
                                <option value="sunday" {{ old('day_of_week', $schedule->day_of_week) == 'sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                            @error('day_of_week')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Time Window --}}
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                                <input type="time" name="start_time" id="start_time" 
                                    value="{{ old('start_time', $schedule->start_time->format('H:i')) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('start_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                                <input type="time" name="end_time" id="end_time" 
                                    value="{{ old('end_time', $schedule->end_time->format('H:i')) }}" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('end_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-6">Enter the time window when internet access is allowed (e.g., 3:00 PM - 9:00 PM)</p>

                        {{-- Duration Limit --}}
                        <div class="mb-6">
                            <label for="duration_limit_minutes" class="block text-sm font-medium text-gray-700 mb-2">Daily Duration Limit (Optional)</label>
                            <div class="flex items-center space-x-2">
                                <input type="number" name="duration_limit_minutes" id="duration_limit_minutes" 
                                    value="{{ old('duration_limit_minutes', $schedule->duration_limit_minutes) }}" min="1" max="1440"
                                    class="w-32 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                    placeholder="120">
                                <span class="text-sm text-gray-500">minutes (leave empty for no limit)</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Maximum time allowed per day (e.g., 120 = 2 hours). Leave empty for no daily limit.</p>
                            @error('duration_limit_minutes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Active Status --}}
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                <span class="ml-2 text-sm text-gray-700">Active (schedule will be enforced)</span>
                            </label>
                            @error('is_active')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('schedules.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                                Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        // Client-side validation for time comparison
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime) {
                // Convert time strings to comparable format
                const start = new Date('2000-01-01T' + startTime + ':00');
                const end = new Date('2000-01-01T' + endTime + ':00');
                
                if (end <= start) {
                    e.preventDefault();
                    alert('End time must be after start time.');
                    document.getElementById('end_time').focus();
                    return false;
                }
            }
        });
    </script>
</x-app-layout>

