@if ($user->hasParentCapability())
    <section
        x-data="{
            tailscaleLoading: false,
            tailscaleResult: null,
            tailscaleError: null,
            lastTailscaleForce: false,
            requestTailscaleSwitch() {
                if (! confirm(@js(__('This signs the Pi out of Tailscale until you finish the browser login. Remote access over Tailscale stops until you complete it. Continue?')))) {
                    return;
                }
                this.fetchTailscaleLink(true);
            },
            async fetchTailscaleLink(forceReauth = false) {
                this.lastTailscaleForce = !!forceReauth;
                this.tailscaleLoading = true;
                this.tailscaleError = null;
                try {
                    const res = await fetch(@js(route('profile.tailscale.auth-link')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ force_reauth: !!forceReauth }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.tailscaleError = data?.message ?? @js(__('Failed to get Tailscale sign-in link.'));
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
        }"
    >
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Remote dashboard access (Tailscale)') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('To reach this dashboard from outside your home Wi-Fi, the Raspberry Pi must be signed in to Tailscale. Use the same identity you use for the login email (:email) when Tailscale asks you to sign in.', ['email' => $user->email]) }}
            </p>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('If the Pi was signed in with a different Google or Microsoft account, use “Sign in with a different Tailscale account” so the Pi can get a fresh link for this email.') }}
            </p>
        </header>

        <div class="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-medium text-gray-900">{{ __('Tailscale on the Pi') }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black hover:opacity-90 disabled:opacity-50"
                        @click="fetchTailscaleLink()"
                        :disabled="tailscaleLoading"
                    >
                        <span x-show="!tailscaleLoading">{{ __('Get Tailscale sign-in link') }}</span>
                        <span x-show="tailscaleLoading" style="display: none;">{{ __('Loading…') }}</span>
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                        @click="fetchTailscaleLink(lastTailscaleForce)"
                        :disabled="tailscaleLoading"
                        x-show="tailscaleResult || tailscaleError"
                        style="display: none;"
                    >
                        {{ __('Refresh link') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-amber-600 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-50 disabled:opacity-50"
                        @click="requestTailscaleSwitch()"
                        :disabled="tailscaleLoading"
                    >
                        {{ __('Sign in with a different Tailscale account') }}
                    </button>
                </div>
            </div>

            <template x-if="tailscaleResult">
                <div class="mt-3 border-t border-gray-200 pt-3">
                    <p>
                        <span class="font-medium">{{ __('Pi status:') }}</span>
                        <span x-text="tailscaleResult.status"></span>
                    </p>
                    <p class="mt-1" x-text="tailscaleResult.message"></p>
                    <template x-if="tailscaleResult.status === 'action_required' && tailscaleResult.auth_url">
                        <p class="mt-2">
                            <a
                                :href="tailscaleResult.auth_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-blue-700 underline"
                            >
                                {{ __('Open Tailscale sign-in link') }}
                            </a>
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
