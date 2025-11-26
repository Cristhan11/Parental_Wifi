{{-- Child Portal: Video Watching Interface --}}
{{-- Displays video player with dictionary word overlays and word submission form --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video - {{ $video->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #FFFFCC; /* Light yellow background matching design */
        }
        /* Word overlay styling - appears on top of video */
        .word-overlay {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(255, 222, 21, 0.95); /* Yellow with transparency */
            color: #000000;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeInOut 8s ease-in-out; /* 8 seconds for child to read */
        }
        @keyframes fadeInOut {
            0% { opacity: 0; } /* Fade in quickly */
            5% { opacity: 1; } /* Fully visible */
            90% { opacity: 1; } /* Stay visible for most of the duration */
            100% { opacity: 0; } /* Fade out at the end */
        }
        /* Ensure video element is interactive */
        #videoPlayer {
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }
        
        /* Ensure word overlay container doesn't block video controls */
        #wordOverlayContainer {
            pointer-events: none !important;
            z-index: 2; /* Above video but doesn't block clicks */
        }
        
        /* Word overlays themselves should not block clicks */
        .word-overlay {
            pointer-events: none !important;
        }
        
        /* Hide video controls for seeking/fast-forward */
        video::-webkit-media-controls-timeline {
            display: none !important;
        }
        video::-webkit-media-controls-current-time-display {
            display: none !important;
        }
        video::-webkit-media-controls-time-remaining-display {
            display: none !important;
        }
        
        /* Hide playback speed controls */
        video::-webkit-media-controls-playback-rate-button {
            display: none !important;
        }
        /* For Chrome/Edge - hide playback speed menu */
        video::-webkit-media-controls-overlay-play-button {
            display: none !important;
        }
        
        /* Additional hiding for playback speed in various browsers */
        video::-webkit-media-controls-enclosure {
            overflow: hidden;
        }
        
        /* Hide playback speed controls in Firefox and other browsers */
        video::-moz-media-controls {
            /* Firefox specific */
        }
    </style>
</head>
<body>
    <div class="min-h-screen" style="background-color: #FFFFCC;">
        {{-- Header: Yellow bar with back button and video label --}}
        <div class="bg-yellow-400 py-4 px-6 flex items-center justify-between" style="background-color: #FFDE15;">
            <div class="flex items-center space-x-3">
                <button onclick="goBack()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:opacity-75">
                    <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="px-4 py-2 border-2 border-blue-500 rounded" style="border-color: #3B82F6;">
                    <span class="font-semibold text-blue-500" style="color: #3B82F6;">VIDEO</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-black font-medium">{{ $video->title }}</div>
            </div>
        </div>

        {{-- Main Content: Video player and word submission form --}}
        <div class="max-w-4xl mx-auto px-4 py-8">
            {{-- Video Player Container --}}
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ $video->title }}</h2>
                @if($video->description)
                    <p class="text-gray-600 mb-4">{{ $video->description }}</p>
                @endif

                {{-- 
                    Video Player
                    - controls: Shows play/pause/volume controls (but seeking is disabled via CSS)
                    - ontimeupdate: Tracks video progress to show words at specific timestamps
                    - onended: Detects when video completes to show word submission form
                    - preload: Loads video metadata before playback
                --}}
                <div class="relative" style="background-color: #000000;">
                    <video 
                        id="videoPlayer" 
                        controls 
                        preload="metadata"
                        playsinline
                        class="w-full"
                        style="max-height: 70vh; position: relative; z-index: 1;"
                        ontimeupdate="handleTimeUpdate()"
                        onended="handleVideoEnded()"
                        onerror="handleVideoError(event)"
                        oncanplay="handleVideoCanPlay()"
                        onclick="console.log('Video clicked')">
                        <source src="{{ $video->getVideoUrl() }}" type="video/mp4">
                        <source src="{{ $video->getVideoUrl() }}" type="video/mpeg">
                        Your browser does not support the video tag.
                    </video>
                    
                    {{-- Word Overlay Container --}}
                    {{-- Words will appear here at random timestamps during playback --}}
                    {{-- pointer-events-none ensures clicks pass through to video controls --}}
                    <div id="wordOverlayContainer" class="absolute inset-0" style="pointer-events: none; z-index: 2;"></div>
                </div>

                {{-- Video Info --}}
                <div class="mt-4 text-sm text-gray-600">
                    <p>Duration: {{ $video->getDurationFormatted() }}</p>
                    @if($video->dictionary_words_enabled)
                        <p class="mt-1">
                            <strong>Watch carefully!</strong> {{ $video->word_count }} dictionary words will appear during the video. 
                            You must remember and enter them at the end.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Word Submission Form (Hidden until video ends) --}}
            {{-- This form appears when video completes, allowing child to enter words they saw --}}
            <div id="wordSubmissionForm" class="bg-white rounded-lg shadow-lg p-6" style="display: none;">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Enter the Words You Saw</h3>
                <p class="text-gray-600 mb-4">
                    Please enter all the dictionary words that appeared during the video, separated by commas.
                </p>
                
                <form id="wordsForm" action="{{ route('portal.video.submit', ['mac' => $device->mac_address]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="mac" value="{{ $device->mac_address }}">
                    
                    <div class="mb-4">
                        <label for="words" class="block text-sm font-medium text-gray-700 mb-2">
                            Words (comma-separated) *
                        </label>
                        <textarea 
                            name="words" 
                            id="words" 
                            rows="4" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                            placeholder="e.g., adventure, curious, discover"></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            Enter the words exactly as they appeared (case doesn't matter)
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button 
                            type="submit" 
                            class="px-6 py-3 rounded-lg text-white font-medium hover:opacity-90"
                            style="background-color: #10B981;">
                            Submit Words
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        /**
         * Video Player and Word Display Logic
         * 
         * This script handles:
         * 1. Displaying dictionary words at random timestamps during video playback
         * 2. Tracking which words have been shown
         * 3. Detecting video completion
         * 4. Showing word submission form when video ends
         * 5. Preventing seeking/fast-forward (video controls are restricted)
         */

        // Get video player element
        const videoPlayer = document.getElementById('videoPlayer');
        const wordOverlayContainer = document.getElementById('wordOverlayContainer');
        const wordSubmissionForm = document.getElementById('wordSubmissionForm');

        // Words data from server (contains word, definition, timestamp)
        const wordsData = @json($wordsData);
        const shownWords = []; // Track which words have been displayed

        /**
         * Handle video error events.
         * Logs errors to console for debugging and shows user-friendly error messages.
         * 
         * Common error codes:
         * - 1: MEDIA_ERR_ABORTED - Video loading aborted
         * - 2: MEDIA_ERR_NETWORK - Network error while loading video
         * - 3: MEDIA_ERR_DECODE - Video decoding error (codec not supported)
         * - 4: MEDIA_ERR_SRC_NOT_SUPPORTED - Video source not supported
         */
        function handleVideoError(event) {
            const video = event.target;
            console.error('Video error:', {
                error: video.error,
                code: video.error ? video.error.code : null,
                message: video.error ? video.error.message : null,
                networkState: video.networkState,
                readyState: video.readyState,
                src: video.src
            });
            
            // Show user-friendly error message
            if (video.error) {
                let errorMsg = 'Video playback error: ';
                switch(video.error.code) {
                    case 1: 
                        errorMsg += 'Video loading aborted. Please try refreshing the page.'; 
                        break;
                    case 2: 
                        errorMsg += 'Network error while loading video. Please check your connection.'; 
                        break;
                    case 3: 
                        errorMsg += 'Video codec not supported by your browser. Please try a different browser or video format.'; 
                        break;
                    case 4: 
                        errorMsg += 'Video format not supported. Please use MP4, WebM, or OGG format.'; 
                        break;
                    default: 
                        errorMsg += 'Unknown error occurred. Please try again.';
                }
                alert(errorMsg);
            }
        }

        /**
         * Handle when video can start playing.
         * This ensures video is ready before allowing playback.
         * Logs to console for debugging.
         */
        function handleVideoCanPlay() {
            console.log('Video can play - ready for playback');
            console.log('Video duration:', videoPlayer.duration, 'seconds');
            console.log('Video readyState:', videoPlayer.readyState);
            
            // Ensure video element is interactive
            videoPlayer.style.pointerEvents = 'auto';
            console.log('Video pointer-events set to auto');
        }
        
        /**
         * Add event listeners to diagnose interaction issues.
         */
        videoPlayer.addEventListener('click', function(e) {
            console.log('Video element clicked', e);
        });
        
        videoPlayer.addEventListener('play', function() {
            console.log('▶️ Video play event - video should be playing');
        });
        
        videoPlayer.addEventListener('playing', function() {
            console.log('▶️▶️ Video is actually playing!');
        });
        
        videoPlayer.addEventListener('pause', function() {
            console.log('⏸️ Video paused');
        });
        
        // Log when user tries to interact with controls
        videoPlayer.addEventListener('loadedmetadata', function() {
            console.log('Video metadata loaded');
            console.log('Video controls visible:', videoPlayer.controls);
        });

        /**
         * Handle video time update event.
         * 
         * This function is called continuously as video plays (every ~250ms).
         * It checks if any words should be displayed at the current timestamp.
         * 
         * How it works:
         * 1. Gets current video time in seconds
         * 2. Checks if any word's timestamp matches current time (within 1 second tolerance)
         * 3. If match found and word not already shown, displays word overlay
         * 4. Word appears for 3 seconds, then fades out
         */
        function handleTimeUpdate() {
            const currentTime = Math.floor(videoPlayer.currentTime);

            // Check each word to see if it should be displayed now
            wordsData.forEach((wordData, index) => {
                const wordTimestamp = wordData.timestamp;
                
                // Show word if current time matches timestamp (within 1 second tolerance)
                // and word hasn't been shown yet
                if (currentTime >= wordTimestamp && currentTime < wordTimestamp + 1 && !shownWords.includes(index)) {
                    showWord(wordData);
                    shownWords.push(index); // Mark as shown
                }
            });
        }

        /**
         * Display a dictionary word as an overlay on the video.
         * 
         * Creates a temporary overlay element that appears on top of the video,
         * displays the word and definition, then fades out after 8 seconds.
         * 
         * IMPORTANT: Pauses the video when word appears so child can read it.
         * Video will resume automatically after word fades out.
         * 
         * @param {Object} wordData - Object containing word, definition, and timestamp
         */
        function showWord(wordData) {
            // Pause video so child can read the word without missing content
            if (!videoPlayer.paused) {
                videoPlayer.pause();
                console.log('Video paused to show word:', wordData.word);
            }
            
            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'word-overlay';
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="text-2xl font-bold mb-1">${wordData.word}</div>
                    <div class="text-sm">${wordData.definition}</div>
                </div>
            `;

            // Add to container
            wordOverlayContainer.appendChild(overlay);

            // Remove after 8 seconds (animation handles fade out)
            // Resume video playback after word disappears
            setTimeout(() => {
                overlay.remove();
                // Resume video playback after word fades out
                if (videoPlayer.paused && videoPlayer.readyState >= 3) {
                    videoPlayer.play().then(() => {
                        console.log('Video resumed after word display');
                    }).catch(err => {
                        console.warn('Could not auto-resume video:', err);
                        // If autoplay fails, user can click play button
                    });
                }
            }, 8000); // 8 seconds to match animation duration
        }

        /**
         * Handle video completion event.
         * 
         * Called when video reaches the end (onended event).
         * Shows the word submission form so child can enter the words they saw.
         */
        function handleVideoEnded() {
            // Show word submission form
            wordSubmissionForm.style.display = 'block';
            
            // Scroll to form
            wordSubmissionForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        /**
         * Prevent seeking/fast-forward by disabling seek bar interactions.
         * 
         * This adds additional protection against seeking, even if CSS doesn't fully hide controls.
         * 
         * Note: This is disabled initially to allow video playback. Re-enable after confirming playback works.
         */
        // Temporarily disabled to allow video playback - re-enable after testing
        /*
        videoPlayer.addEventListener('seeked', function(e) {
            // If user tries to seek, reset to current playback position
            // This prevents skipping ahead in the video
            const currentTime = videoPlayer.currentTime;
            const duration = videoPlayer.duration;
            
            // Only allow seeking if video hasn't started or is at the end
            // Otherwise, prevent seeking by resetting to last known position
            if (currentTime > 0 && currentTime < duration - 1) {
                // Store last valid position
                if (!videoPlayer.lastValidTime) {
                    videoPlayer.lastValidTime = 0;
                }
                
                // If seeking forward more than 1 second, reset to last valid position
                if (currentTime > videoPlayer.lastValidTime + 1) {
                    videoPlayer.currentTime = videoPlayer.lastValidTime;
                } else {
                    videoPlayer.lastValidTime = currentTime;
                }
            }
        });
        */

        // Track last valid playback position (for future seeking prevention)
        videoPlayer.addEventListener('timeupdate', function() {
            if (!videoPlayer.lastValidTime) {
                videoPlayer.lastValidTime = 0;
            }
            videoPlayer.lastValidTime = videoPlayer.currentTime;
        });

        /**
         * Go back to previous page.
         */
        function goBack() {
            window.history.back();
        }
    </script>
</body>
</html>

