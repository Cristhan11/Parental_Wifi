<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 text-black">
                    <p class="text-lg font-medium">{{ __("You're logged in!") }}</p>
                    @auth
                        <p class="mt-2 text-sm" style="color: #00000080;">
                            Welcome, {{ Auth::user()->name }}!
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold ml-1" style="background-color: #FFDE15; color: #000000;" title="Your dashboard account type">
                                {{ Auth::user()->accountTypeLabel() }}
                            </span>
                        </p>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
