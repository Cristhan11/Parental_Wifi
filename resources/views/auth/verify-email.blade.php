<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Verify Your Email</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm" style="color: #08E60F;">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-black hover:opacity-75 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition" style="color: #00000080; focus:ring-color: #FFDE15;">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
