{{-- 
    Blocked Websites: Create Form
    
    App-style blocking only: main domain plus suggested related domains for mobile apps.
    Alpine.js powers related-domain suggestions.
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

                        <x-collapsible-instructions class="mb-6">
                            <p class="mb-2 font-semibold">Instructions</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>Enter the site you want to block, or pick from common websites below.</li>
                                <li>We also block common app and website addresses for that site (ex: Facebook links used by the app).</li>
                                <li>Red input border means required. Fill it in until it turns green.</li>
                            </ul>
                        </x-collapsible-instructions>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Common websites</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="website in commonWebsites" :key="website.domain">
                                    <button
                                        type="button"
                                        @click="selectCommonWebsite(website.domain)"
                                        class="px-3 py-1.5 rounded-full border border-gray-300 text-sm text-gray-700 bg-white hover:bg-gray-50">
                                        <span x-text="`${website.name} (${website.domain})`"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Tap a common site to fill the domain quickly.</p>
                        </div>

                        <div class="mb-6">
                            <label for="domain" class="block text-sm font-medium text-gray-700 mb-2">Domain *</label>
                            <input type="text" name="domain" id="domain" value="{{ old('domain') }}" required
                                x-model="domain"
                                x-on:blur="suggestDomains()"
                                list="common-domain-list"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 font-mono"
                                placeholder="example.com">
                            <datalist id="common-domain-list">
                                <template x-for="website in commonWebsites" :key="`list-${website.domain}`">
                                    <option :value="website.domain" x-text="website.name"></option>
                                </template>
                            </datalist>
                            <p class="mt-1 text-sm text-gray-500">Enter the website address (e.g., facebook.com or youtube.com)</p>
                            @error('domain')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Automatic protection</label>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50 text-sm text-gray-700">
                                <p>Related domains and subdomains are blocked automatically.</p>
                                <p x-show="relatedDomains.length > 0" class="mt-2">
                                    Extra related domains found: <span class="font-medium" x-text="relatedDomains.length"></span>
                                </p>
                            </div>
                        </div>
                        <input type="hidden" name="related_domains" :value="JSON.stringify(relatedDomains)">
                        <input type="hidden" name="block_subdomains" value="1">

                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                            <textarea name="reason" id="reason" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="Why is this website blocked?">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

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

    @php
        $relatedDomainsOld = old('related_domains', []);
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
                domain: @js(old('domain', '')),
                relatedDomains: @json($relatedDomainsOld),
                commonWebsites: @json($commonWebsites ?? []),
                loading: false,

                selectCommonWebsite(domain) {
                    this.domain = domain;
                    const domainField = document.getElementById('domain');
                    if (domainField) {
                        domainField.value = domain;
                        setRequiredFieldState(domainField);
                        domainField.dispatchEvent(new Event('input', { bubbles: true }));
                        domainField.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    this.suggestDomains();
                },

                async suggestDomains() {
                    const domain = this.domain;

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
                            body: JSON.stringify({ domain })
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

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
                        this.relatedDomains = [];
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>
