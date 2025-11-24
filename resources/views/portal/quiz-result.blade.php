{{-- Child Portal: Quiz Results Page --}}
{{-- Shows score and pass/fail status. Does NOT show correct answers to allow fair retries. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz Result</title>
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
                    Quiz Result Display
                    Shows different content based on whether child passed or failed.
                    If passed: Success message, score, time granted, auto-redirect
                    If failed: Failure message, score, retry button (answers NOT shown)
                --}}
                @if($attempt->passed)
                    {{-- 
                        PASSED: Show success message and auto-redirect
                        Child sees congratulations message, score, and time granted.
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
                        <p class="text-lg text-gray-700 mb-4">You passed the quiz!</p>
                    </div>

                    {{-- Score display in yellow box --}}
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #FFDE15;">
                        <div class="text-4xl font-bold text-black mb-2">{{ $attempt->score }}%</div>
                        <div class="text-sm text-gray-700">Your Score</div>
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
                        
                        Why auto-redirect? Provides smooth user experience - child doesn't need
                        to click a button, they automatically get access to internet.
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
                        FAILED: Show failure message with retry option
                        Child sees their score and required score, but correct answers are NOT shown.
                        This allows fair retry - child must actually learn, not just memorize answers.
                    --}}
                    <div class="mb-6">
                        {{-- Red X circle icon --}}
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: #EF4444;">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold mb-2" style="color: #EF4444;">Try Again</h2>
                        <p class="text-lg text-gray-700 mb-4">You did not pass this time.</p>
                    </div>

                    {{-- Score display showing child's score and required score --}}
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #FFDE15;">
                        <div class="text-4xl font-bold text-black mb-2">{{ $attempt->score }}%</div>
                        <div class="text-sm text-gray-700">Your Score</div>
                        <div class="text-sm text-gray-600 mt-2">
                            Required: {{ $attempt->quiz->passing_score }}%
                        </div>
                    </div>

                    {{-- Encouragement message --}}
                    <div class="mb-6">
                        <p class="text-gray-700 mb-4">
                            Don't worry! You can try again. Study the material and take the quiz once more to earn internet time.
                        </p>
                    </div>
                    
                    {{-- 
                        Note: Correct answers are NOT displayed here.
                        This prevents children from simply memorizing answers and retaking
                        the quiz immediately. They must actually learn the material.
                    --}}

                    <div class="flex justify-center space-x-3">
                        <a href="{{ route('portal.quiz.show', ['quiz' => $attempt->quiz->id, 'mac' => $device->mac_address]) }}" 
                           class="px-6 py-3 rounded-lg text-white font-medium hover:opacity-90"
                           style="background-color: #10B981;">
                            Retry Quiz
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

