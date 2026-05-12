<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("This email is your dashboard login. Verify it—it is also what you should use on Tailscale for remote access (not reporting-only addresses added elsewhere).") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        class="mt-6 space-y-6"
        x-data="{
            originalEmail: @js((string) $user->email),
            formEmail: @js((string) old('email', $user->email)),
            serverVerifiedEmail: @js(\App\Support\Auth\ProfileEmailChangeSession::verifiedEmail(request())),
            showEmailVerifyModal: false,
            showTailscaleModal: false,
            tailscaleConfirmed: false,
            tailscaleLoading: false,
            tailscaleResult: null,
            tailscaleError: null,
            emailVerifyCode: '',
            emailVerifyError: null,
            emailVerifySending: false,
            emailVerifyLoading: false,
            normalizeEmail(value) {
                return (value ?? '').trim().toLowerCase();
            },
            shouldIntercept() {
                return this.normalizeEmail(this.formEmail) !== this.normalizeEmail(this.originalEmail);
            },
            emailMatchesSessionVerified() {
                const v = this.serverVerifiedEmail;
                if (!v) return false;
                return this.normalizeEmail(this.formEmail) === this.normalizeEmail(v);
            },
            async fetchTailscaleLink() {
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
                    const data = await res.json();
                    if (!res.ok) {
                        this.tailscaleError = data?.message ?? 'Failed to get Tailscale sign-in link.';
                        this.tailscaleResult = null;
                        return;
                    }
                    this.tailscaleResult = data;
                } catch (e) {
                    this.tailscaleResult = null;
                    this.tailscaleError = 'Failed to contact local Pi helper service.';
                } finally {
                    this.tailscaleLoading = false;
                }
            },
            async sendEmailChangeCode() {
                this.emailVerifySending = true;
                this.emailVerifyError = null;
                try {
                    const res = await fetch(@js(route('profile.email-change.send-code')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ email: this.formEmail }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const msg = data?.errors?.email?.[0] ?? data?.message ?? @js(__('Could not send the confirmation code. Try again.'));
                        this.emailVerifyError = msg;
                        return;
                    }
                    if (data?.already_verified) {
                        this.serverVerifiedEmail = this.normalizeEmail(this.formEmail);
                        this.showEmailVerifyModal = false;
                        this.showTailscaleModal = true;
                        this.emailVerifyCode = '';
                        await this.fetchTailscaleLink();
                    }
                } catch (e) {
                    this.emailVerifyError = @js(__('Could not send the confirmation code. Try again.'));
                } finally {
                    this.emailVerifySending = false;
                }
            },
            async verifyEmailChangeFromModal() {
                this.emailVerifyLoading = true;
                this.emailVerifyError = null;
                try {
                    const res = await fetch(@js(route('profile.email-change.verify-code')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ code: this.emailVerifyCode }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const msg = data?.errors?.code?.[0] ?? data?.message ?? @js(__('That confirmation number is not valid.'));
                        this.emailVerifyError = msg;
                        return;
                    }
                    this.serverVerifiedEmail = data?.email ?? this.normalizeEmail(this.formEmail);
                    this.showEmailVerifyModal = false;
                    this.emailVerifyCode = '';
                    this.showTailscaleModal = true;
                    await this.fetchTailscaleLink();
                } catch (e) {
                    this.emailVerifyError = @js(__('Something went wrong. Try again.'));
                } finally {
                    this.emailVerifyLoading = false;
                }
            },
            async handleSubmit() {
                if (!this.shouldIntercept()) {
                    this.$el.submit();
                    return;
                }
                if (!this.emailMatchesSessionVerified()) {
                    if (!this.showEmailVerifyModal) {
                        this.showEmailVerifyModal = true;
                        this.emailVerifyCode = '';
                        this.emailVerifyError = null;
                        await this.sendEmailChangeCode();
                    }
                    return;
                }
                if (!this.showTailscaleModal) {
                    this.showTailscaleModal = true;
                    await this.fetchTailscaleLink();
                    return;
                }
                if (!this.tailscaleConfirmed) {
                    return;
                }
                this.$el.submit();
            },
        }"
        @submit.prevent="handleSubmit"
    >
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" x-model="formEmail" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification code.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-code-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification code has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>

        <div
            x-show="showEmailVerifyModal"
            x-transition
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4"
            style="display: none;"
            @keydown.escape.window="showEmailVerifyModal = false"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Confirm your new email') }}</h3>
                <p class="mt-2 text-sm text-gray-700">
                    {{ __('We sent a six-digit confirmation number to') }}
                    <span class="font-mono font-medium text-gray-900" x-text="formEmail"></span>.
                    {{ __('Enter it below to continue. After that, you will finish the Tailscale step before the profile is saved.') }}
                </p>

                <div class="mt-4">
                    <x-input-label for="profile-email-change-code" :value="__('Confirmation number')" />
                    <x-text-input
                        id="profile-email-change-code"
                        class="mt-1 block w-full"
                        type="text"
                        x-model="emailVerifyCode"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        @keydown.enter.prevent="verifyEmailChangeFromModal()"
                    />
                    <p x-show="emailVerifyError" x-text="emailVerifyError" class="mt-2 text-sm text-red-600" style="display: none;"></p>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <button
                        type="button"
                        class="text-sm font-medium text-gray-600 underline hover:text-gray-900 disabled:opacity-50"
                        :disabled="emailVerifySending"
                        @click="sendEmailChangeCode()"
                    >
                        <span x-show="!emailVerifySending">{{ __('Resend code') }}</span>
                        <span x-show="emailVerifySending" style="display:none;">{{ __('Sending…') }}</span>
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showEmailVerifyModal = false">
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black disabled:opacity-50"
                            :disabled="emailVerifyLoading || emailVerifyCode.trim().length !== 6"
                            @click="verifyEmailChangeFromModal()"
                        >
                            <span x-show="!emailVerifyLoading">{{ __('Verify and continue') }}</span>
                            <span x-show="emailVerifyLoading" style="display:none;">{{ __('Checking…') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="showTailscaleModal"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            style="display: none;"
            @keydown.escape.window="showTailscaleModal = false"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Finish Tailscale setup for the new email') }}</h3>
                <p class="mt-2 text-sm text-gray-700">
                    {{ __('You changed your account email. Before we save, update Tailscale so remote access uses this new email too.') }}
                </p>
                <p class="mt-2 text-sm text-gray-700">
                    {{ __('The Pi will sign out of Tailscale first, then you get a browser link—sign in with the same identity as your new dashboard email below.') }}
                </p>
                <p class="mt-1 text-sm text-gray-700">
                    {{ __('New email:') }} <span class="font-mono text-gray-900" x-text="formEmail"></span>
                </p>

                <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium">{{ __('Get Tailscale sign-in link') }}</p>
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold hover:bg-gray-100" @click="fetchTailscaleLink()" :disabled="tailscaleLoading">
                            <span x-show="!tailscaleLoading">{{ __('Refresh link') }}</span>
                            <span x-show="tailscaleLoading" style="display:none;">{{ __('Loading...') }}</span>
                        </button>
                    </div>

                    <template x-if="tailscaleResult">
                        <div class="mt-2">
                            <p>
                                <span class="font-medium">{{ __('Pi status:') }}</span>
                                <span x-text="tailscaleResult.status"></span>
                            </p>
                            <p class="mt-1" x-text="tailscaleResult.message"></p>
                            <template x-if="tailscaleResult.status === 'action_required' && tailscaleResult.auth_url">
                                <p class="mt-2">
                                    <a :href="tailscaleResult.auth_url" target="_blank" rel="noopener noreferrer" class="font-medium underline text-blue-700">
                                        {{ __('Open Tailscale sign-in link') }}
                                    </a>
                                </p>
                            </template>
                        </div>
                    </template>

                    <template x-if="tailscaleError">
                        <p class="mt-2 text-red-700" x-text="tailscaleError"></p>
                    </template>
                </div>

                <label class="mt-4 flex items-start gap-2 text-sm text-gray-800">
                    <input type="checkbox" class="mt-1 rounded border-gray-300" x-model="tailscaleConfirmed">
                    <span>{{ __('I have updated Tailscale sign-in using the new email, and I want to continue saving this profile change.') }}</span>
                </label>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showTailscaleModal = false">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" class="rounded-md bg-[#FFDE15] px-4 py-2 text-sm font-semibold text-black disabled:opacity-50" :disabled="!tailscaleConfirmed" @click="handleSubmit()">
                        {{ __('Continue and save') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
