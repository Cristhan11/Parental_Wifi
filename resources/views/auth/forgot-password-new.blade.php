<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">New password</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            Choose a new password for <span class="font-medium text-black">{{ $userEmail }}</span>.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.forgot.new.store') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-black hover:opacity-75" style="color: #00000080;" href="{{ route('login') }}">
                {{ __('Back to login') }}
            </a>

            <x-primary-button>
                {{ __('Save password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
