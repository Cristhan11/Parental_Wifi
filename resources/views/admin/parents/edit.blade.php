<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Edit parent account</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
        <p class="text-sm text-gray-600 mb-6">
            <a href="{{ route('admin.parents.index') }}" class="underline text-black">← Back to parent accounts</a>
        </p>

        <div class="bg-white shadow border border-gray-200 rounded-lg p-6">
            <form method="POST" action="{{ route('admin.parents.update', $user) }}" class="space-y-6">
                @csrf
                @method('patch')

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    <p class="mt-2 text-xs text-gray-500">If you change the email, the parent must verify the new address before it counts as verified.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-primary-button type="submit">Save changes</x-primary-button>
                    <a href="{{ route('admin.parents.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-800 hover:bg-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
