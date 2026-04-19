<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal - Parental WiFi</title>
    <link rel="stylesheet" href="/css/portal-captive.css">
</head>
<body class="portal">
    <div class="portal-wrap">
        <div class="portal-inner">
            <header class="portal-hero">
                <h1 class="portal-title">Welcome!</h1>
                <p class="portal-subtitle">Complete activities to earn internet time</p>
            </header>

            @if(session('error'))
                <div class="portal-banner portal-banner--error">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @if(isset($error))
                <div class="portal-banner portal-banner--error">
                    <p>{{ $error }}</p>
                </div>
            @elseif($device)
                <section class="portal-card">
                    <h2 class="portal-card__title">
                        <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Your Device
                    </h2>
                    <div class="portal-stat-grid">
                        <div>
                            <p class="portal-stat-label">Device Name</p>
                            <p class="portal-stat-value">{{ $device->name }}</p>
                        </div>
                        <div>
                            <p class="portal-stat-label">Time Remaining</p>
                            <p class="portal-stat-value">{{ $device->getRemainingTimeFormatted() }}</p>
                        </div>
                    </div>
                </section>

                @if($quizzes->count() > 0)
                    <section class="portal-activities">
                        <h2 class="portal-section-title">
                            <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Available Quizzes
                        </h2>
                        <div class="portal-grid">
                            @foreach($quizzes as $quiz)
                                <a href="{{ route('portal.quiz.show', ['quiz' => $quiz->id, 'mac' => $device->mac_address]) }}"
                                   class="portal-tile">
                                    <div class="portal-tile__body">
                                        <h3 class="portal-tile__title">{{ $quiz->title }}</h3>
                                        <p class="portal-tile__desc">{{ $quiz->description ?? 'Take this quiz to earn internet time!' }}</p>
                                    </div>
                                    <div class="portal-tile__footer">
                                        <div>
                                            <p class="portal-tile__reward-label">Time Reward</p>
                                            <p class="portal-tile__reward">{{ $quiz->time_reward_minutes }} min</p>
                                        </div>
                                        <svg class="portal-tile__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($videos->count() > 0)
                    <section class="portal-activities">
                        <h2 class="portal-section-title">
                            <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Available Videos
                        </h2>
                        <div class="portal-grid">
                            @foreach($videos as $video)
                                <a href="{{ route('portal.video.show', ['video' => $video->id, 'mac' => $device->mac_address]) }}"
                                   class="portal-tile">
                                    <div class="portal-tile__body">
                                        <h3 class="portal-tile__title">{{ $video->title }}</h3>
                                        <p class="portal-tile__desc">{{ $video->description ?? 'Watch this video to earn internet time!' }}</p>
                                        <p class="portal-tile__meta">
                                            Duration: {{ gmdate('i:s', $video->duration_seconds) }}
                                            @if($video->dictionary_words_enabled)
                                                • {{ $video->word_count }} words
                                            @endif
                                        </p>
                                    </div>
                                    <div class="portal-tile__footer">
                                        <div>
                                            <p class="portal-tile__reward-label">Time Reward</p>
                                            <p class="portal-tile__reward">{{ $video->time_reward_minutes }} min</p>
                                        </div>
                                        <svg class="portal-tile__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($quizzes->count() === 0 && $videos->count() === 0)
                    <div class="portal-empty">
                        <svg class="portal-empty__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>No quizzes or videos available at this time.</p>
                        <p class="portal-muted-text">Please check back later or contact your parent.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
