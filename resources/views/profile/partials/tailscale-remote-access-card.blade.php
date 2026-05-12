@if ($user->hasParentCapability())
    <section
        x-data="{
            tailscaleLoading: false,
            tailscaleStatusLoading: true,
            tailscaleResult: null,
            tailscaleStatus: null,
            tailscaleError: null,
            copyFeedback: null,
            init() {
                this.fetchTailscaleStatus();
            },
            async fetchTailscaleStatus() {
                this.tailscaleStatusLoading = true;
                try {
                    const res = await fetch(@js(route('profile.tailscale.auth-link')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ status_only: true }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok) {
                        this.tailscaleStatus = data;
                    }
                } catch (e) {
                    this.tailscaleStatus = null;
                } finally {
                    this.tailscaleStatusLoading = false;
                }
            },
            async fetchTailscaleSignInLink() {
                this.tailscaleLoading = true;
                this.tailscaleError = null;
                this.copyFeedback = null;
                try {
                    const res = await fetch(@js(route('profile.tailscale.auth-link')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ sync_tailscale_with_dashboard: true }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.tailscaleError = data?.message ?? @js(__('Could not reach the Pi helper. Try again in a few minutes.'));
                        this.tailscaleResult = null;
                        return;
                    }
                    this.tailscaleResult = data;
                    if (data?.status === 'already_authenticated' || data?.signed_in_as) {
                        this.tailscaleStatus = data;
                    }
                } catch (e) {
                    this.tailscaleResult = null;
                    this.tailscaleError = @js(__('Failed to contact local Pi helper service.'));
                } finally {
                    this.tailscaleLoading = false;
                }
            },
            async copyAuthUrl() {
                const url = this.tailscaleResult?.auth_url;
                if (!url) return;
                try {
                    await navigator.clipboard.writeText(url);
                    this.copyFeedback = @js(__('Copied'));
                } catch (e) {
                    this.copyFeedback = @js(__('Select the link below to copy'));
                }
                setTimeout(() => { this.copyFeedback = null }, 2500);
            },
        }"
    >
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Remote dashboard access (Tailscale)') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('One click sets up Tailscale on this Raspberry Pi using your login email (:email). The Pi sends you a sign-in link below — open it on your phone or computer to finish. You do not need to log in to the Pi yourself.', ['email' => $user->email]) }}
            </p>
        </header>

        <div class="mt-4" x-show="tailscaleStatusLoading" style="display: none;">
            <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                <svg class="h-4 w-4 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>{{ __('Checking current Tailscale status on the Pi…') }}</span>
            </div>
        </div>

        <template x-if="!tailscaleStatusLoading && tailscaleStatus && tailscaleStatus.status === 'already_authenticated'">
            <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div class="space-y-1">
                        <p class="font-medium">
                            <template x-if="tailscaleStatus.signed_in_as">
                                <span>{{ __('Pi is signed in to Tailscale as') }} <span class="font-mono" x-text="tailscaleStatus.signed_in_as"></span>.</span>
                            </template>
                            <template x-if="!tailscaleStatus.signed_in_as">
                                <span>{{ __('Pi is signed in to Tailscale.') }}</span>
                            </template>
                        </p>
                        <template x-if="tailscaleStatus.matches_dashboard === true">
                            <p class="text-xs text-green-800">{{ __('This matches your dashboard email — remote access is ready.') }}</p>
                        </template>
                        <template x-if="tailscaleStatus.matches_dashboard === false">
                            <p class="text-xs text-amber-800">{{ __('This is a different account from your dashboard email. Click the button below to switch the Pi to :email.', ['email' => $user->email]) }}</p>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!tailscaleStatusLoading && tailscaleStatus && tailscaleStatus.status === 'action_required' && !tailscaleStatus.auth_url">
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p>{{ __('Pi is not signed in to Tailscale yet. Click the button below to get a sign-in link.') }}</p>
            </div>
        </template>

        <template x-if="!tailscaleStatusLoading && tailscaleStatus && tailscaleStatus.status === 'unavailable'">
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p x-text="tailscaleStatus.message"></p>
            </div>
        </template>

        <div class="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-medium text-gray-900">{{ __('Tailscale on the Pi') }}</p>
                <button
                    type="button"
                    class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black hover:opacity-90 disabled:opacity-50"
                    @click="fetchTailscaleSignInLink()"
                    :disabled="tailscaleLoading"
                >
                    <span x-show="!tailscaleLoading">{{ __('Get Tailscale sign-in link') }}</span>
                    <span x-show="tailscaleLoading" style="display: none;">{{ __('Working… this can take up to a minute') }}</span>
                </button>
            </div>

            <p class="mt-3 text-xs text-gray-600">
                {{ __('If the Pi is already signed in with your email, you will see a confirmation instead of a link.') }}
            </p>

            <template x-if="tailscaleResult">
                <div class="mt-3 border-t border-gray-200 pt-3">
                    <p class="text-gray-800" x-text="tailscaleResult.message"></p>
                    <template x-if="tailscaleResult.status === 'action_required' && tailscaleResult.auth_url">
                        <div class="mt-3 space-y-2">
                            <p>
                                <a
                                    :href="tailscaleResult.auth_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-medium text-blue-700 underline"
                                >
                                    {{ __('Open sign-in link') }}
                                </a>
                            </p>
                            <p class="text-xs text-gray-600">
                                {{ __('Sign in with :email when Tailscale asks. The Pi appears in your Tailscale account once you finish.', ['email' => $user->email]) }}
                            </p>
                            <div class="flex flex-wrap items-end gap-2">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Sign-in link (copy)') }}</label>
                                    <input
                                        type="text"
                                        readonly
                                        class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 font-mono text-xs text-gray-800"
                                        :value="tailscaleResult.auth_url"
                                        @click="$event.target.select()"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-800 hover:bg-gray-100"
                                    @click="copyAuthUrl()"
                                >
                                    {{ __('Copy link') }}
                                </button>
                            </div>
                            <p x-show="copyFeedback" class="text-xs text-gray-600" x-text="copyFeedback"></p>
                        </div>
                    </template>
                    <template x-if="tailscaleResult.status === 'already_authenticated'">
                        <p class="mt-3 text-sm text-gray-700">
                            {{ __('The Pi is signed in to Tailscale with :email. No further action is needed.', ['email' => $user->email]) }}
                        </p>
                    </template>
                </div>
            </template>

            <template x-if="tailscaleError">
                <p class="mt-3 border-t border-gray-200 pt-3 text-red-700" x-text="tailscaleError"></p>
            </template>
        </div>
    </section>
@endif
