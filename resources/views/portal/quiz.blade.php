{{-- Child Portal: Quiz Taking Interface --}}
{{-- Displays quiz questions one at a time with timer and navigation --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz - {{ $quiz->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #FFFFCC; /* Light yellow background matching design */
        }
    </style>
</head>
<body>
    <div class="min-h-screen" style="background-color: #FFFFCC;">
        {{-- Header: Yellow bar with back button, quiz label, and timer --}}
        <div class="bg-yellow-400 py-4 px-6 flex items-center justify-between" style="background-color: #FFDE15;">
            <div class="flex items-center space-x-3">
                <button onclick="goBack()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:opacity-75">
                    <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="px-4 py-2 border-2 border-blue-500 rounded" style="border-color: #3B82F6;">
                    <span class="font-semibold text-blue-500" style="color: #3B82F6;">QUIZ</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-black">TIME REMAINING</div>
                <div class="text-2xl font-bold text-black" id="timer">10:00</div> {{-- Timer updated via JavaScript --}}
            </div>
        </div>

        {{-- Main Content: Quiz form with questions --}}
        <div class="max-w-4xl mx-auto px-4 py-8">
            <form id="quizForm" action="{{ route('portal.quiz.submit', ['mac' => $device->mac_address]) }}" method="POST">
                @csrf
                <input type="hidden" name="mac" value="{{ $device->mac_address }}">

                {{-- 
                    Question Display Loop
                    Shows all questions, but only one is visible at a time (controlled by JavaScript).
                    The first question (index 0) is visible by default, others are hidden.
                    JavaScript handles showing/hiding questions as child navigates.
                --}}
                @foreach($questions as $index => $question)
                    {{-- 
                        Question Card
                        - data-question-index: Used by JavaScript to identify which question to show
                        - hidden class: Hides all questions except first (index 0)
                        - JavaScript removes 'hidden' class to show current question
                    --}}
                    <div class="question-card mb-6 bg-white rounded-lg shadow-lg p-6 {{ $index === 0 ? '' : 'hidden' }}" data-question-index="{{ $index }}">
                        {{-- Question counter: "QUESTION 1 of 5" --}}
                        <div class="mb-4 text-sm font-medium text-gray-600">
                            QUESTION {{ $index + 1 }} of {{ count($questions) }}
                        </div>

                        <div class="mb-6">
                            {{-- Question text --}}
                            <p class="text-lg font-medium text-gray-900 mb-4">
                                {{ $index + 1 }}. {{ $question['question'] }}
                            </p>

                            {{-- 
                                Multiple Choice Question Type
                                Displays 4 radio buttons (A, B, C, D) with yellow background.
                                Child selects one option. Value sent is letter (a, b, c, d).
                                
                                Why chr(97 + $optionIndex)? 
                                - chr(97) = 'a', chr(98) = 'b', chr(99) = 'c', chr(100) = 'd'
                                - Converts array index (0,1,2,3) to letter (a,b,c,d)
                            --}}
                            @if($question['type'] === 'multiple_choice')
                                <div class="space-y-3">
                                    @foreach($question['options'] as $optionIndex => $option)
                                        <label class="flex items-center p-4 rounded-lg cursor-pointer hover:opacity-90 transition" 
                                               style="background-color: #FFDE15;">
                                            <input type="radio" 
                                                   name="answers[{{ $index }}]" 
                                                   value="{{ chr(97 + $optionIndex) }}" 
                                                   class="mr-3 w-5 h-5 text-yellow-600 focus:ring-yellow-500"
                                                   required>
                                            <span class="text-black font-medium">
                                                {{ chr(97 + $optionIndex) }}) {{ $option }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            {{-- 
                                Fill-in-the-Blank Question Type
                                Displays a text input field where child types their answer.
                                Answer is compared case-insensitively (e.g., "Paris" = "paris").
                            --}}
                            @elseif($question['type'] === 'fill_blank')
                                <div class="p-4 rounded-lg" style="background-color: #FFDE15;">
                                    <input type="text" 
                                           name="answers[{{ $index }}]" 
                                           placeholder="Enter your answer"
                                           class="w-full px-4 py-3 rounded border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                           required>
                                </div>
                            {{-- 
                                True/False Question Type
                                Displays 2 radio buttons (True, False) with yellow background.
                                Child selects one. Value sent is "True" or "False".
                            --}}
                            @elseif($question['type'] === 'true_false')
                                <div class="space-y-3">
                                    <label class="flex items-center p-4 rounded-lg cursor-pointer hover:opacity-90 transition" 
                                           style="background-color: #FFDE15;">
                                        <input type="radio" 
                                               name="answers[{{ $index }}]" 
                                               value="True" 
                                               class="mr-3 w-5 h-5 text-yellow-600 focus:ring-yellow-500"
                                               required>
                                        <span class="text-black font-medium">True</span>
                                    </label>
                                    <label class="flex items-center p-4 rounded-lg cursor-pointer hover:opacity-90 transition" 
                                           style="background-color: #FFDE15;">
                                        <input type="radio" 
                                               name="answers[{{ $index }}]" 
                                               value="False" 
                                               class="mr-3 w-5 h-5 text-yellow-600 focus:ring-yellow-500"
                                               required>
                                        <span class="text-black font-medium">False</span>
                                    </label>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            @if($index > 0)
                                <button type="button" 
                                        onclick="showPreviousQuestion()" 
                                        class="px-6 py-2 rounded-lg text-white font-medium hover:opacity-90"
                                        style="background-color: #EF4444;">
                                    ← GO PREVIOUS
                                </button>
                            @endif
                            @if($index < count($questions) - 1)
                                <button type="button" 
                                        onclick="showNextQuestion()" 
                                        class="px-6 py-2 rounded-lg text-white font-medium hover:opacity-90"
                                        style="background-color: #10B981;">
                                    NEXT →
                                </button>
                            @else
                                <button type="submit" 
                                        class="px-6 py-2 rounded-lg text-white font-medium hover:opacity-90"
                                        style="background-color: #10B981;">
                                    SUBMIT QUIZ
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
    </div>

    <script>
        let currentQuestion = 0;
        @php
            $totalQuestions = count($questions ?? []);
        @endphp
        const totalQuestions = {{ $totalQuestions }};
        let timeRemaining = 600; // 10 minutes in seconds
        let timerInterval;

        function showQuestion(index) {
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.add('hidden');
            });
            document.querySelector(`[data-question-index="${index}"]`).classList.remove('hidden');
            currentQuestion = index;
        }

        function showNextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                showQuestion(currentQuestion + 1);
            }
        }

        function showPreviousQuestion() {
            if (currentQuestion > 0) {
                showQuestion(currentQuestion - 1);
            }
        }

        function goBack() {
            if (confirm('Are you sure you want to leave? Your progress will be lost.')) {
                window.history.back();
            }
        }

        function updateTimer() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            document.getElementById('timer').textContent = 
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting quiz...');
                document.getElementById('quizForm').submit();
            }
            
            timeRemaining--;
        }

        // Start timer
        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();

        // Show first question on load
        document.addEventListener('DOMContentLoaded', function() {
            showQuestion(0);
        });
    </script>
</body>
</html>

