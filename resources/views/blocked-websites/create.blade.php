{{-- 
    Blocked Websites: Create Form
    
    This view displays a form for creating a new blocked website.
    Includes blocking type selection (Domain/App), related domains suggestion UI,
    and Alpine.js for dynamic features.
--}}
<style>
    [x-cloak] { 
        display: none !important; 
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('blocked-websites.index') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    BLOCK WEBSITE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('blocked-websites.store') }}" method="POST" x-data="blockedWebsiteForm()">
                        @csrf
                        
                        {{-- Display validation errors --}}
                        @if ($errors->any())
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">
                                            Please correct the following errors:
                                        </h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Display success message --}}
                        @if (session('success'))
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Blocking Type Selection --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Blocking Type *</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50"
                                    :class="blockType === 'domain' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-300'">
                                    <input type="radio" name="block_type" value="domain" x-model="blockType" class="mr-3" required>
                                    <div>
                                        <div class="font-medium">Domain</div>
                                        <div class="text-xs text-gray-500">Block website and all its pages</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50"
                                    :class="blockType === 'app' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-300'">
                                    <input type="radio" name="block_type" value="app" x-model="blockType" class="mr-3" required>
                                    <div>
                                        <div class="font-medium">App</div>
                                        <div class="text-xs text-gray-500">Block website + mobile app completely</div>
                                    </div>
                                </label>
                            </div>
                            @error('block_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Domain Input (shown for Domain/App type) --}}
                        <div class="mb-6" x-show="blockType === 'domain' || blockType === 'app'" x-cloak>
                            <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain *</label>
                            <input type="text" name="domain" id="domain" value="{{ old('domain') }}"
                                x-on:blur="suggestDomains()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono"
                                placeholder="example.com">
                            <p class="mt-1 text-sm text-gray-500">Enter the website address (e.g., facebook.com or youtube.com)</p>
                            @error('domain')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- App Name Input (shown for App type) --}}
                        <div class="mb-6" x-show="blockType === 'app'" x-cloak>
                            <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">App Name (Optional)</label>
                            <input type="text" name="app_name" id="app_name" value="{{ old('app_name') }}"
                                x-on:blur="suggestDomains()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Facebook">
                            <p class="mt-1 text-sm text-gray-500">Helps identify related domains</p>
                        </div>

                        {{-- Related Domains Display (shown for App type) --}}
                        <div class="mb-6" x-show="blockType === 'app'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Related Domains</label>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <p class="text-sm text-gray-600 mb-3" x-show="relatedDomains.length === 0">
                                    Enter the domain and app name above, then click outside the field to auto-detect related domains.
                                </p>
                                <p class="text-sm text-gray-600 mb-3" x-show="relatedDomains.length > 0">
                                    The following website addresses will also be blocked to ensure the app cannot work:
                                </p>
                                <div class="space-y-2" x-show="relatedDomains.length > 0">
                                    <template x-for="(domain, index) in relatedDomains" :key="index">
                                        <div class="flex items-center justify-between p-2 bg-white rounded border">
                                            <span class="font-mono text-sm" x-text="domain"></span>
                                            <button type="button" @click="removeDomain(index)" class="text-red-600 hover:text-red-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                {{-- Always include hidden input for app blocks, even if empty --}}
                                <input type="hidden" name="related_domains" :value="JSON.stringify(relatedDomains)">
                            </div>
                        </div>

                        {{-- Block Subdomains Checkbox --}}
                        <div class="mb-6" x-show="blockType === 'domain' || blockType === 'app'" x-cloak>
                            <label class="flex items-center">
                                <input type="checkbox" name="block_subdomains" value="1" 
                                    {{ old('block_subdomains') ? 'checked' : '' }}
                                    class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Also block subdomains (e.g., www.example.com, m.example.com)</span>
                            </label>
                            @error('block_subdomains')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea name="reason" id="reason" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Why is this website blocked?">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('blocked-websites.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                                Block Website
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function blockedWebsiteForm() {
            return {
                blockType: '{{ old("block_type", "domain") }}',
                relatedDomains: @json(old('related_domains', [])),
                loading: false,

                async suggestDomains() {
                    if (this.blockType !== 'app') return;
                    
                    const domain = document.getElementById('domain')?.value;
                    const appName = document.getElementById('app_name')?.value;
                    
                    if (!domain) return;
                    
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route("blocked-websites.suggest-domains") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ domain, app_name: appName })
                        });
                        
                        // Check if response is OK
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Response is not JSON:', text.substring(0, 100));
                            throw new Error('Response is not JSON');
                        }
                        
                        const data = await response.json();
                        this.relatedDomains = data.domains || [];
                    } catch (error) {
                        console.error('Error fetching related domains:', error);
                        // Don't show error to user, just leave relatedDomains empty
                        // Controller will auto-populate related domains when form is submitted
                        this.relatedDomains = [];
                    } finally {
                        this.loading = false;
                    }
                },

                removeDomain(index) {
                    this.relatedDomains.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>

