<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Choose a new password</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            A household operator reset your password to the default value. Set a new private password to continue using your account. You will not be able to access the dashboard until this is done.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.force-change.update') }}">
        @csrf

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Save password') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-xs underline text-black hover:opacity-75" style="color: #00000080;">
            Sign out
        </button>
    </form>
</x-guest-layout>
