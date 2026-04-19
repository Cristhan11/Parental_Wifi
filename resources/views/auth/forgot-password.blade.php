<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Forgot Password</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            Enter the email on your parent account. Administrators will be notified to reset your password. They will tell you how to sign in; then change your password under profile settings. No verification email is sent from this form.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-black hover:opacity-75 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition" style="color: #00000080; focus:ring-color: #FFDE15;" href="{{ route('login') }}">
                {{ __('Back to login') }}
            </a>

            <x-primary-button>
                Request password reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
