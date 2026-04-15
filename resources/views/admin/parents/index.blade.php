<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Parent accounts</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
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
                            <td class="p-3 text-right">
                                @if ($user->isStrictParentRole() && $user->isApprovedParentAccount())
                                    <form action="{{ route('admin.parents.promote', $user) }}" method="POST" class="inline" onsubmit="return confirm('Grant this parent access to Administration (household operator)?');">
                                        @csrf
                                        <button type="submit" class="text-xs underline text-black">Make household operator</button>
                                    </form>
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
</x-app-layout>
