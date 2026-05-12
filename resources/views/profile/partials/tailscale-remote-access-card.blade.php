@if ($user->hasParentCapability())
    <section
        x-data="{
            tailscaleLoading: false,
            tailscaleResult: null,
            tailscaleError: null,
            copyFeedback: null,
            async fetchQuickSignInLink() {
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
                        body: JSON.stringify({}),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.tailscaleError = data?.message ?? @js(__('Could not reach the Pi helper. Try again in a few minutes.'));
                        this.tailscaleResult = null;
                        return;
                    }
                    this.tailscaleResult = data;
                } catch (e) {
                    this.tailscaleResult = null;
                    this.tailscaleError = @js(__('Failed to contact local Pi helper service.'));
                } finally {
                    this.tailscaleLoading = false;
                }
            },
            async fetchMatchDashboardEmail() {
                if (! confirm(@js(__('This checks Tailscale on the Pi against your login email. If it is a different account, the Pi signs out first, then you get a new sign-in link. It can take a minute or two. Continue?')))) {
                    return;
                }
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
                } catch (e) {
                    this.tailscaleResult = null;
                    this.tailscaleError = @js(__('Failed to contact local Pi helper service.'));
                } finally {
                    this.tailscaleLoading = false;
                }
            },
            async fetchForceTailscaleSignIn() {
                if (! confirm(@js(__('This signs the Pi out of Tailscale until you finish the browser login. Remote access over Tailscale pauses until you complete it. Continue?')))) {
                    return;
                }
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
                        body: JSON.stringify({ force_reauth: true }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.tailscaleError = data?.message ?? @js(__('Could not get a sign-in link. Try again in a few minutes.'));
                        this.tailscaleResult = null;
                        return;
                    }
                    this.tailscaleResult = data;
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
                {{ __('Get a Tailscale sign-in link for this Raspberry Pi. Open it on your phone or computer to finish setup. Optional: match the Pi to the same account as your login email (:email) if it was signed in as someone else.', ['email' => $user->email]) }}
            </p>
        </header>

        <div class="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-medium text-gray-900">{{ __('Tailscale on the Pi') }}</p>
                <button
                    type="button"
                    class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black hover:opacity-90 disabled:opacity-50"
                    @click="fetchQuickSignInLink()"
                    :disabled="tailscaleLoading"
                >
                    <span x-show="!tailscaleLoading">{{ __('Get Tailscale sign-in link') }}</span>
                    <span x-show="tailscaleLoading" style="display: none;">{{ __('Working…') }}</span>
                </button>
            </div>

            <p class="mt-3 text-xs text-gray-600">
                <button
                    type="button"
                    class="font-medium text-blue-700 underline hover:text-blue-900 disabled:opacity-50"
                    @click="fetchMatchDashboardEmail()"
                    :disabled="tailscaleLoading"
                >
                    {{ __('Match Pi to my login email (:email) — may take longer', ['email' => $user->email]) }}
                </button>
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
                            {{ __('If the Pi still does not appear in the Tailscale app on your phone, open a new sign-in link and use :email when Tailscale asks.', ['email' => $user->email]) }}
                            <button
                                type="button"
                                class="ml-1 font-medium text-blue-700 underline hover:text-blue-900 disabled:opacity-50"
                                @click="fetchForceTailscaleSignIn()"
                                :disabled="tailscaleLoading"
                            >
                                {{ __('Get a new sign-in link') }}
                            </button>
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
