@if ($user->hasParentCapability())
    <section
        x-data="{
            tailscaleLoading: false,
            tailscaleResult: null,
            tailscaleError: null,
            async setupTailscaleRemoteAccess() {
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
        }"
    >
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Remote dashboard access (Tailscale)') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Use one button to connect this Raspberry Pi to Tailscale with the same identity as your login email (:email). If the Pi was on a different account, we sign it out first, then you open the link and sign in on your phone or computer.', ['email' => $user->email]) }}
            </p>
        </header>

        <div class="mt-6 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-medium text-gray-900">{{ __('Tailscale on the Pi') }}</p>
                <button
                    type="button"
                    class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black hover:opacity-90 disabled:opacity-50"
                    @click="setupTailscaleRemoteAccess()"
                    :disabled="tailscaleLoading"
                >
                    <span x-show="!tailscaleLoading">{{ __('Set up remote access (Tailscale)') }}</span>
                    <span x-show="tailscaleLoading" style="display: none;">{{ __('Working…') }}</span>
                </button>
            </div>

            <template x-if="tailscaleResult">
                <div class="mt-3 border-t border-gray-200 pt-3">
                    <p class="text-gray-800" x-text="tailscaleResult.message"></p>
                    <template x-if="tailscaleResult.status === 'action_required' && tailscaleResult.auth_url">
                        <p class="mt-2">
                            <a
                                :href="tailscaleResult.auth_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-blue-700 underline"
                            >
                                {{ __('Open sign-in link') }}
                            </a>
                        </p>
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
