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
            {{-- Action buttons: Import Excel (green) and Create New (red). shrink-0 prevents flex from collapsing these below their content width. --}}
            <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-2 sm:pl-2">
                <a href="{{ route('quizzes.import') }}"
                   class="inline-flex w-full shrink-0 items-center justify-center gap-1.5 rounded px-3 py-2 text-sm font-medium whitespace-nowrap text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base"
                   style="background-color: #10B981;"
                   aria-label="Import/export question bank">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"></path>
                    </svg>
                    <span>Question Bank Excel</span>
                </a>
                <a href="{{ route('quizzes.create') }}"
                   class="inline-flex w-full shrink-0 items-center justify-center gap-1 rounded px-3 py-2 text-sm font-medium whitespace-nowrap text-white hover:opacity-90 sm:w-auto sm:px-4 sm:text-base"
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

            {{-- Search + Filters for parent-friendly browsing --}}
            <form method="GET" action="{{ route('quizzes.index') }}" class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <div class="md:col-span-2">
                        <label for="q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Title, description, level, subject"
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                        >
                    </div>
                    <div>
                        <label for="level" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Level</label>
                        <select id="level" name="level" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">All Levels</option>
                            @foreach(($filterLevels ?? []) as $level)
                                <option value="{{ $level }}" @selected(request('level') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="subject" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Subject</label>
                        <select id="subject" name="subject" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">All Subjects</option>
                            @foreach(($filterSubjects ?? []) as $subject)
                                <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                        <select id="status" name="status" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-white hover:opacity-90" style="background-color: #3B82F6;">
                        Apply Filters
                    </button>
                    <a href="{{ route('quizzes.index') }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Reset
                    </a>
                    <span class="text-sm text-gray-500">
                        Showing <strong>{{ $quizzes->count() }}</strong> quiz{{ $quizzes->count() === 1 ? '' : 'zes' }}
                    </span>
                </div>
            </form>

            {{-- Random Quiz Mode Settings (single-row table) --}}
            <div class="mb-4 min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3 sm:px-6">
                    <h3 class="text-sm font-semibold text-gray-900">Time Reward Mode (Random Quiz) Settings</h3>
                    <p class="mt-1 text-xs text-gray-500">Configure one global random-quiz mode for child devices.</p>
                </div>
                <div class="p-4 sm:p-6">
                    <form method="POST" action="{{ route('quizzes.random-mode.update') }}">
                        @csrf
                        <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                            <table class="min-w-[760px] w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assigned Devices</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Minutes Per Correct Answer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Retry Interval (Minutes)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Max Uses Per Day</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            @php
                                                $selectedRandomDevices = old('devices', $randomModeDeviceIds ?? []);
                                                $selectedCount = is_array($selectedRandomDevices) ? count($selectedRandomDevices) : 0;
                                            @endphp
                                            <details class="w-full rounded border border-gray-300 bg-white">
                                                <summary class="cursor-pointer select-none px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                    {{ $selectedCount > 0 ? $selectedCount . ' device(s) selected' : 'Select child devices' }}
                                                </summary>
                                                <div class="max-h-44 overflow-y-auto border-t border-gray-200 px-3 py-2">
                                                    @forelse(($assignableDevices ?? []) as $device)
                                                        <label class="mb-2 flex items-center gap-2 text-sm text-gray-700">
                                                            <input
                                                                type="checkbox"
                                                                name="devices[]"
                                                                value="{{ $device->id }}"
                                                                @checked(in_array($device->id, $selectedRandomDevices))
                                                                class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                                            >
                                                            <span>{{ $device->name }} ({{ $device->mac_address }})</span>
                                                        </label>
                                                    @empty
                                                        <p class="text-xs text-gray-500">No registered child devices yet.</p>
                                                    @endforelse
                                                </div>
                                            </details>
                                            <p class="mt-1 text-xs text-gray-500">Open the dropdown and check devices to include in random quiz mode.</p>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <input
                                                type="number"
                                                name="minutes_per_correct"
                                                min="1"
                                                max="60"
                                                value="{{ old('minutes_per_correct', $randomModeQuiz->minutes_per_correct ?? 1) }}"
                                                required
                                                class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                            >
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <input
                                                type="number"
                                                name="retry_cooldown_minutes"
                                                min="0"
                                                max="10080"
                                                value="{{ old('retry_cooldown_minutes', $randomModeQuiz->retry_cooldown_minutes) }}"
                                                placeholder="0 or blank = no wait"
                                                class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                            >
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <input
                                                type="number"
                                                name="max_passes_per_day"
                                                min="1"
                                                max="500"
                                                value="{{ old('max_passes_per_day', $randomModeQuiz->max_passes_per_day) }}"
                                                placeholder="Blank = unlimited"
                                                class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                            >
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-white hover:opacity-90" style="background-color: #3B82F6;">
                                                Save
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>

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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
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
                                            @php
                                                $levelBadge = match ($quiz->level) {
                                                    'Elementary' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                                                    'High School' => ['bg' => '#EDE9FE', 'text' => '#6D28D9'],
                                                    'Senior High School' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
                                                    default => ['bg' => '#E5E7EB', 'text' => '#374151'],
                                                };
                                                $rowHighlight = match (strtolower((string) $quiz->subject)) {
                                                    'math' => '#35858E',
                                                    'english' => '#7DA78C',
                                                    'science' => '#C2D099',
                                                    default => '#E6EEC9',
                                                };
                                                $rowTextColor = match (strtolower((string) $quiz->subject)) {
                                                    'math', 'english' => '#F9FAFB',
                                                    default => '#111827',
                                                };
                                                $rowSubTextColor = match (strtolower((string) $quiz->subject)) {
                                                    'math', 'english' => '#E5E7EB',
                                                    default => '#374151',
                                                };
                                            @endphp
                                            <td class="px-6 py-4 whitespace-nowrap" style="background-color: {{ $rowHighlight }};">
                                                <div class="text-sm font-medium" style="color: {{ $rowTextColor }};">{{ $quiz->title }}</div>
                                                @if($quiz->description)
                                                    <div class="text-sm" style="color: {{ $rowSubTextColor }};">{{ Str::limit($quiz->description, 50) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: {{ $levelBadge['bg'] }}; color: {{ $levelBadge['text'] }};">
                                                    {{ $quiz->level ?? 'Legacy' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                {{ $quiz->subject ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                {{ $quiz->question_count ?? count($quiz->questions['questions'] ?? []) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                {{ $quiz->passing_score }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                {{ $quiz->time_reward_minutes }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap" style="background-color: {{ $rowHighlight }};">
                                                @if($quiz->is_active)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full" style="background-color: #10B981; color: white;">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-400 text-white">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm" style="background-color: {{ $rowHighlight }}; color: {{ $rowTextColor }};">
                                                {{ $quiz->attempts_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="background-color: {{ $rowHighlight }};">
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

