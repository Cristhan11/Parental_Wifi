<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal - Parental WiFi</title>
    
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
<body class="min-h-screen bg-[#FFFFCC] font-montserrat">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            
            <!-- Header Section -->
            <div class="text-center mb-8">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-black mb-2 font-montserrat">Welcome!</h1>
                <p class="text-lg text-gray-700 font-semibold font-montserrat">Complete activities to earn internet time</p>
            </div>

            <!-- Error Display -->
            @if(isset($error))
                <div class="mb-6 p-4 rounded-xl bg-red-100 border-4 border-red-500 text-red-700 font-semibold text-center">
                    <p>{{ $error }}</p>
                </div>
            @elseif($device)
                
                <!-- Device Info Card -->
                <div class="mb-8 bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6 font-montserrat">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-black mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Your Device
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Device Name</p>
                            <p class="text-lg font-bold text-black">{{ $device->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Time Remaining</p>
                            <p class="text-lg font-bold text-black">{{ $device->getRemainingTimeFormatted() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Available Quizzes Section -->
                @if($quizzes->count() > 0)
                    <div class="mb-8">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-black mb-6 flex items-center gap-3 font-montserrat">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Available Quizzes
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($quizzes as $quiz)
                                <a href="{{ route('portal.quiz.show', ['quiz' => $quiz->id, 'mac' => $device->mac_address]) }}" 
                                   class="block bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6 hover:shadow-xl transition-all duration-300 font-montserrat">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-extrabold text-black mb-2">{{ $quiz->title }}</h3>
                                            <p class="text-sm text-gray-600 mb-3">{{ $quiz->description ?? 'Take this quiz to earn internet time!' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-4 border-t-2 border-gray-100">
                                        <div>
                                            <p class="text-xs font-semibold text-gray-600 uppercase">Time Reward</p>
                                            <p class="text-2xl font-extrabold text-black">{{ $quiz->time_reward_minutes }} min</p>
                                        </div>
                                        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Available Videos Section -->
                @if($videos->count() > 0)
                    <div class="mb-8">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-black mb-6 flex items-center gap-3 font-montserrat">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Available Videos
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($videos as $video)
                                <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}" 
                                   class="block bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6 hover:shadow-xl transition-all duration-300 font-montserrat">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-extrabold text-black mb-2">{{ $video->title }}</h3>
                                            <p class="text-sm text-gray-600 mb-2">{{ $video->description ?? 'Watch this video to earn internet time!' }}</p>
                                            <p class="text-xs text-gray-500">
                                                Duration: {{ gmdate('i:s', $video->duration_seconds) }}
                                                @if($video->dictionary_words_enabled)
                                                    • {{ $video->word_count }} words
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-4 border-t-2 border-gray-100">
                                        <div>
                                            <p class="text-xs font-semibold text-gray-600 uppercase">Time Reward</p>
                                            <p class="text-2xl font-extrabold text-black">{{ $video->time_reward_minutes }} min</p>
                                        </div>
                                        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- No Activities Message -->
                @if($quizzes->count() === 0 && $videos->count() === 0)
                    <div class="text-center py-12 bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-8">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-lg font-semibold text-gray-700 mb-2">No quizzes or videos available at this time.</p>
                        <p class="text-sm text-gray-600">Please check back later or contact your parent.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
