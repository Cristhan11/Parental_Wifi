{{-- 
    Blocked Websites: Edit Form
    
    App-style blocking only (same as create).
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

                        <x-collapsible-instructions class="mb-6" innerClass="mt-3 rounded-md border border-yellow-100 bg-yellow-50 px-3 py-3 text-sm text-gray-700">
                            <p class="mb-2 font-semibold">Instructions</p>
                            <p class="text-gray-700">
                                Blocks the main domain and suggested related addresses so typical app traffic is covered. Saving upgrades this rule to full app-style blocking.
                            </p>
                        </x-collapsible-instructions>

                        <div class="mb-6">
                            <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain *</label>
                            <input type="text" name="domain" id="domain" value="{{ old('domain', $blockedWebsite->domain) }}" required
                                x-on:blur="suggestDomains()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono"
                                placeholder="example.com">
                            <p class="mt-1 text-sm text-gray-500">Enter the website address (e.g., facebook.com or youtube.com)</p>
                            @error('domain')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">App name (optional)</label>
                            <input type="text" name="app_name" id="app_name" value="{{ old('app_name') }}"
                                x-on:blur="suggestDomains()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Facebook">
                            <p class="mt-1 text-sm text-gray-500">Helps find related addresses for that app or service</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Related domains</label>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <p class="text-sm text-gray-600 mb-3" x-show="relatedDomains.length === 0">
                                    Enter the domain (and optional app name), then click outside a field to suggest extra addresses to block.
                                </p>
                                <p class="text-sm text-gray-600 mb-3" x-show="relatedDomains.length > 0">
                                    These addresses will also be blocked so the app or service is harder to reach:
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
                                <input type="hidden" name="related_domains" :value="JSON.stringify(relatedDomains)">
                            </div>
                        </div>

                        <div class="mb-6">
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

                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea name="reason" id="reason" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Why is this website blocked?">{{ old('reason', $blockedWebsite->reason) }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

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

    @php
        $relatedDomainsOld = old('related_domains', $blockedWebsite->related_domains ?? []);
        if (is_string($relatedDomainsOld)) {
            $decodedRd = json_decode($relatedDomainsOld, true);
            $relatedDomainsOld = is_array($decodedRd) ? $decodedRd : [];
        }
        if (! is_array($relatedDomainsOld)) {
            $relatedDomainsOld = [];
        }
    @endphp
    <script>
        function setRequiredFieldState(field) {
            if (!field || field.type === 'hidden' || field.disabled) return;
            if (!field.hasAttribute('required')) return;

            const hasValue = String(field.value ?? '').trim() !== '';
            if (hasValue) {
                field.style.borderColor = '#16A34A';
                field.style.boxShadow = '0 0 0 1px #16A34A';
            } else {
                field.style.borderColor = '#DC2626';
                field.style.boxShadow = '0 0 0 1px #DC2626';
            }
        }

        function bindRequiredFieldFeedback(scope = document) {
            const fields = scope.querySelectorAll('input[required], select[required], textarea[required]');
            fields.forEach((field) => {
                if (field.dataset.requiredBound === '1') return;
                field.dataset.requiredBound = '1';
                setRequiredFieldState(field);
                field.addEventListener('input', () => setRequiredFieldState(field));
                field.addEventListener('change', () => setRequiredFieldState(field));
                field.addEventListener('blur', () => setRequiredFieldState(field));
            });
        }

        function initializeRequiredFeedback() {
            bindRequiredFieldFeedback();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeRequiredFeedback);
        } else {
            initializeRequiredFeedback();
        }
        window.addEventListener('pageshow', initializeRequiredFeedback);

        function blockedWebsiteForm() {
            return {
                relatedDomains: @json($relatedDomainsOld),
                loading: false,

                async suggestDomains() {
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

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
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
