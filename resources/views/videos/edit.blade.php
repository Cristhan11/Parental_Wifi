{{-- Parent Dashboard: Video Edit Form --}}
{{-- Form that allows parents to edit existing videos --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('videos.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    Edit Video: {{ $video->title }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('videos.update', $video) }}" method="POST" enctype="multipart/form-data" id="videoForm">
                        @csrf
                        @method('PUT')

                        {{-- Video File Upload (FIRST - Optional for edit, but detects duration if new file selected) --}}
                        <div class="mb-6">
                            <label for="video_file" class="block text-sm font-medium text-gray-700 mb-2">
                                Replace Video File (Optional)
                            </label>
                            <input type="file" name="video_file" id="video_file" accept="video/mp4,video/webm,video/ogg"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                onchange="handleVideoFileSelect(event)">
                            <p class="mt-1 text-sm text-gray-500">Leave empty to keep current video. Accepted formats: MP4, WebM, OGG. Maximum size: 512MB</p>
                            <p class="mt-1 text-sm text-gray-600">Current video: <strong>{{ basename($video->video_path) }}</strong></p>
                            <div id="video_loading" class="mt-2 text-sm text-blue-600" style="display: none;">
                                <span class="inline-block animate-spin mr-2">⏳</span> Detecting video duration...
                            </div>
                            <div id="video_duration_detected" class="mt-2 text-sm text-green-600" style="display: none;">
                                ✅ Duration detected: <span id="detected_duration_display"></span>
                            </div>
                            @error('video_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Video Metadata: Title, Description (Shown after video is selected) --}}
                        <div class="mb-6" id="metadata_section">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Video Title *</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $video->title) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6" id="description_section">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">{{ old('description', $video->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Duration and Time Reward (Duration auto-filled from video) --}}
                        <div class="grid grid-cols-2 gap-4 mb-6" id="duration_section">
                            <div>
                                <label for="duration_seconds" class="block text-sm font-medium text-gray-700 mb-2">
                                    Duration (seconds) * 
                                    <span class="text-green-600 text-xs" id="auto_detected_badge" style="display: none;">(Auto-detected)</span>
                                </label>
                                <input type="number" name="duration_seconds" id="duration_seconds" value="{{ old('duration_seconds', $video->duration_seconds) }}" min="1" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                    placeholder="Will be auto-filled from video" readonly>
                                <p class="mt-1 text-sm text-gray-500">Automatically detected from video file</p>
                                @error('duration_seconds')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_reward_minutes" class="block text-sm font-medium text-gray-700 mb-2">Time Reward (minutes) *</label>
                                <input type="number" name="time_reward_minutes" id="time_reward_minutes" value="{{ old('time_reward_minutes', $video->time_reward_minutes) }}" min="1" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                @error('time_reward_minutes')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Dictionary Words Settings (Shown after duration is detected) --}}
                        <div class="mb-6" id="dictionary_section">
                            <div class="flex items-center mb-4">
                                <input type="checkbox" name="dictionary_words_enabled" id="dictionary_words_enabled" value="1" 
                                    {{ old('dictionary_words_enabled', $video->dictionary_words_enabled) ? 'checked' : '' }}
                                    onchange="toggleWordCount()"
                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label for="dictionary_words_enabled" class="ml-2 block text-sm font-medium text-gray-700">
                                    Enable Dictionary Words
                                </label>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">If enabled, random dictionary words will appear during video playback. Children must remember and enter them at the end.</p>

                            <div id="word_count_container" style="display: {{ old('dictionary_words_enabled', $video->dictionary_words_enabled) ? 'block' : 'none' }};">
                                <label for="word_count" class="block text-sm font-medium text-gray-700 mb-2">Number of Words *</label>
                                <input type="number" name="word_count" id="word_count" value="{{ old('word_count', $video->word_count) }}" min="1"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="mt-1 text-sm text-gray-500">How many random words to display during video</p>
                                @error('word_count')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Device Assignment (Shown after duration is detected) --}}
                        @if($devices->count() > 0)
                            <div class="mb-6" id="devices_section">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Devices</label>
                                <p class="text-sm text-gray-500 mb-3">Select which devices can watch this video</p>
                                <div class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                                    @foreach($devices as $device)
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" name="devices[]" id="device_{{ $device->id }}" value="{{ $device->id }}"
                                                {{ in_array($device->id, old('devices', $assignedDeviceIds)) ? 'checked' : '' }}
                                                class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                            <label for="device_{{ $device->id }}" class="ml-2 block text-sm text-gray-700">
                                                {{ $device->name }} ({{ $device->mac_address }})
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('devices')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md" id="devices_section">
                                <p class="text-sm text-yellow-800">No devices available. Please add devices first before assigning videos.</p>
                            </div>
                        @endif

                        {{-- Active Status (Shown after duration is detected) --}}
                        <div class="mb-6" id="active_section">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" 
                                    {{ old('is_active', $video->is_active) ? 'checked' : '' }}
                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">
                                    Active (Video will appear in portal)
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('videos.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #FFDE15; color: #000000;">
                                Update Video
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
         * Handle video file selection and detect duration.
         * 
         * When a video file is selected:
         * 1. Creates a temporary video element to read metadata
         * 2. Detects video duration automatically
         * 3. Fills in the duration_seconds field
         * 4. Shows the rest of the form fields
         */
        function handleVideoFileSelect(event) {
            const fileInput = event.target;
            const file = fileInput.files[0];
            
            if (!file) {
                // No file selected, hide sections
                hideFormSections();
                return;
            }
            
            // Validate file type
            const validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid video file (MP4, WebM, or OGG)');
                fileInput.value = '';
                hideFormSections();
                return;
            }
            
            // Show loading indicator
            document.getElementById('video_loading').style.display = 'block';
            document.getElementById('video_duration_detected').style.display = 'none';
            hideFormSections();
            
            // Create temporary video element to read metadata
            const video = document.createElement('video');
            video.preload = 'metadata';
            
            // Create object URL for the selected file
            const url = URL.createObjectURL(file);
            video.src = url;
            
            // When metadata is loaded, get duration
            video.addEventListener('loadedmetadata', function() {
                // Get duration in seconds (rounded to nearest integer)
                const durationSeconds = Math.round(video.duration);
                
                // Fill in duration field
                const durationInput = document.getElementById('duration_seconds');
                durationInput.value = durationSeconds;
                durationInput.removeAttribute('readonly'); // Allow manual editing if needed
                
                // Format duration for display (e.g., "3:45" or "1:23:45")
                const hours = Math.floor(durationSeconds / 3600);
                const minutes = Math.floor((durationSeconds % 3600) / 60);
                const seconds = durationSeconds % 60;
                let durationDisplay = '';
                if (hours > 0) {
                    durationDisplay = `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                } else {
                    durationDisplay = `${minutes}:${String(seconds).padStart(2, '0')}`;
                }
                
                // Show success message
                document.getElementById('video_loading').style.display = 'none';
                document.getElementById('video_duration_detected').style.display = 'block';
                document.getElementById('detected_duration_display').textContent = `${durationDisplay} (${durationSeconds} seconds)`;
                document.getElementById('auto_detected_badge').style.display = 'inline';
                
                // Show rest of form fields
                showFormSections();
                
                // Clean up object URL
                URL.revokeObjectURL(url);
            });
            
            // Handle errors
            video.addEventListener('error', function() {
                document.getElementById('video_loading').style.display = 'none';
                alert('Error loading video. Please try a different file.');
                fileInput.value = '';
                hideFormSections();
                URL.revokeObjectURL(url);
            });
        }
        
        /**
         * Show form sections after video duration is detected.
         */
        function showFormSections() {
            document.getElementById('metadata_section').style.display = 'block';
            document.getElementById('description_section').style.display = 'block';
            document.getElementById('duration_section').style.display = 'block';
            document.getElementById('dictionary_section').style.display = 'block';
            document.getElementById('devices_section').style.display = 'block';
            document.getElementById('active_section').style.display = 'block';
        }
        
        /**
         * Hide form sections until video is selected.
         */
        function hideFormSections() {
            // Don't hide metadata and description - they can be filled anytime
            // Only hide sections that depend on duration
            document.getElementById('duration_section').style.display = 'none';
            document.getElementById('dictionary_section').style.display = 'none';
            document.getElementById('devices_section').style.display = 'none';
            document.getElementById('active_section').style.display = 'none';
        }
        
        /**
         * Toggle word count input visibility based on dictionary words checkbox.
         * 
         * When parent checks/unchecks "Enable Dictionary Words", this function
         * shows/hides the word count input field.
         */
        function toggleWordCount() {
            const enabled = document.getElementById('dictionary_words_enabled').checked;
            const container = document.getElementById('word_count_container');
            const wordCountInput = document.getElementById('word_count');
            
            if (enabled) {
                container.style.display = 'block';
                wordCountInput.setAttribute('required', 'required');
            } else {
                container.style.display = 'none';
                wordCountInput.removeAttribute('required');
            }
        }
        
        // On page load, show all sections since we're editing an existing video
        // (existing video already has duration, so all fields should be visible)
        window.addEventListener('DOMContentLoaded', function() {
            // Always show all sections for edit page (existing video has duration)
            showFormSections();
        });
    </script>
    @endpush
</x-app-layout>

