<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('PARENTAL_WIFI_LOGO.png') }}">
    <link rel="shortcut icon" href="{{ asset('PARENTAL_WIFI_LOGO.png') }}">
    <title>Login - Parental WiFi</title>
    @include('auth.partials.head-assets')
</head>
<body class="login-page">
    <div class="login-split">
        <div class="login-form-col">
            <div class="login-form-card">
                <div class="login-logo-wrap">
                    <x-application-logo class="login-logo" />
                </div>

                <h2 class="login-heading">Welcome</h2>
                <p class="login-lead">Please log-in to continue</p>

                @if (session('status'))
                    <div class="login-alert login-alert--ok" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="login-alert login-alert--err" role="alert">
                        <strong>Error:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form-fields">
                    @csrf

                    <div>
                        <label for="email" class="login-label">Email</label>
                        <div class="login-input-shell">
                            <svg class="login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <input
                                id="email"
                                type="text"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                inputmode="email"
                                autocapitalize="none"
                                spellcheck="false"
                                placeholder="Enter your email"
                            />
                        </div>
                        @error('email')
                            <p class="login-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="login-label">Password</label>
                        <div class="login-input-shell">
                            <svg class="login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                            </svg>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                            <button
                                type="button"
                                id="login-password-toggle"
                                class="login-password-toggle"
                                aria-label="{{ __('Show password') }}"
                                aria-pressed="false"
                            >
                                <svg class="login-password-toggle-icon--show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg class="login-password-toggle-icon--hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="login-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="login-remember" for="remember_me">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                        />
                        <span class="login-remember-text">Remember me</span>
                    </label>

                    <p class="login-links-row" style="margin-top: 1rem; font-size: 0.875rem;">
                        @if(($canRegisterParent ?? false) === true)
                            <a href="{{ route('register') }}" class="login-link-secondary">Create parent account</a>
                            <span style="opacity:0.5;"> · </span>
                        @endif
                        <a href="{{ route('password.request') }}" class="login-link-secondary">Forgot password?</a>
                    </p>

                    <button type="submit" class="login-submit">
                        Log in
                    </button>
                </form>
            </div>
        </div>

        <div class="login-brand-col">
            <div class="login-brand-inner">
                <x-application-logo class="login-brand-logo" />
                <h1 class="login-brand-title">Parental WiFi</h1>
                <p class="login-brand-tagline">Parental control &amp; internet management</p>
                <p class="login-brand-copy">
                    Manage your children's internet access, monitor usage, and support safer online experiences.
                </p>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var btn = document.getElementById('login-password-toggle');
            var input = document.getElementById('password');
            if (!btn || !input) return;
            var iconShow = btn.querySelector('.login-password-toggle-icon--show');
            var iconHide = btn.querySelector('.login-password-toggle-icon--hide');
            var labelShow = @json(__('Show password'));
            var labelHide = @json(__('Hide password'));
            btn.addEventListener('click', function () {
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                btn.setAttribute('aria-pressed', visible ? 'false' : 'true');
                btn.setAttribute('aria-label', visible ? labelShow : labelHide);
                if (iconShow && iconHide) {
                    iconShow.style.display = visible ? '' : 'none';
                    iconHide.style.display = visible ? 'none' : '';
                }
            });
        })();
    </script>
</body>
</html>
