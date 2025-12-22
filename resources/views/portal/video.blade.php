<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video - {{ $video->title }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
        .font-montserrat {
            font-family: 'Montserrat', sans-serif;
        }
        .word-overlay {
            position: absolute;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(255, 222, 21, 0.95);
            color: #000000;
            padding: 1.5rem 3rem;
            border-radius: 1rem;
            border: 4px solid #000000;
            font-size: 1.75rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            animation: fadeInOut 8s ease-in-out;
        }
        @keyframes fadeInOut {
            0% { opacity: 0; }
            5% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
        #videoPlayer {
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }
        #wordOverlayContainer {
            pointer-events: none !important;
            z-index: 2;
        }
        .word-overlay {
            pointer-events: none !important;
        }
        video::-webkit-media-controls-timeline {
            display: none !important;
        }
        video::-webkit-media-controls-current-time-display {
            display: none !important;
        }
        video::-webkit-media-controls-time-remaining-display {
            display: none !important;
        }
        video::-webkit-media-controls-playback-rate-button {
            display: none !important;
        }
        video::-webkit-media-controls-overlay-play-button {
            display: none !important;
        }
        video::-webkit-media-controls-enclosure {
            overflow: hidden;
        }
    </style>
</head>
<body class="min-h-screen bg-[#FFFFCC] font-montserrat">
    <div class="min-h-screen">
        <!-- Header: Yellow bar with back button and video label -->
        <div class="bg-[#FFDE15] py-4 px-6 flex items-center justify-between shadow-md">
            <div class="flex items-center space-x-3">
                <button onclick="goBack()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:opacity-90 shadow-md transition">
                    <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="px-4 py-2 border-4 border-black rounded-lg bg-white">
                    <span class="font-extrabold text-black text-sm uppercase">VIDEO</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-extrabold text-black">{{ $video->title }}</div>
            </div>
        </div>

        <!-- Main Content: Video player and word submission form -->
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Video Player Container -->
            <div class="bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6 mb-6">
                <h2 class="text-2xl font-extrabold text-black mb-3">{{ $video->title }}</h2>
                @if($video->description)
                    <p class="text-gray-700 font-semibold mb-4">{{ $video->description }}</p>
                @endif

                <!-- Video Player -->
                <div class="relative rounded-lg overflow-hidden" style="background-color: #000000;">
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
                        oncanplay="handleVideoCanPlay()">
                        <source src="{{ $video->getVideoUrl() }}" type="video/mp4">
                        <source src="{{ $video->getVideoUrl() }}" type="video/mpeg">
                        Your browser does not support the video tag.
                    </video>
                    
                    <!-- Word Overlay Container -->
                    <div id="wordOverlayContainer" class="absolute inset-0" style="pointer-events: none; z-index: 2;"></div>
                </div>

                <!-- Video Info -->
                <div class="mt-4 p-4 rounded-xl bg-[#FFDE15] border-4 border-black">
                    <p class="text-sm font-extrabold text-black mb-2">Duration: {{ $video->getDurationFormatted() }}</p>
                    @if($video->dictionary_words_enabled)
                        <p class="text-sm font-extrabold text-black">
                            <strong>Watch carefully!</strong> {{ $video->word_count }} dictionary words will appear during the video. 
                            You must remember and enter them at the end.
                        </p>
                    @endif
                </div>
            </div>

            <!-- Word Submission Form (Hidden until video ends) -->
            <div id="wordSubmissionForm" class="bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6" style="display: none;">
                <h3 class="text-2xl font-extrabold text-black mb-4">Enter the Words You Saw</h3>
                <p class="text-gray-700 font-semibold mb-6">
                    Please enter all the dictionary words that appeared during the video, separated by commas.
                </p>
                
                <form id="wordsForm" action="{{ route('portal.video.submit', ['mac' => $device->mac_address]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="mac" value="{{ $device->mac_address }}">
                    
                    <div class="mb-6">
                        <label for="words" class="block text-sm font-extrabold text-black mb-2 uppercase">
                            Words (comma-separated) *
                        </label>
                        <textarea 
                            name="words" 
                            id="words" 
                            rows="4" 
                            required
                            class="w-full px-4 py-3 border-4 border-[#FFDE15] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#FFDE15] font-semibold text-black"
                            placeholder="e.g., adventure, curious, discover"></textarea>
                        <p class="mt-2 text-sm font-semibold text-gray-600">
                            Enter the words exactly as they appeared (case doesn't matter)
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="px-8 py-3 rounded-full bg-green-600 text-white font-extrabold hover:bg-green-700 transition shadow-lg">
                            Submit Words
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const videoPlayer = document.getElementById('videoPlayer');
        const wordOverlayContainer = document.getElementById('wordOverlayContainer');
        const wordSubmissionForm = document.getElementById('wordSubmissionForm');
        const wordsData = @json($wordsData);
        const shownWords = [];

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
            
            if (video.error) {
                let errorMsg = 'Video playback error: ';
                switch(video.error.code) {
                    case 1: errorMsg += 'Video loading aborted. Please try refreshing the page.'; break;
                    case 2: errorMsg += 'Network error while loading video. Please check your connection.'; break;
                    case 3: errorMsg += 'Video codec not supported by your browser. Please try a different browser or video format.'; break;
                    case 4: errorMsg += 'Video format not supported. Please use MP4, WebM, or OGG format.'; break;
                    default: errorMsg += 'Unknown error occurred. Please try again.';
                }
                alert(errorMsg);
            }
        }

        function handleVideoCanPlay() {
            console.log('Video can play - ready for playback');
            console.log('Video duration:', videoPlayer.duration, 'seconds');
            videoPlayer.style.pointerEvents = 'auto';
        }
        
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

        videoPlayer.addEventListener('loadedmetadata', function() {
            console.log('Video metadata loaded');
            console.log('Video controls visible:', videoPlayer.controls);
        });

        function handleTimeUpdate() {
            const currentTime = Math.floor(videoPlayer.currentTime);
            wordsData.forEach((wordData, index) => {
                const wordTimestamp = wordData.timestamp;
                if (currentTime >= wordTimestamp && currentTime < wordTimestamp + 1 && !shownWords.includes(index)) {
                    showWord(wordData);
                    shownWords.push(index);
                }
            });
        }

        function showWord(wordData) {
            if (!videoPlayer.paused) {
                videoPlayer.pause();
                console.log('Video paused to show word:', wordData.word);
            }
            
            const overlay = document.createElement('div');
            overlay.className = 'word-overlay';
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="text-3xl font-extrabold mb-2">${wordData.word}</div>
                    <div class="text-base font-semibold">${wordData.definition}</div>
                </div>
            `;

            wordOverlayContainer.appendChild(overlay);

            setTimeout(() => {
                overlay.remove();
                if (videoPlayer.paused && videoPlayer.readyState >= 3) {
                    videoPlayer.play().then(() => {
                        console.log('Video resumed after word display');
                    }).catch(err => {
                        console.warn('Could not auto-resume video:', err);
                    });
                }
            }, 8000);
        }

        function handleVideoEnded() {
            wordSubmissionForm.style.display = 'block';
            wordSubmissionForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        videoPlayer.addEventListener('timeupdate', function() {
            if (!videoPlayer.lastValidTime) {
                videoPlayer.lastValidTime = 0;
            }
            videoPlayer.lastValidTime = videoPlayer.currentTime;
        });

        function goBack() {
            if (confirm('Are you sure you want to leave? Your progress will be lost.')) {
                window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
            }
        }
    </script>
</body>
</html>

