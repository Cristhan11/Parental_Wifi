{{--
    Guest Layout Template
    
    Purpose: Base layout for all authentication pages (login, register, password reset, etc.)
    
    How It Works:
    1. This is a Blade component (can be used with <x-guest-layout>)
    2. Other views extend this layout using: <x-guest-layout>...</x-guest-layout>
    3. Content from child views is inserted where {{ $slot }} appears
    4. Provides consistent styling (Montserrat font, cream background, white card)
    
    Usage:
    In login.blade.php: <x-guest-layout>Login form content here</x-guest-layout>
    The "Login form content" replaces {{ $slot }} below
    
    Design Elements:
    - Light cream background (#FFFFCC)
    - Montserrat font from Google Fonts
    - White card panel for form content
    - Yellow logo accent (#FFDE15)
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        {{-- CSRF Token: Security token for forms, prevents cross-site request forgery attacks --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Page Title: Uses app name from config, defaults to 'Laravel' if not set --}}
        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Montserrat Font: Loads from Google Fonts for consistent typography --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

        {{-- Vite Assets: Loads compiled CSS and JavaScript --}}
        {{-- @vite directive tells Laravel to include the built assets --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-black antialiased" style="font-family: 'Montserrat', sans-serif;">
        {{-- Main Container: Full screen height, centered content, cream background --}}
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" style="background-color: #FFFFCC;">
            {{-- Logo Section: Application logo with yellow accent color --}}
            <div>
                <a href="/">
                    {{-- Component: Reusable logo component --}}
                    <x-application-logo class="w-20 h-20 fill-current" style="color: #FFDE15;" />
                </a>
            </div>

            {{-- Content Card: White card where form content appears --}}
            {{-- $slot: This is where content from child views (login.blade.php, etc.) is inserted --}}
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg border border-gray-200">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
