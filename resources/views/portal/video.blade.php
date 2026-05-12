<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video - {{ $video->title }}</title>
    @include('portal.partials.head-favicon')
    @include('portal.partials.head-assets')
    <style>
        /* Overlay must never steal taps from the video (dictionary cards are non-interactive). */
        #wordOverlayContainer {
            pointer-events: none !important;
        }
        #videoPlayer {
            pointer-events: auto !important;
            touch-action: manipulation;
        }
        /*
         * Do NOT hide ::-webkit-media-controls-overlay-play-button or clip the controls enclosure:
         * on iOS/Android WebKit that removes the main play target and can break the control bar.
         * Hide timeline / time / speed where Blink/WebKit allow it; mobile also gets JS seek clamp below.
         */
        #videoPlayer::-webkit-media-controls-timeline {
            display: none !important;
        }
        #videoPlayer::-webkit-media-controls-current-time-display {
            display: none !important;
        }
        #videoPlayer::-webkit-media-controls-time-remaining-display {
            display: none !important;
        }
        #videoPlayer::-webkit-media-controls-playback-rate-button {
            display: none !important;
        }
        @if($video->dictionary_words_enabled)
        #videoPlayer::-webkit-media-controls-fullscreen-button {
            display: none !important;
        }
        @endif
    </style>
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
                <div class="portal-badge">Video</div>
            </div>
            <div class="portal-topbar__right">
                <div class="portal-topbar__title">{{ $video->title }}</div>
            </div>
        </header>

        <div class="portal-main">
            <div class="portal-card">
                <h2 class="portal-video-title">{{ $video->title }}</h2>
                @if(!empty($wordRetryMode))
                    <div class="portal-banner portal-banner--info portal-spacer-bottom" role="status">
                        <strong>Try the words again.</strong>
                        You already watched the video — pick the words in order. This is try
                        <strong>{{ (int) $wordGuessFailedCount + 1 }}</strong> of
                        <strong>{{ (int) $videoWordGuessMaxFailures }}</strong>.
                    </div>
                @endif
                @if(session('portal_video_word_retries_left'))
                    <div class="portal-banner portal-banner--info portal-spacer-bottom" role="status">
                        Not quite right. You have <strong>{{ (int) session('portal_video_word_retries_left') }}</strong>
                        more {{ (int) session('portal_video_word_retries_left') === 1 ? 'try' : 'tries' }} before you need to watch the video again.
                    </div>
                @endif
                @if($video->description)
                    <p class="portal-video-desc">{{ $video->description }}</p>
                @endif

                <div id="portalVideoStage" class="portal-video-frame">
                    <video
                        id="videoPlayer"
                        controls
                        preload="auto"
                        playsinline
                        webkit-playsinline
                        x5-playsinline
                        @if($video->dictionary_words_enabled)
                        controlslist="nofullscreen noremoteplayback"
                        disablepictureinpicture
                        @endif
                        ontimeupdate="handleTimeUpdate()"
                        onended="handleVideoEnded()"
                        onerror="handleVideoError(event)"
                        oncanplay="handleVideoCanPlay()">
                        <source src="{{ route('portal.video.stream', ['video' => $video->id, 'mac' => $device->mac_address], false) }}" type="{{ $video->getMimeType() }}">
                        Your browser does not support the video tag.
                    </video>
                    <div id="wordOverlayContainer" class="portal-video-overlay-host"></div>
                    @if($video->dictionary_words_enabled)
                        <button type="button" id="portalVideoFsBtn" class="portal-video-fs-btn" aria-pressed="false">
                            Fullscreen
                        </button>
                    @endif
                </div>

                <div class="portal-video-fallback-controls">
                    <button type="button" id="portalVideoPlayPauseBtn" class="portal-video-play-btn">
                        Play
                    </button>
                </div>

                <div class="portal-video-info">
                    <p class="portal-video-instruction-label">Instruction</p>
                    <p style="margin:0 0 0.35rem;">Duration: {{ $video->getDurationFormatted() }}</p>
                    @if($video->dictionary_words_enabled)
                        <p style="margin:0;">
                            <strong>Watch carefully!</strong> {{ $video->word_count }} dictionary words will appear during the video.
                            When the video ends, tap the colorful words <strong>in the order they appeared</strong>.
                        </p>
                        <p class="portal-video-fs-hint">
                            For fullscreen, use the yellow <strong>Fullscreen</strong> on the video (not the player’s own fullscreen button) so word pop-ups stay visible.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($video->dictionary_words_enabled)
        <div id="portalWordGameModal" class="portal-word-game-modal hidden" aria-hidden="true">
            <div class="portal-word-game-modal__backdrop" aria-hidden="true"></div>
            <div class="portal-word-game-modal__panel" role="dialog" aria-modal="true" aria-labelledby="portalWordGameTitle">
                @php
                    $portalWordMaxWrong = (int) ($videoWordGuessMaxFailures ?? 3);
                    $portalWordFailed = (int) ($completion->word_guess_failed_count ?? 0);
                    $portalWordTriesLeft = max(0, $portalWordMaxWrong - $portalWordFailed);
                    $portalWordTriesAria = $portalWordTriesLeft === 1
                        ? '1 wrong try left before you must watch the video again.'
                        : $portalWordTriesLeft.' wrong tries left before you must watch the video again.';
                @endphp
                <div class="portal-word-game-modal__head">
                    <h3 id="portalWordGameTitle" class="portal-word-game-modal__title">Pick the words in order!</h3>
                    @if($portalWordTriesLeft > 0)
                        <div class="portal-word-game-modal__tries-badge" role="status" aria-label="{{ $portalWordTriesAria }}">
                            <span class="portal-word-game-modal__tries-k">Tries</span>
                            <span class="portal-word-game-modal__tries-v">{{ $portalWordTriesLeft }}</span>
                        </div>
                    @endif
                </div>
                <p class="portal-word-game-modal__lead">
                    Tap <strong>{{ $video->word_count }}</strong> words in order.
                </p>

                <form id="wordsForm" action="{{ route('portal.video.submit', ['mac' => $device->mac_address]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="mac" value="{{ $device->mac_address }}">
                    <div id="wordsHiddenInputs" class="portal-word-game-modal__hidden-inputs"></div>

                    <p class="portal-word-game-modal__section-label">Your picks</p>
                    <div id="portalWordGamePicks" class="portal-word-game__picks" aria-live="polite"></div>
                    <div class="portal-word-game-modal__toolbar">
                        <button type="button" id="portalWordGameUndo" class="portal-btn portal-btn--outline portal-word-game-modal__tool-btn" disabled>Undo last</button>
                        <button type="button" id="portalWordGameClear" class="portal-btn portal-btn--outline portal-word-game-modal__tool-btn" disabled>Clear all</button>
                    </div>

                    <p class="portal-word-game-modal__section-label">Tap a word</p>
                    <div id="portalWordGameBank" class="portal-word-game__bank"></div>

                    <div class="portal-word-game-modal__actions">
                        <button type="submit" id="portalWordGameSubmit" class="portal-btn portal-btn--success portal-word-game-modal__submit" disabled>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        const videoPlayer = document.getElementById('videoPlayer');
        const videoStage = document.getElementById('portalVideoStage');
        const portalFsBtn = document.getElementById('portalVideoFsBtn');
        const wordOverlayContainer = document.getElementById('wordOverlayContainer');
        const wordGameModal = document.getElementById('portalWordGameModal');
        const wordsData = @json($wordsData);
        const distractorWords = @json($distractorWords);
        const wordsInOrder = wordsData.map(function (w) { return w.word; });
        const expectedWordCount = wordsInOrder.length;
        const shownWords = [];
        /** How long each dictionary word overlay stays visible (8s + 3s). */
        const dictionaryWordDisplayMs = 11000;
        const dictionaryWordsEnabled = @json((bool) $video->dictionary_words_enabled);
        let portalFsRedirecting = false;

        function portalFullscreenElement() {
            return document.fullscreenElement
                || document.webkitFullscreenElement
                || document.mozFullScreenElement
                || document.msFullscreenElement;
        }

        function portalExitAllFullscreen() {
            try {
                if (videoPlayer && typeof videoPlayer.webkitExitFullscreen === 'function') {
                    videoPlayer.webkitExitFullscreen();
                }
            } catch (e) {
                console.warn('video webkitExitFullscreen:', e);
            }
            const exit = document.exitFullscreen
                || document.webkitExitFullscreen
                || document.webkitCancelFullScreen
                || document.mozCancelFullScreen
                || document.msExitFullscreen;
            if (exit && portalFullscreenElement()) {
                try {
                    const p = exit.call(document);
                    if (p && typeof p.catch === 'function') {
                        p.catch(function () {});
                    }
                } catch (e) {
                    console.warn('document exitFullscreen:', e);
                }
            }
        }

        function portalOpenWordGameModal() {
            if (!wordGameModal) {
                return;
            }
            wordGameModal.classList.remove('hidden');
            wordGameModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('portal-word-game-open');
        }

        function portalShuffle(arr) {
            const a = arr.slice();
            for (let i = a.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                const t = a[i];
                a[i] = a[j];
                a[j] = t;
            }
            return a;
        }

        const portalWordGameChipClasses = [
            'portal-word-game-chip--0',
            'portal-word-game-chip--1',
            'portal-word-game-chip--2',
            'portal-word-game-chip--3',
            'portal-word-game-chip--4',
            'portal-word-game-chip--5',
            'portal-word-game-chip--6',
            'portal-word-game-chip--7',
        ];

        let portalWordGameSelected = [];
        let portalWordGameSelectedButtons = [];

        function portalWordGameSyncUi() {
            const picksEl = document.getElementById('portalWordGamePicks');
            const hiddenWrap = document.getElementById('wordsHiddenInputs');
            const undoBtn = document.getElementById('portalWordGameUndo');
            const clearBtn = document.getElementById('portalWordGameClear');
            const submitBtn = document.getElementById('portalWordGameSubmit');
            if (!picksEl || !hiddenWrap) {
                return;
            }
            picksEl.innerHTML = '';
            hiddenWrap.innerHTML = '';
            portalWordGameSelected.forEach(function (word, idx) {
                const pill = document.createElement('span');
                pill.className = 'portal-word-game__pick-pill';
                pill.textContent = (idx + 1) + '. ' + word;
                picksEl.appendChild(pill);
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'words[]';
                inp.value = word;
                hiddenWrap.appendChild(inp);
            });
            const n = portalWordGameSelected.length;
            const canEdit = n > 0;
            if (undoBtn) {
                undoBtn.disabled = !canEdit;
            }
            if (clearBtn) {
                clearBtn.disabled = !canEdit;
            }
            if (submitBtn) {
                submitBtn.disabled = (expectedWordCount <= 0) || (n !== expectedWordCount);
            }
        }

        function portalInitWordGameIfNeeded() {
            if (!dictionaryWordsEnabled || !wordGameModal || expectedWordCount <= 0) {
                return;
            }
            const bank = document.getElementById('portalWordGameBank');
            if (!bank || bank.dataset.initialized === '1') {
                return;
            }
            bank.dataset.initialized = '1';
            const pool = portalShuffle(wordsInOrder.concat(distractorWords));
            pool.forEach(function (word, i) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'portal-word-game-chip ' + portalWordGameChipClasses[i % portalWordGameChipClasses.length];
                btn.textContent = word;
                btn.dataset.word = word;
                btn.addEventListener('click', function () {
                    if (btn.disabled || portalWordGameSelected.length >= expectedWordCount) {
                        return;
                    }
                    portalWordGameSelected.push(word);
                    portalWordGameSelectedButtons.push(btn);
                    btn.disabled = true;
                    portalWordGameSyncUi();
                });
                bank.appendChild(btn);
            });
            const undoBtn = document.getElementById('portalWordGameUndo');
            const clearBtn = document.getElementById('portalWordGameClear');
            if (undoBtn) {
                undoBtn.addEventListener('click', function () {
                    if (portalWordGameSelected.length === 0) {
                        return;
                    }
                    portalWordGameSelected.pop();
                    const b = portalWordGameSelectedButtons.pop();
                    if (b) {
                        b.disabled = false;
                    }
                    portalWordGameSyncUi();
                });
            }
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    portalWordGameSelected = [];
                    while (portalWordGameSelectedButtons.length) {
                        const b = portalWordGameSelectedButtons.pop();
                        if (b) {
                            b.disabled = false;
                        }
                    }
                    portalWordGameSyncUi();
                });
            }
            portalWordGameSyncUi();
        }

        function portalToggleStageFullscreen() {
            if (!videoStage) {
                return;
            }
            if (!portalFullscreenElement()) {
                const req = videoStage.requestFullscreen
                    || videoStage.webkitRequestFullscreen
                    || videoStage.mozRequestFullScreen
                    || videoStage.msRequestFullscreen;
                if (req) {
                    req.call(videoStage).catch(function (err) {
                        console.warn('Fullscreen failed:', err);
                    });
                }
            } else {
                const exit = document.exitFullscreen
                    || document.webkitExitFullscreen
                    || document.webkitCancelFullScreen
                    || document.mozCancelFullScreen
                    || document.msExitFullscreen;
                if (exit) {
                    exit.call(document);
                }
            }
        }

        function portalOnFullscreenChange() {
            /* Native <video> fullscreen hides our overlay (sibling of <video>). Move fullscreen to the stage wrapper. */
            if (dictionaryWordsEnabled && videoPlayer && videoStage && !portalFsRedirecting) {
                const active = portalFullscreenElement();
                if (active === videoPlayer) {
                    portalFsRedirecting = true;
                    try {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        } else if (document.webkitCancelFullScreen) {
                            document.webkitCancelFullScreen();
                        } else if (document.mozCancelFullScreen) {
                            document.mozCancelFullScreen();
                        } else if (document.msExitFullscreen) {
                            document.msExitFullscreen();
                        }
                    } catch (e) {
                        console.warn('Exit video fullscreen failed:', e);
                    }
                    try {
                        const req = videoStage.requestFullscreen
                            || videoStage.webkitRequestFullscreen
                            || videoStage.webkitRequestFullScreen
                            || videoStage.mozRequestFullScreen
                            || videoStage.msRequestFullscreen;
                        if (req) {
                            req.call(videoStage);
                        }
                    } catch (e) {
                        console.warn('Enter stage fullscreen failed:', e);
                    }
                    window.setTimeout(function () {
                        portalFsRedirecting = false;
                    }, 600);
                }
            }

            if (!portalFsBtn || !videoStage) {
                return;
            }
            const on = portalFullscreenElement() === videoStage;
            portalFsBtn.textContent = on ? 'Exit fullscreen' : 'Fullscreen';
            portalFsBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        }

        if (portalFsBtn && videoStage) {
            portalFsBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                portalToggleStageFullscreen();
            });
            document.addEventListener('fullscreenchange', portalOnFullscreenChange);
            document.addEventListener('webkitfullscreenchange', portalOnFullscreenChange);
            document.addEventListener('mozfullscreenchange', portalOnFullscreenChange);
            document.addEventListener('MSFullscreenChange', portalOnFullscreenChange);

            /* iOS WebKit: video-only fullscreen does not include DOM overlays; exit so the child can use the yellow stage fullscreen control. */
            if (dictionaryWordsEnabled && videoPlayer) {
                videoPlayer.addEventListener('webkitbeginfullscreen', function () {
                    if (portalFsRedirecting) {
                        return;
                    }
                    portalFsRedirecting = true;
                    try {
                        if (typeof videoPlayer.webkitExitFullscreen === 'function') {
                            videoPlayer.webkitExitFullscreen();
                        }
                    } catch (e) {
                        console.warn('webkitExitFullscreen failed:', e);
                    }
                    window.setTimeout(function () {
                        portalFsRedirecting = false;
                    }, 400);
                });
            }
        }

        const playPauseBtn = document.getElementById('portalVideoPlayPauseBtn');

        function syncPlayPauseLabel() {
            if (!playPauseBtn || !videoPlayer) {
                return;
            }
            playPauseBtn.textContent = videoPlayer.paused ? 'Play' : 'Pause';
            playPauseBtn.setAttribute('aria-label', videoPlayer.paused ? 'Play video' : 'Pause video');
        }

        if (playPauseBtn && videoPlayer) {
            playPauseBtn.addEventListener('click', function () {
                if (videoPlayer.paused) {
                    videoPlayer.play().catch(function (err) {
                        console.warn('play() failed:', err);
                        alert('Could not start playback. Try again or use the controls on the video.');
                    });
                } else {
                    videoPlayer.pause();
                }
            });
            videoPlayer.addEventListener('play', syncPlayPauseLabel);
            videoPlayer.addEventListener('pause', syncPlayPauseLabel);
            videoPlayer.addEventListener('ended', syncPlayPauseLabel);
            videoPlayer.addEventListener('loadeddata', syncPlayPauseLabel);
        }

        function handleVideoError(event) {
            const video = event.target;
            console.error('Video error:', {
                error: video.error,
                code: video.error ? video.error.code : null,
                message: video.error ? video.error.message : null,
                networkState: video.networkState,
                readyState: video.readyState,
                src: video.src
            });

            if (video.error) {
                let errorMsg = 'Video playback error: ';
                switch (video.error.code) {
                    case 1: errorMsg += 'Video loading aborted. Please try refreshing the page.'; break;
                    case 2: errorMsg += 'Network error while loading video. Please check your connection.'; break;
                    case 3: errorMsg += 'Playback failed (decode). Often this is a bad or interrupted download—try refreshing. If it keeps happening, re-encode the file as H.264 + AAC in MP4, or try another browser.'; break;
                    case 4: errorMsg += 'Video format not supported. Please use MP4, WebM, or OGG format.'; break;
                    default: errorMsg += 'Unknown error occurred. Please try again.';
                }
                alert(errorMsg);
            }
        }

        function handleVideoCanPlay() {
            videoPlayer.style.pointerEvents = 'auto';
            syncPlayPauseLabel();
        }

        function handleTimeUpdate() {
            const currentTime = Math.floor(videoPlayer.currentTime);
            wordsData.forEach((wordData, index) => {
                const wordTimestamp = wordData.timestamp;
                if (currentTime >= wordTimestamp && currentTime < wordTimestamp + 1 && !shownWords.includes(index)) {
                    showWord(wordData);
                    shownWords.push(index);
                }
            });
        }

        function showWord(wordData) {
            if (!videoPlayer.paused) {
                videoPlayer.pause();
                console.log('Video paused to show word:', wordData.word);
            }
            syncPlayPauseLabel();

            const overlay = document.createElement('div');
            overlay.className = 'word-overlay';
            overlay.innerHTML =
                '<div class="portal-word-inner">' +
                    '<div class="portal-word-title"></div>' +
                    '<div class="portal-word-def"></div>' +
                '</div>';
            overlay.querySelector('.portal-word-title').textContent = wordData.word;
            overlay.querySelector('.portal-word-def').textContent = wordData.definition;

            wordOverlayContainer.appendChild(overlay);

            setTimeout(() => {
                overlay.remove();
                if (videoPlayer.paused && videoPlayer.readyState >= 3) {
                    videoPlayer.play().then(function () {
                        console.log('Video resumed after word display');
                        syncPlayPauseLabel();
                    }).catch(function (err) {
                        console.warn('Could not auto-resume video:', err);
                        syncPlayPauseLabel();
                    });
                } else {
                    syncPlayPauseLabel();
                }
            }, dictionaryWordDisplayMs);
        }

        function handleVideoEnded() {
            if (!dictionaryWordsEnabled || !wordGameModal || expectedWordCount <= 0) {
                return;
            }
            portalExitAllFullscreen();
            portalInitWordGameIfNeeded();
            portalOpenWordGameModal();
        }

        (function preventPortalVideoSkip() {
            if (!videoPlayer) {
                return;
            }
            const seekToleranceSec = 0.85;
            let portalSeekInProgress = false;

            videoPlayer.portalMaxTime = 0;

            videoPlayer.addEventListener('seeking', function () {
                portalSeekInProgress = true;
            });

            videoPlayer.addEventListener('seeked', function () {
                portalSeekInProgress = false;
                const maxT = videoPlayer.portalMaxTime || 0;
                if (videoPlayer.currentTime > maxT + seekToleranceSec) {
                    try {
                        videoPlayer.currentTime = maxT;
                    } catch (e) {
                        console.warn('Seek clamp failed:', e);
                    }
                }
            });

            videoPlayer.addEventListener('timeupdate', function () {
                /* Native `seeking` stays true during many scrub drags so we do not treat preview times as watched. */
                if (portalSeekInProgress || videoPlayer.seeking) {
                    return;
                }
                const ct = videoPlayer.currentTime;
                const prev = videoPlayer.portalMaxTime || 0;
                if (ct > prev) {
                    videoPlayer.portalMaxTime = ct;
                }
            });

            videoPlayer.addEventListener('ratechange', function () {
                if (videoPlayer.playbackRate !== 1) {
                    videoPlayer.playbackRate = 1;
                }
            });
            videoPlayer.playbackRate = 1;
        })();

        function goBack() {
            if (confirm('Are you sure you want to leave? Your progress will be lost.')) {
                window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
            }
        }

        syncPlayPauseLabel();
        portalInitWordGameIfNeeded();

        const openWordGameOnLoad = @json(!empty($openWordGameOnLoad));
        if (openWordGameOnLoad) {
            document.addEventListener('DOMContentLoaded', function () {
                if (dictionaryWordsEnabled && expectedWordCount > 0 && wordGameModal) {
                    portalOpenWordGameModal();
                }
            });
        }
    </script>
</body>
</html>
