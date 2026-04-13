{{-- 
    Blocked Websites: Edit Form
    
    This view displays a form for editing an existing blocked website.
    Similar to create form but pre-filled with existing data.
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
                    EDIT BLOCKED WEBSITE
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('blocked-websites.update', $blockedWebsite) }}" method="POST" x-data="blockedWebsiteForm()">
                        @csrf
                        @method('PUT')

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

                        {{-- Domain Input --}}
                        <div class="mb-6" x-show="blockType === 'domain' || blockType === 'app'" x-cloak>
                            <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain *</label>
                            <input type="text" name="domain" id="domain" value="{{ old('domain', $blockedWebsite->domain) }}"
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

                        {{-- Related Domains Display --}}
                        <div class="mb-6" x-show="blockType === 'app' && relatedDomains.length > 0" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Related Domains</label>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <p class="text-sm text-gray-600 mb-3">The following website addresses will also be blocked to ensure the app cannot work:</p>
                                <div class="space-y-2">
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
                                <input type="hidden" name="related_domains" :value="JSON.stringify(relatedDomains)">
                            </div>
                        </div>

                        {{-- Block Subdomains Checkbox --}}
                        <div class="mb-6" x-show="blockType === 'domain' || blockType === 'app'" x-cloak>
                            <label class="flex items-center">
                                <input type="checkbox" name="block_subdomains" value="1" 
                                    {{ old('block_subdomains', $blockedWebsite->block_subdomains) ? 'checked' : '' }}
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
                                placeholder="Why is this website blocked?">{{ old('reason', $blockedWebsite->reason) }}</textarea>
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
                                Update Blocked Website
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
                blockType: '{{ old("block_type", $blockedWebsite->block_type) }}',
                relatedDomains: @json(old('related_domains', $blockedWebsite->related_domains ?? [])),
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
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ domain, app_name: appName })
                        });
                        
                        const data = await response.json();
                        // Merge with existing domains
                        const existing = this.relatedDomains;
                        this.relatedDomains = [...new Set([...existing, ...(data.domains || [])])];
                    } catch (error) {
                        console.error('Error fetching related domains:', error);
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

