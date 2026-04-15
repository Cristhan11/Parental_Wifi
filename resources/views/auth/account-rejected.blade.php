<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Registration not approved</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            An administrator did not approve this parent registration. You cannot access the parent dashboard with this account.
        </p>
    </div>

    @if (! empty($note))
        <div class="mb-4 p-3 rounded-md bg-gray-100 text-sm text-gray-800">
            <strong>Note from administrator:</strong> {{ $note }}
        </div>
    @endif

    <div class="mt-6">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-primary-button type="submit">Log out</x-primary-button>
        </form>
    </div>
</x-guest-layout>
