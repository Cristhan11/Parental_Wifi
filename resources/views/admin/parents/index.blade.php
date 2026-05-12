<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Parent accounts</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-800 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.parents.index') }}" class="mb-6 flex gap-2 flex-wrap items-end">
            <div>
                <label for="q" class="block text-xs text-gray-500 mb-1">Search</label>
                <input type="text" name="q" id="q" value="{{ $q }}" placeholder="Name or email"
                       class="rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500" />
            </div>
            <x-primary-button type="submit">Search</x-primary-button>
        </form>

        <div class="bg-white shadow border border-gray-200 rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left p-3 font-semibold">Name</th>
                        <th class="text-left p-3 font-semibold">Email</th>
                        <th class="text-left p-3 font-semibold">Account type</th>
                        <th class="text-left p-3 font-semibold">Approved</th>
                        <th class="text-right p-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parents as $user)
                        <tr class="border-b">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->accountTypeLabel() }}</td>
                            <td class="p-3">{{ $user->approved_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="p-3 text-right align-top">
                                @if ($user->isApprovedParentAccount())
                                    <div class="flex flex-col items-end gap-2 text-xs">
                                        <a href="{{ route('admin.parents.edit', $user) }}" class="underline text-black">Edit</a>
                                        @if ($user->isStrictParentRole())
                                            <form action="{{ route('admin.parents.promote', $user) }}" method="POST" class="inline" onsubmit="return confirm('Grant this parent access to Parent Owner tools (household operator)?');">
                                                @csrf
                                                <button type="submit" class="underline text-black">Make household operator</button>
                                            </form>
                                        @elseif ($user->isParentAdmin())
                                            @if ($householdOperatorCount > 1)
                                                <form action="{{ route('admin.parents.demote', $user) }}" method="POST" class="inline" onsubmit="return confirm('Remove Parent Owner tool access and make this a standard parent account?');">
                                                    @csrf
                                                    <button type="submit" class="underline text-black">Remove household operator</button>
                                                </form>
                                            @else
                                                <span class="text-gray-400">Remove household operator (disabled: last operator)</span>
                                            @endif
                                        @endif
                                        @if (auth()->user()->isParentAdmin() && $user->id !== auth()->id())
                                            <form action="{{ route('admin.parents.reset-password-default', $user) }}" method="POST" class="inline" onsubmit="return confirm('Set this account’s password to the default 12345678? The parent will be required to change it on next login.');">
                                                @csrf
                                                <button type="submit" class="underline text-black">Reset password to default</button>
                                            </form>
                                        @endif
                                        @if ($user->isStrictParentRole())
                                            <form action="{{ route('admin.parents.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete this parent account and all related data? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="underline text-red-700">Delete account</button>
                                            </form>
                                        @else
                                            <span class="text-gray-400">Delete account (only standard parents)</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $parents->links() }}
        </div>
    </div>

    @if ($popup = session('default_password_popup'))
        <div x-data="{ open: true }" x-cloak x-show="open"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             role="dialog" aria-modal="true" aria-labelledby="default-password-popup-title">
            <div @click.outside="open = false" @keydown.escape.window="open = false"
                 class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-100 text-yellow-700 flex items-center justify-center font-bold">!</div>
                    <div class="flex-1">
                        <h3 id="default-password-popup-title" class="text-lg font-semibold text-black">Password reset to default</h3>
                        <p class="mt-2 text-sm text-gray-700">
                            <span class="font-medium text-black">{{ $popup['parent_name'] }}</span>
                            (<span class="text-gray-600">{{ $popup['parent_email'] }}</span>)
                            can now sign in with the default password below. They will be required to change it immediately after logging in.
                        </p>
                        <div class="mt-4 p-3 rounded-md bg-gray-50 border border-gray-200">
                            <div class="text-xs uppercase tracking-wider text-gray-500">Default password</div>
                            <div class="mt-1 flex items-center gap-2">
                                <code id="default-password-popup-value" class="text-lg font-mono font-bold text-black select-all">{{ $popup['default_password'] }}</code>
                                <button type="button"
                                        class="text-xs underline text-black hover:opacity-75"
                                        onclick="navigator.clipboard?.writeText(document.getElementById('default-password-popup-value').innerText); this.textContent='Copied';">
                                    Copy
                                </button>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">
                            Share this with the parent through a trusted channel. They will be forced to set a new password on their next login.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" @click="open = false"
                            class="inline-flex items-center px-4 py-2 bg-black text-white text-xs font-semibold rounded-md hover:opacity-80 focus:outline-none">
                        Got it
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
