<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Parental WiFi</title>
    <link rel="stylesheet" href="/css/auth-captive.css">
</head>
<body class="login-page">
    <div class="login-split">
        <div class="login-form-col">
            <div class="login-form-card">
                <div class="login-logo-wrap">
                    <x-application-logo class="login-logo fill-current" style="color: #FFDE15;" />
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
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
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
                        </div>
                        @error('password')
                            <p class="login-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="login-remember">
                        <input id="remember_me" type="checkbox" name="remember" />
                        <label for="remember_me"><span>Remember me</span></label>
                    </div>

                    <button type="submit" class="login-submit">
                        Log in
                    </button>
                </form>
            </div>
        </div>

        <div class="login-brand-col">
            <div class="login-brand-inner">
                <x-application-logo class="login-brand-logo fill-current" style="color: #000000;" />
                <h1 class="login-brand-title">Parental WiFi</h1>
                <p class="login-brand-tagline">Parental control &amp; internet management</p>
                <p class="login-brand-copy">
                    Manage your children's internet access, monitor usage, and support safer online experiences.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
