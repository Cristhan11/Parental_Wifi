{{-- 
    Child Portal: Landing Page
    
    Purpose: Main entry point for children accessing the portal. Shows all available
    quizzes and videos that the child can complete to earn internet time.
    
    What it displays:
    - Device name and remaining internet time
    - List of available quizzes (with time rewards)
    - List of available videos (with time rewards)
    - Error message if device not found
    
    How it works:
    1. Child accesses portal with MAC address: /portal?mac=AA:BB:CC:DD:EE:FF
    2. System identifies device by MAC address
    3. System fetches quizzes and videos assigned to this device
    4. Page displays all available activities
    5. Child clicks on activity to start it
    
    Design:
    - Yellow background (#FFFFCC) matching portal theme
    - Cards for each quiz/video showing title, description, and time reward
    - Color-coded: Blue for quizzes, Green for videos
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal</title>
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
    <div class="min-h-screen" style="background-color: #FFFFCC;">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Welcome!</h1>
                
                {{-- 
                    Error Display Section
                    Shows error message if device was not found in database.
                    This happens if MAC address doesn't match any device records.
                --}}
                @if(isset($error))
                    <div class="mb-6 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700">
                        <p>{{ $error }}</p>
                    </div>
                @elseif($device)
                    {{-- 
                        Device Information Section
                        Shows device name and remaining internet time.
                        getRemainingTimeFormatted() converts minutes to readable format
                        (e.g., "1 hour 30 minutes" or "45 minutes").
                    --}}
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #FFDE15;">
                        <h2 class="text-xl font-bold text-black mb-2">Your Device</h2>
                        <p class="text-gray-800"><strong>Name:</strong> {{ $device->name }}</p>
                        <p class="text-gray-800"><strong>Time Remaining:</strong> {{ $device->getRemainingTimeFormatted() }}</p>
                    </div>

                    {{-- 
                        Available Quizzes Section
                        Displays all active quizzes assigned to this device.
                        Each quiz card shows:
                        - Quiz title and description
                        - Time reward (minutes child will earn)
                        - Clickable link to start the quiz
                        
                        Design: Blue theme (#3B82F6) to distinguish from videos
                    --}}
                    @if($quizzes->count() > 0)
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Available Quizzes</h2>
                            <div class="space-y-3">
                                {{-- Loop through each quiz assigned to this device --}}
                                @foreach($quizzes as $quiz)
                                    {{-- 
                                        Quiz Card
                                        - Clickable link to start quiz
                                        - route('portal.quiz.show') generates URL: /portal/quiz/{id}?mac=...
                                        - Passes quiz ID and device MAC address
                                    --}}
                                    <a href="{{ route('portal.quiz.show', ['quiz' => $quiz->id, 'mac' => $device->mac_address]) }}" 
                                       class="block p-4 rounded-lg border-2 hover:opacity-90 transition-opacity" 
                                       style="border-color: #3B82F6; background-color: #EFF6FF;">
                                        <div class="flex items-center justify-between">
                                            {{-- Quiz information (left side) --}}
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">{{ $quiz->title }}</h3>
                                                {{-- Show description or default message --}}
                                                {{-- ?? operator: if description is null, use default text --}}
                                                <p class="text-sm text-gray-600">{{ $quiz->description ?? 'Take this quiz to earn internet time!' }}</p>
                                            </div>
                                            {{-- Time reward (right side) --}}
                                            <div class="text-right">
                                                <div class="text-sm text-gray-600">Time Reward</div>
                                                <div class="text-lg font-bold" style="color: #3B82F6;">{{ $quiz->time_reward_minutes }} min</div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 
                        Available Videos Section
                        Displays all active videos assigned to this device.
                        Each video card shows:
                        - Video title and description
                        - Video duration (formatted as MM:SS)
                        - Word count (if dictionary words enabled)
                        - Time reward (minutes child will earn)
                        - Clickable link to start watching video
                        
                        Design: Green theme (#10B981) to distinguish from quizzes
                    --}}
                    @if($videos->count() > 0)
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Available Videos</h2>
                            <div class="space-y-3">
                                {{-- Loop through each video assigned to this device --}}
                                @foreach($videos as $video)
                                    {{-- 
                                        Video Card
                                        - Clickable link to start video
                                        - route('portal.video.show') generates URL: /portal/video/{id}?mac=...
                                        - Passes video ID and device MAC address
                                    --}}
                                    <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}" 
                                       class="block p-4 rounded-lg border-2 hover:opacity-90 transition-opacity" 
                                       style="border-color: #10B981; background-color: #ECFDF5;">
                                        <div class="flex items-center justify-between">
                                            {{-- Video information (left side) --}}
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">{{ $video->title }}</h3>
                                                {{-- Show description or default message --}}
                                                <p class="text-sm text-gray-600">{{ $video->description ?? 'Watch this video to earn internet time!' }}</p>
                                                {{-- Video metadata: duration and word count --}}
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{-- gmdate('i:s', seconds) formats duration as MM:SS (e.g., "05:30" for 5 minutes 30 seconds) --}}
                                                    Duration: {{ gmdate('i:s', $video->duration_seconds) }}
                                                    {{-- Show word count if dictionary words are enabled --}}
                                                    @if($video->dictionary_words_enabled)
                                                        • {{ $video->word_count }} words
                                                    @endif
                                                </p>
                                            </div>
                                            {{-- Time reward (right side) --}}
                                            <div class="text-right">
                                                <div class="text-sm text-gray-600">Time Reward</div>
                                                <div class="text-lg font-bold" style="color: #10B981;">{{ $video->time_reward_minutes }} min</div>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 
                        No Activities Available Message
                        Shown when device exists but has no quizzes or videos assigned.
                        This happens when:
                        - Parent hasn't assigned any activities to this device yet
                        - All activities have been deactivated (is_active = false)
                    --}}
                    @if($quizzes->count() === 0 && $videos->count() === 0)
                        <div class="text-center py-8">
                            <p class="text-gray-600 text-lg">No quizzes or videos available at this time.</p>
                            <p class="text-gray-500 text-sm mt-2">Please check back later or contact your parent.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</body>
</html>

