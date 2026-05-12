<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li><strong>Profile</strong> updates your name and email used to sign in. If you change your email, confirm the new address with a code sent to that inbox, then a short Tailscale step appears before the change is saved.</li>
                    <li><strong>Remote dashboard access (Tailscale)</strong> uses one button on this page so the Pi can send you a sign-in link. You will also find plain-language steps for installing Tailscale on your phone or computer, plus the same away-from-home web link that appears in report emails—so you do not have to wait for an email to open the dashboard.</li>
                    <li><strong>Password</strong> is only if you want a new one—you must type your current password to confirm.</li>
                    <li><strong>Delete account</strong> removes your account for good. Only use it if you are sure.</li>
                </ul>
            </x-collapsible-instructions>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.tailscale-remote-access-card', [
                        'remote_dashboard_url' => $remote_dashboard_url ?? null,
                    ])
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
