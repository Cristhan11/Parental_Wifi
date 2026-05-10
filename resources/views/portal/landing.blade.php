<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Portal - Parental WiFi</title>
    @include('portal.partials.head-favicon')
    <link rel="stylesheet" href="/css/portal-captive.css">
</head>
<body class="portal portal--landing">
    <div class="portal-wrap">
        <div class="portal-inner">
            @php
                $portalBase = array_filter(['mac' => $device->mac_address ?? null, 'tok' => request('tok')]);
                $portalFlow = $flow ?? 'chooser';
                $portalShowEarnHeader = ! empty($showDeviceRegistration) || empty($device) || $portalFlow === 'chooser';
            @endphp

            @if($portalShowEarnHeader)
                <header class="portal-hero portal-hero--compact">
                    <div class="portal-brand-mark" aria-hidden="true">
                        <img src="{{ asset('PARENTAL_WIFI_LOGO.png') }}" alt="" width="56" height="56" decoding="async">
                    </div>
                    <h1 class="portal-title">Earn time</h1>
                    <p class="portal-subtitle">
                        @if(!empty($device))
                            Quiz or video — earn minutes.
                        @elseif(!empty($showDeviceRegistration))
                            New device? Ask a parent to add you, or send a request below.
                        @else
                            Connect to home Wi‑Fi, then open this page again.
                        @endif
                    </p>
                </header>
            @endif

            @if(session('portal_info'))
                <div class="portal-banner portal-banner--info">
                    <p>{{ session('portal_info') }}</p>
                </div>
            @endif

            @if(session('success'))
                <div class="portal-banner portal-banner--success">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="portal-banner portal-banner--error">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @error('device_name')
                <div class="portal-banner portal-banner--error">
                    <p>{{ $message }}</p>
                </div>
            @enderror

            @if(isset($error) && $error)
                <div class="portal-banner portal-banner--error">
                    <p>{{ $error }}</p>
                </div>
            @endif

            @if(!empty($showDeviceRegistration))
                <section class="portal-card portal-card--register" aria-labelledby="register-heading">
                    <h2 id="register-heading" class="portal-card__title">
                        <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New device
                    </h2>
                    <p class="portal-register-lead">Pick a name your parent will recognize, then tap the button. They will see your request on the Accounts page.</p>
                    <form method="POST" action="{{ route('device-request.store') }}" class="portal-register-form">
                        @csrf
                        <label class="portal-field-label" for="device_name">Device name</label>
                        <input id="device_name" name="device_name" type="text" class="portal-input" value="{{ old('device_name') }}" placeholder="Example: Miguel tablet" required autocomplete="off" maxlength="255">
                        <button type="submit" class="portal-btn-primary">Request to Register</button>
                    </form>
                </section>
            @elseif($device)
                <aside class="portal-meta-strip" aria-label="Device status">
                    <span class="portal-meta-strip__name">{{ $device->name }}</span>
                    <span class="portal-meta-strip__time">{{ $device->getRemainingTimeFormatted() }} left</span>
                </aside>

                @if(($flow ?? 'chooser') === 'chooser')
                    <section class="portal-stage" aria-label="Choose activity type">
                        <p class="portal-stage__tagline">Pick one</p>
                        <div class="portal-type-pair">
                            @if($eligibleQuizzes->isNotEmpty() || $randomMixEligible)
                                <a class="portal-type-card portal-type-card--quiz" href="{{ route('portal.landing', array_merge($portalBase, ['flow' => ($eligibleQuizzes->isEmpty() && $randomMixEligible) ? 'quiz_more' : 'quiz'])) }}">
                                    <span class="portal-type-card__icon" aria-hidden="true">
                                        <svg viewBox="0 0 48 48" width="40" height="40" fill="none"><path d="M12 8h24v6H12V8zm0 10h24v22H12V18zm4 4v14h16V22H16z" fill="currentColor"/></svg>
                                    </span>
                                    <span class="portal-type-card__label">Quiz</span>
                                    <span class="portal-type-card__hint">Questions &amp; brain snacks</span>
                                </a>
                            @else
                                <div class="portal-type-card portal-type-card--disabled" role="group" aria-labelledby="quiz-unavailable-title">
                                    <span id="quiz-unavailable-title" class="portal-type-card__label">Quiz</span>
                                    <p class="portal-type-card__hint">No quizzes assigned yet. Ask a parent to assign one on the dashboard.</p>
                                </div>
                            @endif

                            @if($eligibleVideos->isNotEmpty())
                                <a class="portal-type-card portal-type-card--video" href="{{ route('portal.landing', array_merge($portalBase, ['flow' => 'video'])) }}">
                                    <span class="portal-type-card__icon portal-type-card__icon--video" aria-hidden="true">
                                        <svg viewBox="0 0 48 48" width="40" height="40" fill="none" focusable="false">
                                            <rect x="9" y="12" width="30" height="20" rx="3.5" ry="3.5" stroke="currentColor" stroke-width="2.25" fill="none"/>
                                            <path fill="currentColor" d="M19 16v12l10-6-10-6z"/>
                                        </svg>
                                    </span>
                                    <span class="portal-type-card__label">Video</span>
                                    <span class="portal-type-card__hint">Watch &amp; remember the words</span>
                                </a>
                            @else
                                <div class="portal-type-card portal-type-card--disabled">
                                    <span class="portal-type-card__label">Video</span>
                                    <p class="portal-type-card__hint">No videos assigned yet.</p>
                                </div>
                            @endif
                        </div>
                        @if($eligibleQuizzes->isEmpty() && $eligibleVideos->isEmpty())
                            <div class="portal-empty portal-empty--inline">
                                <p>Nothing to do here yet. Ask a parent to assign a quiz or video.</p>
                            </div>
                        @endif
                        <p class="portal-captive-hint">Opened from Wi‑Fi sign‑in? You’re in the right place.</p>
                    </section>
                @elseif(($flow ?? '') === 'quiz')
                    <div class="portal-flow-nav portal-flow-nav--tight">
                        <a href="{{ route('portal.landing', $portalBase) }}" class="portal-back-link">← Back</a>
                    </div>
                    @if($recommendedQuiz)
                        <section class="portal-reco portal-reco--quiz-tight" aria-labelledby="reco-quiz-title">
                            <h2 id="reco-quiz-title" class="portal-reco__title">Your quiz</h2>
                            <div class="portal-reco-grid">
                                <div class="portal-reco-card">
                                    <h3 class="portal-reco-card__name">{{ $recommendedQuiz->title }}</h3>
                                    @if($recommendedQuiz->description)
                                        <p class="portal-reco-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($recommendedQuiz->description), 100) }}</p>
                                    @endif
                                    <p class="portal-reco-card__reward">
                                        @if($recommendedQuiz->scoring_mode === 'time_reward')
                                            +{{ (int) $recommendedQuiz->minutes_per_correct }} min per correct answer
                                        @else
                                            Pass for {{ (int) $recommendedQuiz->time_reward_minutes }} min
                                        @endif
                                    </p>
                                    <a class="portal-btn-start" href="{{ route('portal.quiz.show', array_merge($portalBase, ['quiz' => $recommendedQuiz->id])) }}">Start</a>
                                    <a class="portal-link-more" href="{{ route('portal.landing', array_merge($portalBase, ['flow' => 'quiz_more'])) }}">More quizzes</a>
                                </div>
                                @if($randomMixEligible && $randomModeQuiz)
                                    <div class="portal-reco-random-card">
                                        <a class="portal-btn-random" href="{{ route('portal.quiz.show', array_merge($portalBase, ['quiz' => $randomModeQuiz->id])) }}">Random quiz</a>
                                        <p class="portal-btn-random-hint">Mixed topics — each correct answer earns time.</p>
                                    </div>
                                @endif
                            </div>
                        </section>
                    @else
                        <div class="portal-empty">
                            <p>No quiz ready right now.</p>
                            <a class="portal-back-link" href="{{ route('portal.landing', $portalBase) }}">← Back</a>
                        </div>
                    @endif
                @elseif(($flow ?? '') === 'video')
                    <div class="portal-flow-nav portal-flow-nav--tight">
                        <a href="{{ route('portal.landing', $portalBase) }}" class="portal-back-link">← Back</a>
                    </div>
                    @if($recommendedVideo)
                        <section class="portal-reco portal-reco--quiz-tight" aria-labelledby="reco-video-title">
                            <h2 id="reco-video-title" class="portal-reco__title">Your video</h2>
                            <div class="portal-reco-card">
                                <h3 class="portal-reco-card__name">{{ $recommendedVideo->title }}</h3>
                                @if($recommendedVideo->description)
                                    <p class="portal-reco-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($recommendedVideo->description), 100) }}</p>
                                @endif
                                <p class="portal-reco-card__reward">Earn {{ (int) $recommendedVideo->time_reward_minutes }} min when you finish</p>
                                <a class="portal-btn-start" href="{{ route('portal.video.show', array_merge($portalBase, ['video' => $recommendedVideo->id])) }}">Start</a>
                                <a class="portal-link-more" href="{{ route('portal.landing', array_merge($portalBase, ['flow' => 'video_more'])) }}">More videos</a>
                            </div>
                        </section>
                    @else
                        <div class="portal-empty">
                            <p>No video ready right now.</p>
                            <a class="portal-back-link" href="{{ route('portal.landing', $portalBase) }}">← Back</a>
                        </div>
                    @endif
                @elseif(($flow ?? '') === 'quiz_more')
                    <div class="portal-flow-nav">
                        <a href="{{ route('portal.landing', array_merge($portalBase, ['flow' => 'quiz'])) }}" class="portal-back-link">← Back</a>
                    </div>
                    <h2 class="portal-section-title portal-section-title--left">All your quizzes</h2>

                    @if($quizGroups['Other']->isNotEmpty())
                        <div class="portal-other-random-row">
                            <div class="portal-chip-stack">
                                <span class="portal-chip portal-chip--static">Other</span>
                                <div class="portal-mini-grid">
                                    @foreach($quizGroups['Other'] as $quiz)
                                        <a href="{{ route('portal.quiz.show', array_merge($portalBase, ['quiz' => $quiz->id])) }}" class="portal-tile portal-tile--compact">
                                            <span class="portal-tile__title">{{ $quiz->title }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @foreach(['Math', 'English', 'Science'] as $subject)
                        @if($quizGroups[$subject]->isNotEmpty())
                            <section class="portal-browse-block">
                                <h3 class="portal-browse-block__heading">{{ $subject }}</h3>
                                <div class="portal-mini-grid">
                                    @foreach($quizGroups[$subject] as $quiz)
                                        <a href="{{ route('portal.quiz.show', array_merge($portalBase, ['quiz' => $quiz->id])) }}" class="portal-tile portal-tile--compact">
                                            <span class="portal-tile__title">{{ $quiz->title }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    @if($quizGroups['Math']->isEmpty() && $quizGroups['English']->isEmpty() && $quizGroups['Science']->isEmpty() && $quizGroups['Other']->isEmpty())
                        <p class="portal-muted-text">No quizzes in your list.</p>
                    @endif
                @elseif(($flow ?? '') === 'video_more')
                    <div class="portal-flow-nav">
                        <a href="{{ route('portal.landing', array_merge($portalBase, ['flow' => 'video'])) }}" class="portal-back-link">← Back</a>
                    </div>
                    <h2 class="portal-section-title portal-section-title--left">All your videos</h2>
                    <div class="portal-mini-grid">
                        @foreach($eligibleVideos as $video)
                            <a href="{{ route('portal.video.show', array_merge($portalBase, ['video' => $video->id])) }}" class="portal-tile portal-tile--compact">
                                <span class="portal-tile__title">{{ $video->title }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
</body>
</html>
