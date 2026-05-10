<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Parent Owner</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
        @if (request()->boolean('verified'))
            <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-900">
                {{ __('Email verified.') }}
                {{ __('Sign in to the Tailscale app on your Raspberry Pi and other devices with this same address (:email) when you connect remotely—or sign in again if you just changed email.', ['email' => Auth::user()->email]) }}
            </div>
        @endif
        <p class="text-gray-600 mb-8">System overview and parent account management.</p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-10">
            <div class="bg-white border-4 border-[#FFDE15] rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500">Pending parent approvals</div>
                <div class="text-3xl font-bold text-black">{{ $pendingParents }}</div>
                <a href="{{ route('admin.parents.pending') }}" class="text-sm underline mt-2 inline-block text-black">Review queue</a>
            </div>
            <div class="bg-white border-4 border-gray-200 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500">Approved parent accounts</div>
                <div class="text-3xl font-bold text-black">{{ $parentCount }}</div>
                <a href="{{ route('admin.parents.index') }}" class="text-sm underline mt-2 inline-block text-black">View all</a>
            </div>
            <div class="bg-white border-4 border-gray-200 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500">Devices (all accounts)</div>
                <div class="text-3xl font-bold text-black">{{ $deviceCount }}</div>
            </div>
            <div class="bg-white border-4 border-gray-200 rounded-lg p-4 shadow">
                <div class="text-sm text-gray-500">Password reset requests</div>
                <div class="text-3xl font-bold text-black">{{ $pendingPasswordResetRequests }}</div>
                <a href="{{ route('admin.password-reset-requests.index') }}" class="text-sm underline mt-2 inline-block text-black">Review queue</a>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-gray-800">
            <strong>Account types:</strong> <em>Parent</em> manages only their own devices. <em>Household operator</em> combines parent features with this Parent Owner area. <em>Parent Owner</em> is for installation and approvals.
        </div>
    </div>
</x-app-layout>
