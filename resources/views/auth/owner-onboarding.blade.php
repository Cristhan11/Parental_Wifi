<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Parent Owner Setup</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            First login setup: set your real email, verify it, and change the default password.
        </p>
    </div>

    <form method="POST" action="{{ route('owner.onboarding.update') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="email" :value="__('New Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', auth()->user()->email)" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="current_password" :value="__('Current Password')" />
            <x-text-input id="current_password" class="mt-1 block w-full" type="password" name="current_password" required />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required />
        </div>

        <x-primary-button class="w-full justify-center">
            Complete Setup
        </x-primary-button>
    </form>
</x-guest-layout>
