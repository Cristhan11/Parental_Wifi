<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Result - Parental WiFi</title>
    
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
    </style>
</head>
<body class="min-h-screen bg-[#FFFFCC] font-montserrat flex items-center justify-center">
    <div class="max-w-2xl w-full mx-4">
        <div class="bg-white rounded-xl shadow-2xl border-4 border-[#FFDE15] p-8 text-center">
            @if($completion->passed_validation)
                <!-- PASSED: Success message and time granted popup -->
                <div class="mb-6">
                    <div class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-500 shadow-lg">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-4xl font-extrabold mb-2 text-green-600">Congratulations!</h2>
                    <p class="text-xl font-semibold text-gray-700 mb-4">You correctly entered all the words!</p>
                </div>

                <!-- Word count display -->
                <div class="mb-6 p-6 rounded-xl border-4 border-[#FFDE15] bg-[#FFDE15]">
                    <div class="text-5xl font-extrabold text-black mb-2">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                    <div class="text-sm font-semibold text-gray-700 uppercase">Words Correct</div>
                </div>

                <!-- Time granted popup/message -->
                @if($timeGranted > 0)
                    <div class="mb-6 p-6 rounded-xl border-4 border-green-500 bg-green-50 shadow-lg">
                        <div class="text-4xl mb-3">🎉</div>
                        <p class="text-2xl font-extrabold text-green-700 mb-2">
                            You earned {{ $timeGranted }} minutes of internet time!
                        </p>
                        <p class="text-sm font-semibold text-gray-600">You will be redirected automatically...</p>
                    </div>
                @endif

                <!-- Countdown timer -->
                <div class="text-sm font-semibold text-gray-500 mb-4">
                    Redirecting in <span id="countdown" class="text-2xl font-extrabold text-black">3</span> seconds...
                </div>

                <script>
                    let countdown = 3;
                    const countdownElement = document.getElementById('countdown');
                    
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        if (countdown > 0) {
                            countdownElement.textContent = countdown;
                        } else {
                            clearInterval(countdownInterval);
                            window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
                        }
                    }, 1000);
                </script>
            @else
                <!-- FAILED: Failure message with correct words and retry option -->
                <div class="mb-6">
                    <div class="w-24 h-24 mx-auto mb-4 rounded-full flex items-center justify-center bg-red-500 shadow-lg">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-4xl font-extrabold mb-2 text-red-600">Try Again</h2>
                    <p class="text-xl font-semibold text-gray-700 mb-4">You did not enter all words correctly.</p>
                </div>

                <!-- Word count display -->
                <div class="mb-6 p-6 rounded-xl border-4 border-[#FFDE15] bg-[#FFDE15]">
                    <div class="text-5xl font-extrabold text-black mb-2">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                    <div class="text-sm font-semibold text-gray-700 uppercase">Words Correct</div>
                </div>

                <!-- Show correct words for learning -->
                @if(count($wordsShown) > 0)
                    <div class="mb-6 p-6 rounded-xl border-4 border-blue-500 bg-blue-50">
                        <h3 class="text-xl font-extrabold text-black mb-3">The correct words were:</h3>
                        <div class="text-gray-700 mb-3">
                            <p class="font-bold text-lg">{{ implode(', ', $wordsShown) }}</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-600">
                            Watch the video again and remember these words!
                        </p>
                    </div>
                @endif

                <!-- Encouragement message -->
                <div class="mb-6 p-4 rounded-xl bg-gray-50 border-2 border-gray-200">
                    <p class="text-gray-700 font-semibold">
                        Don't worry! You can watch the video again. Pay attention to the dictionary words 
                        that appear, and enter them all correctly to earn internet time.
                    </p>
                </div>

                <!-- Action buttons -->
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}" 
                       class="px-8 py-3 rounded-full bg-green-600 text-white font-extrabold hover:bg-green-700 transition shadow-lg">
                        Watch Video Again
                    </a>
                    <a href="{{ route('portal.landing', ['mac' => $device->mac_address]) }}" 
                       class="px-8 py-3 rounded-full border-4 border-gray-300 text-gray-700 font-extrabold hover:bg-gray-50 transition">
                        Go Back
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
