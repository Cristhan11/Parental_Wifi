<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Verify your email</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            We sent a <strong>6-digit code</strong> to your inbox. Enter it below to confirm your address. Codes expire after 60 minutes.
        </p>
        @auth
            <p class="mt-2 text-sm text-black">
                {{ __('Confirming') }} <strong class="break-all">{{ Auth::user()->email }}</strong>. {{ __('After you verify, use this same email when signing in to Tailscale on each device.') }}
                {{ __('The app cannot switch Tailscale for you—complete sign-in inside the Tailscale app on the Raspberry Pi when you change email.') }}
            </p>
        @endauth
    </div>

    @if (session('status') === 'verification-code-sent')
        <div class="mb-4 font-medium text-sm" style="color: #08E60F;">
            A new verification code has been sent to your email address.
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md text-sm" style="background:#fef2f2; color:#991b1b;" role="alert">
            @foreach ($errors->all() as $error)
                <p class="m-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" class="mb-8">
        @csrf
        <div>
            <label for="code" class="block text-sm font-medium text-black mb-1">Verification code</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                autocomplete="one-time-code"
                value="{{ old('code') }}"
                required
                autofocus
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-center text-2xl tracking-[0.4em] font-mono focus:border-yellow-500 focus:ring-yellow-500"
                placeholder="000000"
            />
        </div>
        <div class="mt-4">
            <x-primary-button type="submit" class="w-full justify-center">
                Verify email
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="guest-link-btn underline text-sm text-black text-left">
                Resend code
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="guest-link-btn underline text-sm text-black">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
