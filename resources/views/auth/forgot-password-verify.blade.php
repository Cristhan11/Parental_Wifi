<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Enter confirmation number</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            Enter the six-digit number from the email we just sent. If the number matches, you can set a new password on the next screen.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.forgot.verify.store') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Confirmation number" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" :value="old('code')" required autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-black hover:opacity-75 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition" style="color: #00000080;" href="{{ route('password.request') }}">
                Start over
            </a>

            <x-primary-button>
                Continue
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
