{{--
    Guest layout for auth pages (register, password reset, etc.).
    Uses /css/auth-captive.css only — no CDN fonts, no Vite (works offline on local gateway).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="stylesheet" href="/css/auth-captive.css">
    </head>
    <body class="guest-auth">
        <div class="guest-shell">
            <div>
                <a href="/">
                    <x-application-logo class="guest-logo-sm fill-current" style="color: #FFDE15;" />
                </a>
            </div>

            <div class="guest-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
