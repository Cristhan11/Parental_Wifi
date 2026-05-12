@if ($user->hasParentCapability())
    @php
        $remoteUrl = is_string($remote_dashboard_url ?? null) && ($remote_dashboard_url ?? '') !== '' ? $remote_dashboard_url : null;
    @endphp
    <section
        x-data="{
            tailscaleLoading: false,
            tailscaleStatusLoading: true,
            tailscaleResult: null,
            tailscaleStatus: null,
            tailscaleError: null,
            copyFeedback: null,
            copyRemoteFeedback: null,
            instructionsOpen: false,
            remoteDashboardUrl: @js($remoteUrl),
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
                        if (typeof data.dashboard_url === 'string' && data.dashboard_url !== '') {
                            this.remoteDashboardUrl = data.dashboard_url;
                        }
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
                    if (typeof data.dashboard_url === 'string' && data.dashboard_url !== '') {
                        this.remoteDashboardUrl = data.dashboard_url;
                    }
                    if (data?.status === 'already_authenticated' || data?.signed_in_as) {
                        this.tailscaleStatus = data;
                    }
                    await this.fetchTailscaleStatus();
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
            async copyRemoteDashboardUrl() {
                if (!this.remoteDashboardUrl) return;
                try {
                    await navigator.clipboard.writeText(this.remoteDashboardUrl);
                    this.copyRemoteFeedback = @js(__('Copied'));
                } catch (e) {
                    this.copyRemoteFeedback = @js(__('Tap the box, select all, then copy'));
                }
                setTimeout(() => { this.copyRemoteFeedback = null }, 2500);
            },
        }"
    >
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Remote dashboard access (Tailscale)') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Use this section when you want to check Parental WiFi from your phone or laptop while you are away from home Wi‑Fi. You do not need technical skills, and you never need to log in to the Raspberry Pi yourself.') }}
            </p>
        </header>

        {{-- Inlined from x-collapsible-instructions so Alpine can read tailscaleStatus on the section --}}
        <div class="mb-4 mt-5">
            <button
                type="button"
                @click="instructionsOpen = !instructionsOpen"
                :aria-expanded="instructionsOpen"
                class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl border border-amber-200/90 bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-100/70 px-4 py-2.5 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-black/5 transition hover:border-amber-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400 focus-visible:ring-offset-2 sm:w-auto sm:justify-start"
            >
                <span class="flex shrink-0 items-center text-gray-600 group-hover:text-gray-800" aria-hidden="true">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </span>
                <span
                    class="min-w-0 flex-1 text-left sm:flex-none"
                    x-text="instructionsOpen ? '{{ e(__('Hide instructions')) }}' : '{{ e(__('Show instructions')) }}'"
                ></span>
                <svg class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 ease-out group-hover:text-gray-700" :class="{ 'rotate-180': instructionsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                x-show="instructionsOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mt-3 rounded-md border border-yellow-100 bg-yellow-50 px-4 py-3 text-sm text-gray-700"
            >
                <p class="mb-3 text-sm text-gray-700">
                    {{ __('The address below is plain text (not a website button). It only appears after the Pi is signed in to Tailscale with the same email as your Parental WiFi account. Copy it and paste it into your browser’s address bar on a device where you have also opened Tailscale and signed in with that same email.') }}
                </p>

                <div x-show="tailscaleStatusLoading" class="mb-4 text-sm text-gray-600">
                    {{ __('Checking whether the Pi is signed in to Tailscale with your dashboard email…') }}
                </div>

                <div
                    x-show="!tailscaleStatusLoading && tailscaleStatus && tailscaleStatus.status === 'already_authenticated' && tailscaleStatus.matches_dashboard === true && remoteDashboardUrl"
                    class="mb-4"
                >
                    <p class="mb-2 font-semibold text-gray-900">{{ __('Your remote dashboard link') }}</p>
                    <p class="mb-2 text-xs text-gray-600">
                        {{ __('Tap the box, select all, then copy—or use Copy. This is not a clickable link on purpose (it only works when Tailscale is running on this device).') }}
                    </p>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-0 flex-1">
                            <input
                                type="text"
                                readonly
                                tabindex="0"
                                class="w-full cursor-text rounded border border-gray-300 bg-white px-2 py-2 font-mono text-xs text-gray-900"
                                :value="remoteDashboardUrl"
                                @click="$event.target.select()"
                            />
                        </div>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-800 hover:bg-gray-100"
                            @click="copyRemoteDashboardUrl()"
                        >
                            {{ __('Copy address') }}
                        </button>
                    </div>
                    <p x-show="copyRemoteFeedback" class="mt-1 text-xs text-gray-600" x-text="copyRemoteFeedback"></p>
                </div>

                <div
                    x-show="!tailscaleStatusLoading && !(tailscaleStatus && tailscaleStatus.status === 'already_authenticated' && tailscaleStatus.matches_dashboard === true && remoteDashboardUrl)"
                    class="mb-4 rounded-md border border-amber-100 bg-amber-50/60 px-3 py-2 text-sm text-amber-950"
                >
                    <p class="mb-1 font-semibold">{{ __('Sign in to Tailscale first') }}</p>
                    <p class="text-sm text-amber-950" x-show="tailscaleStatus && tailscaleStatus.status === 'already_authenticated' && tailscaleStatus.matches_dashboard === false">
                        {{ __('The Pi is signed in to a different Tailscale account than your Parental WiFi email. Use the yellow “Get Tailscale sign-in link” button below to switch the Pi to :email.', ['email' => $user->email]) }}
                    </p>
                    <p class="text-sm text-amber-950" x-show="tailscaleStatus && tailscaleStatus.status === 'action_required'">
                        {{ __('The Pi is not signed in to Tailscale yet. Use the yellow “Get Tailscale sign-in link” button below, then finish the prompts.') }}
                    </p>
                    <p class="text-sm text-amber-950" x-show="tailscaleStatus && tailscaleStatus.status === 'unavailable'">
                        {{ __('We could not check Tailscale on the Pi right now. Try again in a moment, or use the yellow button below.') }}
                    </p>
                    <p class="text-sm text-amber-950" x-show="tailscaleStatus && tailscaleStatus.status === 'already_authenticated' && tailscaleStatus.matches_dashboard === true && !remoteDashboardUrl">
                        {{ __('Tailscale looks correct, but we do not have a dashboard address yet. Refresh this page in a minute or check your Pi connection.') }}
                    </p>
                    <p class="text-sm text-amber-950" x-show="!tailscaleStatus && !tailscaleStatusLoading">
                        {{ __('We could not verify Tailscale on the Pi. Refresh this page or use the yellow “Get Tailscale sign-in link” button below.') }}
                    </p>
                    <p
                        class="text-sm text-amber-950"
                        x-show="tailscaleStatus && tailscaleStatus.status !== 'already_authenticated' && tailscaleStatus.status !== 'action_required' && tailscaleStatus.status !== 'unavailable'"
                    >
                        {{ __('Finish Tailscale setup using the yellow “Get Tailscale sign-in link” button below, then open these instructions again.') }}
                    </p>
                </div>

                <p class="mb-2 font-semibold text-gray-900">{{ __('How to set this up') }}</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>
                        <strong>{{ __('Install Tailscale') }}</strong>
                        {{ __('on the phone or computer you use away from home (App Store on iPhone, Google Play on Android, or the official website on Windows or Mac).') }}
                    </li>
                    <li>
                        <strong>{{ __('Sign in') }}</strong>
                        {{ __('in the Tailscale app with the same account you use for Parental WiFi (for example :email).', ['email' => $user->email]) }}
                    </li>
                    <li>
                        <strong>{{ __('Turn Tailscale on') }}</strong>
                        {{ __('and check that your home Raspberry Pi appears in the list (often named something like “parentalpi”).') }}
                    </li>
                    <li>
                        <strong>{{ __('Pair the Pi') }}</strong>
                        {{ __('using the yellow “Get Tailscale sign-in link” button on this page below, then open the link it gives you on the same device and follow the prompts.') }}
                    </li>
                    <li>
                        <strong>{{ __('If a page does not open') }}</strong>
                        {{ __('wait a few seconds and try again, or open the Tailscale app first and then paste the address from this section into your browser’s address bar once it appears.') }}
                    </li>
                </ul>
            </div>
        </div>

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
