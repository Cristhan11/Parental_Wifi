{{-- 
    Parent Dashboard: Quiz List View
    
    This view displays all quizzes created by the logged-in parent in a table format.
    Parents can see quiz details (title, description, passing score, time reward) and
    perform actions (create, import, edit, delete).
    
    Data Flow:
    1. QuizController@index fetches quizzes from database
    2. Passes $quizzes array to this view
    3. View loops through quizzes and displays in table
    4. Action buttons link to create, edit, delete, import routes
--}}
<x-app-layout>
    <x-slot name="header">
        {{-- Yellow header bar matching design colorway --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0" style="background-color: #FFDE15; padding: 1rem; border-radius: 0.5rem;">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <a href="{{ route('dashboard') }}" class="shrink-0 text-black hover:opacity-75">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <svg class="h-6 w-6 shrink-0 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <h2 class="min-w-0 text-base font-semibold leading-tight text-black sm:text-xl">
                    QUIZZES
                </h2>
            </div>
            {{-- Action buttons: Import Excel (green) and Create New (red) --}}
            <div class="flex w-full min-w-0 flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-2">
                <a href="{{ route('quizzes.import') }}"
                   class="inline-flex w-full items-center justify-center rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base"
                   style="background-color: #10B981;"
                   aria-label="Import quizzes from Excel">
                    <span class="sm:hidden">Import</span><span class="hidden sm:inline">Import Excel</span>
                </a>
                <a href="{{ route('quizzes.create') }}"
                   class="inline-flex w-full items-center justify-center gap-1 rounded px-3 py-2 text-sm font-medium text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base whitespace-nowrap"
                   style="background-color: #EF4444;">
                    <span aria-hidden="true">+</span> New
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">
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

            <div class="min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="min-w-0 p-4 sm:p-6">
                    {{-- 
                        Quiz Table Display
                        Shows quizzes in a table if any exist, otherwise shows empty state message.
                        Table columns: Title, Description, Passing Score, Time Reward, Actions
                    --}}
                    @if($quizzes->count() > 0)
                        {{-- Quiz table: displays quiz details and action buttons --}}
                        <div class="-mx-4 min-w-0 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                            <table class="min-w-[640px] w-full divide-y divide-gray-200 sm:min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passing Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Reward</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($quizzes as $quiz)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $quiz->title }}</div>
                                                @if($quiz->description)
                                                    <div class="text-sm text-gray-500">{{ Str::limit($quiz->description, 50) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ count($quiz->questions['questions'] ?? []) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $quiz->passing_score }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $quiz->time_reward_minutes }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($quiz->is_active)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-400 text-white">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $quiz->attempts_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="{{ route('quizzes.edit', $quiz) }}" class="px-3 py-1 rounded text-white hover:opacity-90" style="background-color: #3B82F6;">
                                                        Edit
                                                    </a>
                                                    <form action="{{ route('quizzes.destroy', $quiz) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
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
                            <p class="text-gray-500 mb-4">No quizzes created yet.</p>
                            <a href="{{ route('quizzes.create') }}" class="inline-block px-4 py-2 rounded text-white font-medium hover:opacity-90" style="background-color: #EF4444;">
                                Create Your First Quiz
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

