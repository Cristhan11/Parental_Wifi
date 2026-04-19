<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Password reset requests</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        <p class="text-gray-600 text-sm mb-6">
            Parents who used <strong>Forgot password</strong> are listed here. Apply the default password when you have confirmed their identity out of band.
        </p>

        <div class="bg-white shadow border border-gray-200 rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left p-3 font-semibold">Name</th>
                        <th class="text-left p-3 font-semibold">Email</th>
                        <th class="text-left p-3 font-semibold">Requested</th>
                        <th class="text-right p-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr class="border-b">
                            <td class="p-3">{{ $req->user?->name ?? '—' }}</td>
                            <td class="p-3">{{ $req->user?->email ?? '—' }}</td>
                            <td class="p-3">{{ $req->created_at->format('M j, Y g:i A') }}</td>
                            <td class="p-3 text-right">
                                @if ($req->user)
                                    <form action="{{ route('admin.password-reset-requests.fulfill', $req) }}" method="POST" class="inline" onsubmit="return confirm('Set this account’s password to the default 12345678?');">
                                        @csrf
                                        <x-primary-button type="submit" class="text-xs">Apply default password</x-primary-button>
                                    </form>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500">No pending password reset requests.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>

        <p class="mt-6 text-xs text-gray-500">
            <a href="{{ route('admin.dashboard') }}" class="underline text-black">← Back to administration</a>
        </p>
    </div>
</x-app-layout>
