<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Result - Parental WiFi</title>
    @include('portal.partials.head-favicon')
    @include('portal.partials.head-assets')
</head>
<body class="portal">
    <div class="portal-result-page">
        <div class="portal-result-card">
            @if($completion->passed_validation)
                <div class="portal-spacer-bottom">
                    <div class="portal-result-icon portal-result-icon--ok portal-spacer-bottom">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="portal-result-title portal-result-title--ok">Congratulations!</h2>
                    <p class="portal-result-msg">You correctly entered all the words!</p>
                </div>

                <div class="portal-score-box portal-spacer-bottom">
                    <div class="portal-score-big">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                    <div class="portal-score-label">Words Correct</div>
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
                    <p class="portal-result-msg">
                        @if((int) ($completion->word_guess_failed_count ?? 0) >= 3)
                            You used all 3 tries and the words were still not all correct.
                        @else
                            You did not enter all words correctly.
                        @endif
                    </p>
                </div>

                <div class="portal-score-box portal-spacer-bottom">
                    <div class="portal-score-big">{{ $completion->words_correct }} / {{ $completion->words_shown_count }}</div>
                    <div class="portal-score-label">Words Correct</div>
                </div>

                @if(count($wordsShown) > 0)
                    <div class="portal-blue-box portal-spacer-bottom">
                        <h3>The correct words were:</h3>
                        <p style="margin:0 0 0.5rem;font-weight:800;font-size:1.1rem;color:#333;">{{ implode(', ', $wordsShown) }}</p>
                        <p class="portal-muted-text">Watch the video again and remember these words!</p>
                    </div>
                @endif

                <div class="portal-info-box portal-spacer-bottom">
                    <p>
                        Don't worry! You can watch the video again. Pay attention to the dictionary words
                        that appear, and enter them all correctly to earn internet time.
                    </p>
                </div>

                <div class="portal-result-actions">
                    <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}"
                       class="portal-btn portal-btn--success">
                        Watch Video Again
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
