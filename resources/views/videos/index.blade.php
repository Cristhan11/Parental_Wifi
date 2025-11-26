{{-- 
    Parent Dashboard: Video List View
    
    This view displays all videos created by the logged-in parent in a table format.
    Parents can see video details (title, description, duration, word settings, time reward) and
    perform actions (create, edit, delete).
    
    Data Flow:
    1. VideoController@index fetches videos from database
    2. Passes $videos array to this view
    3. View loops through videos and displays in table
    4. Action buttons link to create, edit, delete routes
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway --}}
        <div class="flex items-center justify-between" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex items-center space-x-3">
                <a href="{{ route('dashboard') }}" class="text-black hover:opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <h2 class="font-semibold text-xl text-black leading-tight">
                    VIDEOS
                </h2>
            </div>
            {{-- Action button: Create New (red) --}}
            <div class="flex space-x-2">
                <a href="{{ route('videos.create') }}" class="px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                    + New
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success/Error flash messages --}}
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6">
                    {{-- 
                        Video Table Display
                        Shows videos in a table if any exist, otherwise shows empty state message.
                        Table columns: Title, Description, Duration, Dictionary Words, Time Reward, Status, Completions, Actions
                    --}}
                    @if($videos->count() > 0)
                        {{-- Video table: displays video details and action buttons --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dictionary Words</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Reward</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($videos as $video)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $video->title }}</div>
                                                @if($video->description)
                                                    <div class="text-sm text-gray-500">{{ Str::limit($video->description, 50) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $video->getDurationFormatted() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                @if($video->dictionary_words_enabled)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">
                                                        {{ $video->word_count }} words
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">Disabled</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $video->time_reward_minutes }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($video->is_active)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-400 text-white">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $video->completions_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('videos.edit', $video) }}" class="px-3 py-1 rounded text-white hover:opacity-90" style="background-color: #3B82F6;">
                                                        Edit
                                                    </a>
                                                    <form action="{{ route('videos.destroy', $video) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-3 py-1 rounded text-white hover:opacity-90" style="background-color: #EF4444;">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-500 mb-4">No videos created yet.</p>
                            <a href="{{ route('videos.create') }}" class="inline-block px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                Create Your First Video
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

