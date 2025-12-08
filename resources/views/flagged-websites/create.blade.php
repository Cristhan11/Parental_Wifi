{{-- 
    Flagged Websites: Create Form
    
    This view displays a form for creating a new flagged website.
    Simpler than blocked websites - just URL and reason.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('flagged-websites.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    FLAG WEBSITE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Info Banner --}}
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800">
                    <strong>What is Flagging?</strong> Flagged websites are monitored but not blocked. 
                    When a child visits a flagged website, it's logged and you'll be notified, but access is still allowed.
                </p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('flagged-websites.store') }}" method="POST">
                        @csrf

                        {{-- Device Selection --}}
                        <div class="mb-6">
                            <label for="device_id" class="block text-sm font-medium text-gray-700 mb-2">Device *</label>
                            <select name="device_id" id="device_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select a device...</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                        {{ $device->name }} ({{ $device->mac_address }})
                                    </option>
                                @endforeach
                            </select>
                            @error('device_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- URL Input --}}
                        <div class="mb-6">
                            <label for="url" class="block text-sm font-medium text-gray-700 mb-2">URL *</label>
                            <input type="url" name="url" id="url" value="{{ old('url') }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="https://example.com/page">
                            <p class="mt-1 text-sm text-gray-500">Enter the full URL of the website to flag</p>
                            @error('url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea name="reason" id="reason" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Why is this website flagged?">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('flagged-websites.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                                Flag Website
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

