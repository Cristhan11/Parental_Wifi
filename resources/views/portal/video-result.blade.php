{{-- 
    Child Portal: Video Results Page
    
    Purpose: Displays the result of word validation after child completes a video.
    
    What it shows:
    - If PASSED: Success message, time granted, auto-redirect after 3 seconds
    - If FAILED: Failure message, correct words (for learning), retry button
    
    Key Features:
    - Shows different content based on $completion->passed_validation
    - Displays word count (correct / total)
    - Shows time granted if validation passed
    - Shows correct words if validation failed (helps child learn)
    - Auto-redirects on success, manual retry on failure
    
    Why show correct words on failure?
    - Helps children learn the correct words for next attempt
    - They still must watch entire video again with NEW random words
    - Ensures active learning, not just memorization
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Result</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #FFFFCC;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center" style="background-color: #FFFFCC;">
        <div class="max-w-2xl w-full mx-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                {{-- 
                    Video Result Display
                    Shows different content based on whether child passed or failed word validation.
                    If passed: Success message, time granted, auto-redirect
                    If failed: Failure message, correct words shown (for learning), retry button
                --}}
                @if($completion->passed_validation)
                    {{-- 
                        PASSED: Show success message and auto-redirect
                        Child sees congratulations message and time granted.
                        Page automatically redirects after 3 seconds so child can continue browsing.
                    --}}
                    <div class="mb-6">
                        {{-- Green checkmark circle icon --}}
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #10B981;">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold mb-2" style="color: #10B981;">Congratulations!</h2>
                        <p class="text-lg text-gray-700 mb-4">You correctly entered all the words!</p>
                    </div>

                    {{-- Word count display in yellow box --}}
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #FFDE15;">
                        <div class="text-4xl font-bold text-black mb-2">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                        <div class="text-sm text-gray-700">Words Correct</div>
                    </div>

                    {{-- Time granted message (only shown if time was actually granted) --}}
                    @if($timeGranted > 0)
                        <div class="mb-6 p-4 rounded-lg border-2" style="border-color: #10B981;">
                            <p class="text-lg font-semibold text-gray-900 mb-1">
                                🎉 You earned {{ $timeGranted }} minutes of internet time!
                            </p>
                            <p class="text-sm text-gray-600">You will be redirected automatically...</p>
                        </div>
                    @endif

                    {{-- Countdown timer showing seconds until redirect --}}
                    <div class="text-sm text-gray-500 mb-4">
                        Redirecting in <span id="countdown">3</span> seconds...
                    </div>

                    {{-- 
                        Auto-Redirect JavaScript
                        Counts down from 3 seconds, then redirects child to portal landing page.
                        This allows child to see their success message before continuing.
                    --}}
                    <script>
                        let countdown = 3;
                        const countdownElement = document.getElementById('countdown');
                        
                        const countdownInterval = setInterval(() => {
                            countdown--;
                            if (countdown > 0) {
                                countdownElement.textContent = countdown;
                            } else {
                                clearInterval(countdownInterval);
                                // Redirect to continue browsing
                                window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
                            }
                        }, 1000);
                    </script>
                @else
                    {{-- 
                        FAILED: Show failure message with correct words and retry option
                        Child sees which words they got wrong and the correct words.
                        This helps them learn for the next attempt. They must watch the entire
                        video again with new random words, ensuring active learning.
                    --}}
                    <div class="mb-6">
                        {{-- Red X circle icon --}}
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #EF4444;">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold mb-2" style="color: #EF4444;">Try Again</h2>
                        <p class="text-lg text-gray-700 mb-4">You did not enter all words correctly.</p>
                    </div>

                    {{-- Word count display showing correct vs total --}}
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #FFDE15;">
                        <div class="text-4xl font-bold text-black mb-2">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                        <div class="text-sm text-gray-700">Words Correct</div>
                    </div>

                    {{-- Show correct words for learning --}}
                    {{-- Unlike quiz system, we show correct words here to help child learn --}}
                    @if(count($wordsShown) > 0)
                        <div class="mb-6 p-4 rounded-lg border-2" style="border-color: #3B82F6;">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">The correct words were:</h3>
                            <div class="text-gray-700">
                                <p class="font-medium">{{ implode(', ', $wordsShown) }}</p>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">
                                Watch the video again and remember these words!
                            </p>
                        </div>
                    @endif

                    {{-- Encouragement message --}}
                    <div class="mb-6">
                        <p class="text-gray-700 mb-4">
                            Don't worry! You can watch the video again. Pay attention to the dictionary words 
                            that appear, and enter them all correctly to earn internet time.
                        </p>
                    </div>

                    <div class="flex justify-center space-x-3">
                        <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}" 
                           class="px-6 py-3 rounded-lg text-white font-medium hover:opacity-90"
                           style="background-color: #10B981;">
                            Watch Video Again
                        </a>
                        <a href="{{ route('portal.landing', ['mac' => $device->mac_address]) }}" 
                           class="px-6 py-3 rounded-lg border-2 border-gray-300 text-gray-700 font-medium hover:bg-gray-50">
                            Go Back
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

