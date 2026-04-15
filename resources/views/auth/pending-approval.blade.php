<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Awaiting administrator approval</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            Your email is verified. A system administrator must approve your parent account before you can use the dashboard. You will be able to sign in as usual once approved.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm" style="color: #08E60F;">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <a href="{{ route('login') }}" class="guest-link-btn underline text-sm text-black">
            Back to sign in
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="guest-link-btn underline text-sm text-black">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
