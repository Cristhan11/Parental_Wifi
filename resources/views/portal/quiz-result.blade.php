<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quiz Result - Parental WiFi</title>
    <link rel="stylesheet" href="/css/portal-captive.css">
</head>
<body class="portal">
    <div class="portal-result-page">
        <div class="portal-result-card">
            @if($attempt->passed)
                <div class="portal-spacer-bottom">
                    <div class="portal-result-icon portal-result-icon--ok portal-spacer-bottom">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="portal-result-title portal-result-title--ok">Congratulations!</h2>
                    <p class="portal-result-msg">You passed the quiz!</p>
                </div>

                <div class="portal-score-box portal-spacer-bottom">
                    <div class="portal-score-big">{{ $attempt->score }}%</div>
                    <div class="portal-score-label">Your Score</div>
                </div>

                @if($timeGranted > 0)
                    <div class="portal-celebrate portal-spacer-bottom">
                        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🎉</div>
                        <p class="portal-celebrate-title">
                            You earned {{ $timeGranted }} minutes of internet time!
                        </p>
                        <p class="portal-muted-text">You will be redirected automatically...</p>
                    </div>
                @endif

                <div class="portal-countdown-line portal-spacer-bottom">
                    Redirecting in <span id="countdown" class="portal-countdown-num">3</span> seconds...
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
                <div class="portal-spacer-bottom">
                    <div class="portal-result-icon portal-result-icon--bad portal-spacer-bottom">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="portal-result-title portal-result-title--bad">Try Again</h2>
                    <p class="portal-result-msg">You did not pass this time.</p>
                </div>

                <div class="portal-score-box portal-spacer-bottom">
                    <div class="portal-score-big">{{ $attempt->score }}%</div>
                    <div class="portal-score-label" style="margin-bottom:0.35rem;">Your Score</div>
                    <div class="portal-muted-text">Required: {{ $attempt->quiz->passing_score }}%</div>
                </div>

                <div class="portal-info-box portal-spacer-bottom">
                    <p>
                        Don't worry! You can try again. Study the material and take the quiz once more to earn internet time.
                    </p>
                </div>

                <div class="portal-result-actions">
                    <a href="{{ route('portal.quiz.show', ['quiz' => $attempt->quiz->id, 'mac' => $device->mac_address]) }}"
                       class="portal-btn portal-btn--success">
                        Retry Quiz
                    </a>
                    <a href="{{ route('portal.landing', ['mac' => $device->mac_address]) }}"
                       class="portal-btn portal-btn--outline">
                        Go Back
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
