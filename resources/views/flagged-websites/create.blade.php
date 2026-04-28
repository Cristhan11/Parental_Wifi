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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    <form action="{{ route('flagged-websites.store') }}" method="POST" x-data="flaggedWebsiteForm()">
                        @csrf
                        <x-collapsible-instructions class="mb-6">
                            <p class="mb-2 font-semibold">Instructions</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>Flagging means the website is still accessible, but we will include visits in your reports.</li>
                                <li>Enter the full website link, or pick from common websites below.</li>
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
                            <p class="mt-1 text-sm text-gray-500">Tap a common site to fill the URL quickly.</p>
                        </div>

                        {{-- URL Input --}}
                        <div class="mb-6">
                            <label for="url" class="block text-sm font-medium text-gray-700 mb-2">URL *</label>
                            <input type="url" name="url" id="url" value="{{ old('url') }}" required
                                x-model="url"
                                list="common-flagged-url-list"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                placeholder="https://example.com/page">
                            <datalist id="common-flagged-url-list">
                                <template x-for="website in commonWebsites" :key="`list-${website.domain}`">
                                    <option :value="`https://${website.domain}`" x-text="website.name"></option>
                                </template>
                            </datalist>
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

        function flaggedWebsiteForm() {
            return {
                url: @js(old('url', '')),
                commonWebsites: @json($commonWebsites ?? []),

                selectCommonWebsite(domain) {
                    const value = `https://${domain}`;
                    this.url = value;
                    const urlField = document.getElementById('url');
                    if (urlField) {
                        urlField.value = value;
                        setRequiredFieldState(urlField);
                        urlField.dispatchEvent(new Event('input', { bubbles: true }));
                        urlField.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                },
            };
        }
    </script>
</x-app-layout>

