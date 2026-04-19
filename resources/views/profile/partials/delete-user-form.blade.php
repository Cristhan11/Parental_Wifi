<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            @if ($user->canDeleteOwnAccount())
                {{ __('Once your account is deleted, all data linked to it—including child device records, browsing-related logs, and quiz activity—will be permanently removed. This cannot be undone.') }}
            @else
                {{ __('Once an account is deleted, all data linked to it is permanently removed. Administrator accounts cannot be deleted from this page.') }}
            @endif
        </p>
    </header>

    @if ($user->canDeleteOwnAccount())
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >{{ __('Delete Account') }}</x-danger-button>

        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('This will permanently delete your account and all associated data. Enter your password to confirm.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('Delete Account') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    @else
        @if (session('profile_delete_blocked'))
            <p class="text-sm font-medium text-red-600" role="alert">
                {{ session('profile_delete_blocked') }}
            </p>
        @else
            <p class="text-sm font-medium text-gray-700">
                {{ __('Administrator accounts cannot be deleted from profile settings.') }}
            </p>
        @endif
    @endif
</section>
