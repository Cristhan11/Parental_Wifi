{{--
    Login Page View
    
    Purpose: Displays the login form for users to authenticate
    
    How It Works:
    1. Extends guest.blade.php layout (wraps content in cream background, white card)
    2. Creates a form that submits to POST /login route
    3. Uses Blade components for reusable UI elements (inputs, buttons, labels)
    4. Shows validation errors if login fails
    5. Includes "Remember me" checkbox and "Forgot password" link
    
    Blade Syntax Explained:
    - <x-guest-layout> = Uses the guest layout component
    - {{ }} = Outputs variable/expression (escaped for security)
    - @csrf = Generates CSRF token (security requirement for forms)
    - @if/@endif = Conditional statements
    - :value="..." = Passes prop to component
    - route('login') = Generates URL for named route
    - old('email') = Retrieves old input value (if validation fails, keeps user's input)
    - $errors->get('email') = Gets validation errors for 'email' field
--}}
<x-guest-layout>
    {{-- Session Status: Shows success messages (e.g., "You've been logged out") --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Page Header: Title and description --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Login</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">Enter your credentials to access your account</p>
    </div>

    {{-- Login Form: Submits to POST /login route --}}
    <form method="POST" action="{{ route('login') }}">
        {{-- CSRF Protection: Required for all POST forms, prevents cross-site attacks --}}
        @csrf

        {{-- Email Input Field --}}
        <div>
            {{-- Component: Reusable label component --}}
            <x-input-label for="email" :value="__('Email')" />
            
            {{-- Component: Reusable text input component --}}
            {{-- old('email') keeps the email value if form validation fails --}}
            {{-- required = HTML5 validation, autofocus = cursor starts here --}}
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            
            {{-- Component: Shows validation errors for email field --}}
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password Input Field --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            {{-- Password input: type="password" hides the text as user types --}}
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            {{-- Shows validation errors for password field --}}
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Remember Me Checkbox: Keeps user logged in for longer --}}
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                {{-- Checkbox with yellow accent color --}}
                <input id="remember_me" type="checkbox" class="rounded border-gray-300" style="accent-color: #FFDE15;" name="remember">
                <span class="ms-2 text-sm text-black">{{ __('Remember me') }}</span>
            </label>
        </div>

        {{-- Form Actions: Forgot password link and Login button --}}
        <div class="flex items-center justify-between mt-6">
            {{-- Only show "Forgot password" link if that route exists --}}
            @if (Route::has('password.request'))
                <a class="underline text-sm text-black hover:opacity-75 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition" style="color: #00000080; focus:ring-color: #FFDE15;" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            {{-- Primary Button Component: Yellow button with "Log in" text --}}
            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
