{{-- 
    Blocked Websites: Index View
    
    Household-wide blocked websites for the authenticated parent.
    Search by domain; applies to all child devices.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    BLOCKED WEBSITES
                </h2>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('blocked-websites.create') }}" class="px-4 py-2 rounded text-white font-medium hover:opacity-90 flex items-center space-x-1" style="background-color: #EF4444;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Block Website</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 rounded-lg" style="background-color: #10B981; color: white;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 rounded-lg" style="background-color: #EF4444; color: white;">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 p-4 rounded-lg border border-amber-400" style="background-color: #FEF3C7; color: #92400E;">
                    {{ session('warning') }}
                </div>
            @endif

            @if(session('info'))
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    {{ session('info') }}
                </div>
            @endif

            <x-policy-apply-status />

            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>This list applies to <strong>all</strong> your child devices.</li>
                    <li>Sites you add here are <strong>blocked</strong>—they should not open for your children on your home network.</li>
                    <li>Use <strong>Search</strong> to find a domain, or tap <strong>Block Website</strong> above to add one. <strong>Flagged</strong> sites are different: they stay open but visits show in your reports.</li>
                </ul>
            </x-collapsible-instructions>

            {{-- Search --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 mb-4 p-4">
                <form method="GET" action="{{ route('blocked-websites.index') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                placeholder="Domain..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            {{-- Blocked Websites Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    @if($blockedWebsites->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subdomains</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Related Domains</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($blockedWebsites as $blockedWebsite)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span class="font-mono">{{ $blockedWebsite->domain }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($blockedWebsite->block_subdomains)
                                                    <span class="text-green-600">Yes</span>
                                                @else
                                                    <span class="text-gray-400">No</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                @if($blockedWebsite->isAppBlock() && is_array($blockedWebsite->related_domains) && count($blockedWebsite->related_domains) > 0)
                                                    <details class="cursor-pointer">
                                                        <summary class="text-blue-600 hover:underline">
                                                            {{ count($blockedWebsite->related_domains) }} domain(s)
                                                        </summary>
                                                        <ul class="mt-2 space-y-1 list-disc list-inside">
                                                            @foreach($blockedWebsite->related_domains as $relatedDomain)
                                                                <li class="font-mono text-xs">{{ $relatedDomain }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $blockedWebsite->reason ? Str::limit($blockedWebsite->reason, 30) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('blocked-websites.edit', $blockedWebsite) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                                <form action="{{ route('blocked-websites.destroy', $blockedWebsite) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this blocked website?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $blockedWebsites->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">No blocked websites found.</p>
                            <a href="{{ route('blocked-websites.create') }}" class="inline-block px-4 py-2 bg-yellow-500 text-black rounded-md hover:bg-yellow-600 font-medium">
                                Block Your First Website
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

