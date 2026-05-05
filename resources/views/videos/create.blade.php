{{-- Parent Dashboard: Video Creation Form --}}
{{-- Form that allows parents to upload videos and configure settings --}}
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
                    Create New Video
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="videoForm">
                        @csrf

                        <x-collapsible-instructions class="mb-6">
                            <p class="mb-2 font-semibold">Instructions</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>Choose a <strong>video file</strong> first. This page reads how long the video is.</li>
                                <li>Then add a title, optional notes, and how many <strong>minutes of internet time</strong> a child gets after they finish.</li>
                                <li><strong>Dictionary words</strong> are optional: if you turn them on, the child must type the words shown during the video before they earn time.</li>
                                <li>Tap <strong>Save</strong> to upload. Big files can take a while—wait until the upload finishes.</li>
                            </ul>
                        </x-collapsible-instructions>

                        {{-- Video File Upload (FIRST - Required, detects duration). Custom label: native control keeps "Choose File" prefix on Windows. --}}
                        <div class="mb-6">
                            <span class="block text-sm font-medium text-gray-700 mb-2" id="video_file_field_label">
                                Video File <span class="text-red-500">*</span>
                            </span>
                            <div class="flex flex-col gap-2 rounded-md border border-gray-300 bg-white px-3 py-3 shadow-sm sm:flex-row sm:items-center sm:gap-4">
                                <label for="video_file" class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-md border border-gray-400 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100 focus-within:outline-none focus-within:ring-2 focus-within:ring-yellow-500 focus-within:ring-offset-1">
                                    Choose file
                                </label>
                                <input type="file" name="video_file" id="video_file" accept="video/mp4,video/webm,video/ogg" required
                                    class="sr-only"
                                    onchange="handleVideoFileSelect(event)">
                                <span id="video_file_display" class="min-w-0 flex-1 truncate text-sm text-gray-700" title="">No file chosen yet</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Accepted formats: MP4, WebM, OGG. Maximum size: 512MB</p>
                            <div id="video_loading" class="mt-2 text-sm text-blue-600" style="display: none;">
                                <span class="inline-block animate-spin mr-2">⏳</span> Detecting video duration...
                            </div>
                            <div id="video_duration_detected" class="mt-2 text-sm text-green-600" style="display: none;">
                                ✅ Duration detected: <span id="detected_duration_display"></span>
                            </div>
                            @error('video_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('duration_seconds')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Filled by JS from video metadata; visually hidden (not type=hidden: required is ignored on hidden inputs) --}}
                        <input type="number" name="duration_seconds" id="duration_seconds" value="{{ old('duration_seconds') }}" min="1" required
                            class="sr-only pointer-events-none" tabindex="-1" aria-hidden="true">

                        {{-- Video Metadata: Title, Description (Shown after video is selected) --}}
                        <div class="mb-6" id="metadata_section" style="display: none;">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Video Title *</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6" id="description_section" style="display: none;">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Time reward (duration is hidden; set from video file via JS) --}}
                        <div class="mb-6" id="duration_section" style="display: none;">
                            <label for="time_reward_minutes" class="block text-sm font-medium text-gray-700 mb-2">Time Reward (minutes) *</label>
                            <input type="number" name="time_reward_minutes" id="time_reward_minutes" value="{{ old('time_reward_minutes', 15) }}" min="1" required
                                class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            @error('time_reward_minutes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Dictionary Words Settings (Shown after duration is detected) --}}
                        <div class="mb-6" id="dictionary_section" style="display: none;">
                            <div class="flex items-center mb-4">
                                <input type="checkbox" name="dictionary_words_enabled" id="dictionary_words_enabled" value="1" 
                                    {{ old('dictionary_words_enabled') ? 'checked' : '' }}
                                    onchange="toggleWordCount()"
                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label for="dictionary_words_enabled" class="ml-2 block text-sm font-medium text-gray-700">
                                    Enable Dictionary Words
                                </label>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">If enabled, random dictionary words will appear during video playback. Children must remember and enter them at the end.</p>

                            <div id="word_count_container" style="display: {{ old('dictionary_words_enabled') ? 'block' : 'none' }};">
                                <label for="word_count" class="block text-sm font-medium text-gray-700 mb-2">Number of Words *</label>
                                <input type="number" name="word_count" id="word_count" value="{{ old('word_count', 5) }}" min="1"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <p class="mt-1 text-sm text-gray-500">How many random words to display during video</p>
                                @error('word_count')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Device Assignment (Shown after duration is detected) --}}
                        @if($devices->count() > 0)
                            <div class="mb-6" id="devices_section" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Devices</label>
                                <p class="text-sm text-gray-500 mb-3">Select which devices can watch this video</p>
                                <div class="border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                                    @foreach($devices as $device)
                                        <div class="flex items-center mb-2">
                                            <input type="checkbox" name="devices[]" id="device_{{ $device->id }}" value="{{ $device->id }}"
                                                {{ in_array($device->id, old('devices', [])) ? 'checked' : '' }}
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
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md" id="devices_section" style="display: none;">
                                <p class="text-sm text-yellow-800">No devices available. Please add devices first before assigning videos.</p>
                            </div>
                        @endif

                        {{-- Active Status (Shown after duration is detected) --}}
                        <div class="mb-6" id="active_section" style="display: none;">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" 
                                    {{ old('is_active', true) ? 'checked' : '' }}
                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">
                                    Active (Video will appear in portal)
                                </label>
                            </div>
                        </div>

                        <div id="video_upload_errors" class="mb-4 hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"></div>

                        <div id="video_upload_status" class="mb-6 hidden rounded-lg border border-gray-200 bg-gray-50 px-4 py-4" aria-hidden="true">
                            <div class="mb-2 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                <div id="video_upload_progress_fill" class="h-full rounded-full transition-[width] duration-150 ease-out" style="width: 0%; background-color: #FFDE15;"></div>
                            </div>
                            <p id="video_upload_status_text" class="text-sm text-gray-700" role="status" aria-live="polite"></p>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('videos.index') }}" id="videoFormCancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" id="videoFormSubmit" class="px-4 py-2 rounded text-white font-medium hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60" style="background-color: #FFDE15; color: #000000;">
                                Create Video
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
            const fileDisplay = document.getElementById('video_file_display');

            function setFileDisplay(text, title) {
                if (!fileDisplay) {
                    return;
                }
                fileDisplay.textContent = text;
                fileDisplay.setAttribute('title', title || '');
            }

            if (!file) {
                setFileDisplay('No file chosen yet', '');
                hideFormSections();
                return;
            }

            setFileDisplay(file.name, file.name);
            
            // Validate file type
            const validTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            if (!validTypes.includes(file.type)) {
                alert('Please select a valid video file (MP4, WebM, or OGG)');
                fileInput.value = '';
                setFileDisplay('No file chosen yet', '');
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
                
                // Fill hidden duration field
                document.getElementById('duration_seconds').value = durationSeconds;

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
                setFileDisplay('No file chosen yet', '');
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
            document.getElementById('metadata_section').style.display = 'none';
            document.getElementById('description_section').style.display = 'none';
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

        (function () {
            const form = document.getElementById('videoForm');
            const submitBtn = document.getElementById('videoFormSubmit');
            const cancelLink = document.getElementById('videoFormCancel');
            const statusBox = document.getElementById('video_upload_status');
            const statusText = document.getElementById('video_upload_status_text');
            const progressFill = document.getElementById('video_upload_progress_fill');
            const errorsBox = document.getElementById('video_upload_errors');

            function escapeHtml(s) {
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function showErrors(messages) {
                errorsBox.innerHTML = messages.map(function (m) {
                    return '<div>' + escapeHtml(m) + '</div>';
                }).join('');
                errorsBox.classList.remove('hidden');
            }

            function resetProgressUi() {
                progressFill.style.width = '0%';
                statusText.textContent = '';
                errorsBox.classList.add('hidden');
                errorsBox.textContent = '';
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                resetProgressUi();
                statusBox.classList.remove('hidden');
                statusBox.setAttribute('aria-hidden', 'false');
                statusText.textContent = 'Starting upload…';
                submitBtn.disabled = true;
                cancelLink.classList.add('pointer-events-none', 'opacity-50');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.addEventListener('progress', function (ev) {
                    if (ev.lengthComputable && ev.total > 0) {
                        const pct = Math.min(100, Math.round((ev.loaded / ev.total) * 100));
                        progressFill.style.width = pct + '%';
                        statusText.textContent = 'Uploading… ' + pct + '%';
                    } else {
                        statusText.textContent = 'Uploading…';
                    }
                });

                xhr.upload.addEventListener('loadend', function () {
                    if (xhr.readyState < 4) {
                        progressFill.style.width = '100%';
                        statusText.textContent = 'Saving…';
                    }
                });

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success && data.redirect_url) {
                                progressFill.style.width = '100%';
                                statusText.textContent = data.message || 'Video saved.';
                                setTimeout(function () {
                                    window.location.href = data.redirect_url;
                                }, 1000);
                                return;
                            }
                        } catch (err) { /* fall through */ }
                    }

                    submitBtn.disabled = false;
                    cancelLink.classList.remove('pointer-events-none', 'opacity-50');

                    if (xhr.status === 422) {
                        try {
                            const body = JSON.parse(xhr.responseText);
                            const errs = body.errors || {};
                            const flat = [];
                            Object.keys(errs).forEach(function (k) {
                                const arr = errs[k];
                                if (Array.isArray(arr)) {
                                    arr.forEach(function (msg) {
                                        flat.push(msg);
                                    });
                                }
                            });
                            showErrors(flat.length ? flat : ['Could not validate the form.']);
                        } catch (err) {
                            showErrors(['Could not validate the form.']);
                        }
                        statusBox.classList.add('hidden');
                        statusBox.setAttribute('aria-hidden', 'true');
                        return;
                    }

                    if (xhr.status === 419) {
                        showErrors(['Your session expired. Refresh the page and try again.']);
                    } else {
                        showErrors(['Something went wrong. Please try again.']);
                    }
                    statusBox.classList.add('hidden');
                    statusBox.setAttribute('aria-hidden', 'true');
                };

                xhr.onerror = function () {
                    submitBtn.disabled = false;
                    cancelLink.classList.remove('pointer-events-none', 'opacity-50');
                    showErrors(['Network error. Check your connection and try again.']);
                    statusBox.classList.add('hidden');
                    statusBox.setAttribute('aria-hidden', 'true');
                };

                xhr.send(new FormData(form));
            });
        })();
    </script>
    @endpush
</x-app-layout>

