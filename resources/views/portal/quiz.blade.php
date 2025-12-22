<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz - {{ $quiz->title }}</title>
    
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
    <div class="min-h-screen">
        <!-- Header: Yellow bar with back button, quiz label, and timer -->
        <div class="bg-[#FFDE15] py-4 px-6 flex items-center justify-between shadow-md">
            <div class="flex items-center space-x-3">
                <button onclick="goBack()" class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:opacity-90 shadow-md transition">
                    <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="px-4 py-2 border-4 border-black rounded-lg bg-white">
                    <span class="font-extrabold text-black text-sm uppercase">QUIZ</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-semibold text-black uppercase">Time Remaining</div>
                <div class="text-2xl font-extrabold text-black" id="timer">10:00</div>
            </div>
        </div>

        <!-- Main Content: Quiz form with questions -->
        <div class="max-w-4xl mx-auto px-4 py-8">
            <form id="quizForm" action="{{ route('portal.quiz.submit', ['mac' => $device->mac_address]) }}" method="POST">
                @csrf
                <input type="hidden" name="mac" value="{{ $device->mac_address }}">

                @foreach($questions as $index => $question)
                    <div class="question-card mb-6 bg-white rounded-xl shadow-lg border-4 border-[#FFDE15] p-6 {{ $index === 0 ? '' : 'hidden' }}" data-question-index="{{ $index }}">
                        <!-- Question counter -->
                        <div class="mb-4 text-sm font-semibold text-gray-600 uppercase">
                            Question {{ $index + 1 }} of {{ count($questions) }}
                        </div>

                        <div class="mb-6">
                            <!-- Question text -->
                            <p class="text-xl font-extrabold text-black mb-6">
                                {{ $index + 1 }}. {{ $question['question'] }}
                            </p>

                            <!-- Multiple Choice -->
                            @if($question['type'] === 'multiple_choice')
                                <div class="space-y-3">
                                    @foreach($question['options'] as $optionIndex => $option)
                                        <label class="flex items-center p-4 rounded-xl cursor-pointer hover:opacity-90 transition border-4 border-[#FFDE15] bg-[#FFDE15]" 
                                               style="accent-color: #000;">
                                            <input type="radio" 
                                                   name="answers[{{ $index }}]" 
                                                   value="{{ chr(97 + $optionIndex) }}" 
                                                   class="mr-4 w-5 h-5"
                                                   required>
                                            <span class="text-black font-bold text-lg">
                                                {{ strtoupper(chr(97 + $optionIndex)) }}) {{ $option }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            <!-- Fill-in-the-Blank -->
                            @elseif($question['type'] === 'fill_blank')
                                <div class="p-4 rounded-xl border-4 border-[#FFDE15] bg-[#FFDE15]">
                                    <input type="text" 
                                           name="answers[{{ $index }}]" 
                                           placeholder="Enter your answer"
                                           class="w-full px-4 py-3 rounded-lg border-4 border-black focus:outline-none focus:ring-2 focus:ring-[#FFDE15] font-semibold text-black"
                                           required>
                                </div>
                            <!-- True/False -->
                            @elseif($question['type'] === 'true_false')
                                <div class="space-y-3">
                                    <label class="flex items-center p-4 rounded-xl cursor-pointer hover:opacity-90 transition border-4 border-[#FFDE15] bg-[#FFDE15]">
                                        <input type="radio" 
                                               name="answers[{{ $index }}]" 
                                               value="True" 
                                               class="mr-4 w-5 h-5"
                                               required>
                                        <span class="text-black font-bold text-lg">True</span>
                                    </label>
                                    <label class="flex items-center p-4 rounded-xl cursor-pointer hover:opacity-90 transition border-4 border-[#FFDE15] bg-[#FFDE15]">
                                        <input type="radio" 
                                               name="answers[{{ $index }}]" 
                                               value="False" 
                                               class="mr-4 w-5 h-5"
                                               required>
                                        <span class="text-black font-bold text-lg">False</span>
                                    </label>
                                </div>
                            @endif
                        </div>

                        <!-- Navigation buttons -->
                        <div class="flex justify-end space-x-3 mt-6">
                            @if($index > 0)
                                <button type="button" 
                                        onclick="showPreviousQuestion()" 
                                        class="px-6 py-3 rounded-full bg-red-600 text-white font-extrabold hover:bg-red-700 transition shadow-lg">
                                    ← Previous
                                </button>
                            @endif
                            @if($index < count($questions) - 1)
                                <button type="button" 
                                        onclick="showNextQuestion()" 
                                        class="px-6 py-3 rounded-full bg-green-600 text-white font-extrabold hover:bg-green-700 transition shadow-lg">
                                    Next →
                                </button>
                            @else
                                <button type="submit" 
                                        class="px-8 py-3 rounded-full bg-[#FFDE15] text-black font-extrabold hover:bg-[#FFC107] transition shadow-lg border-4 border-black">
                                    Submit Quiz
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
        const totalQuestions = {{ count($questions) }};
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
                window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
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
