<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz - {{ $quiz->title }}</title>
    <link rel="stylesheet" href="/css/portal-captive.css">
</head>
<body class="portal">
    <div class="portal-wrap">
        <header class="portal-topbar">
            <div class="portal-topbar__left">
                <button type="button" class="portal-icon-btn" onclick="goBack()" aria-label="Go back">
                    <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="portal-badge">Quiz</div>
            </div>
            <div class="portal-timer-block">
                <div class="portal-timer-label">Time Remaining</div>
                <div class="portal-timer-value" id="timer">10:00</div>
            </div>
        </header>

        <div class="portal-main">
            <form id="quizForm" action="{{ route('portal.quiz.submit', ['mac' => $device->mac_address]) }}" method="POST">
                @csrf
                <input type="hidden" name="mac" value="{{ $device->mac_address }}">

                @foreach($questions as $index => $question)
                    <div class="question-card portal-card"
                         data-question-index="{{ $index }}"
                         @if($index !== 0) style="display: none;" @endif>
                        <p class="portal-q-meta">Question {{ $index + 1 }} of {{ count($questions) }}</p>

                        <p class="portal-q-text">{{ $index + 1 }}. {{ $question['question'] }}</p>

                        @if($question['type'] === 'multiple_choice')
                            <div class="portal-mc-list">
                                @foreach($question['options'] as $optionIndex => $option)
                                    <label class="portal-mc-label">
                                        <input type="radio"
                                               name="answers[{{ $index }}]"
                                               value="{{ strtoupper(chr(65 + $optionIndex)) }}"
                                               required>
                                        <span>{{ strtoupper(chr(65 + $optionIndex)) }}) {{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($question['type'] === 'fill_blank')
                            <div class="portal-fill-wrap">
                                <input type="text"
                                       name="answers[{{ $index }}]"
                                       placeholder="Enter your answer"
                                       class="portal-text-input"
                                       required
                                       autocomplete="off">
                            </div>
                        @elseif($question['type'] === 'true_false')
                            <div class="portal-mc-list">
                                <label class="portal-mc-label">
                                    <input type="radio" name="answers[{{ $index }}]" value="True" required>
                                    <span>True</span>
                                </label>
                                <label class="portal-mc-label">
                                    <input type="radio" name="answers[{{ $index }}]" value="False" required>
                                    <span>False</span>
                                </label>
                            </div>
                        @endif

                        <div class="portal-actions">
                            @if($index > 0)
                                <button type="button" class="portal-btn portal-btn--danger" onclick="showPreviousQuestion()">
                                    ← Previous
                                </button>
                            @endif
                            @if($index < count($questions) - 1)
                                <button type="button" class="portal-btn portal-btn--success" onclick="showNextQuestion()">
                                    Next →
                                </button>
                            @else
                                <button type="submit" class="portal-btn portal-btn--submit">
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
        let timeRemaining = 600;
        let timerInterval;

        function showQuestion(index) {
            document.querySelectorAll('.question-card').forEach((card) => {
                const i = parseInt(card.getAttribute('data-question-index'), 10);
                card.style.display = i === index ? 'block' : 'none';
            });
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
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting quiz...');
                document.getElementById('quizForm').submit();
                return;
            }

            timeRemaining--;
        }

        timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
        showQuestion(0);
    </script>
</body>
</html>
