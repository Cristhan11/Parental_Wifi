<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-black leading-tight">Pending parent registrations</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        @if (session('status'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif

        <p class="text-gray-600 text-sm mb-6">
            Parents must verify their email before you approve them. “Ready to approve” means the address is verified.
        </p>

        <div class="bg-white shadow border border-gray-200 rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left p-3 font-semibold">Name</th>
                        <th class="text-left p-3 font-semibold">Email</th>
                        <th class="text-left p-3 font-semibold">Registered</th>
                        <th class="text-left p-3 font-semibold">Email status</th>
                        <th class="text-right p-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parents as $user)
                        <tr class="border-b">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->created_at->format('M j, Y g:i A') }}</td>
                            <td class="p-3">
                                @if ($user->hasVerifiedEmail())
                                    <span class="text-green-700 font-medium">Verified — ready to approve</span>
                                @else
                                    <span class="text-amber-700">Not verified yet</span>
                                @endif
                            </td>
                            <td class="p-3 text-right align-top">
                                <div class="flex flex-col items-end gap-2">
                                    @if ($user->hasVerifiedEmail())
                                        <form action="{{ route('admin.parents.approve', $user) }}" method="POST">
                                            @csrf
                                            <x-primary-button type="submit">Approve</x-primary-button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.parents.reject', $user) }}" method="POST" class="w-full max-w-xs" onsubmit="return confirm('Reject this registration?');">
                                        @csrf
                                        <label class="block text-xs text-gray-500 text-left mb-1">Optional note to registrant</label>
                                        <textarea name="note" rows="2" class="w-full text-xs rounded border-gray-300 mb-1" placeholder="Reason (optional)"></textarea>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">No pending registrations.</td>
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
