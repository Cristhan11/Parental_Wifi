<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-black">Device Registration</h2>
        <p class="mt-2 text-sm" style="color: #00000080;">
            New device on your home Wi-Fi? Send a registration request to the Parent Owner.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md p-3 text-sm" style="background:#ecfdf5; color:#065f46;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('device-request.store') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="device_name" :value="__('Device Name')" />
            <x-text-input id="device_name" name="device_name" type="text" class="mt-1 block w-full" :value="old('device_name')" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('device_name')" />
        </div>

        <p class="text-xs text-gray-500">
            MAC address is captured automatically in the backend. Manual MAC entry is available only in advanced/debug tools.
        </p>

        <x-primary-button class="w-full justify-center">
            {{ __('Request to Register') }}
        </x-primary-button>
    </form>
</x-guest-layout>
