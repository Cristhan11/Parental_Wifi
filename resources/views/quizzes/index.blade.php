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
                   aria-label="Import or export quiz (Excel)">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"></path>
                    </svg>
                    <span>Import / export quiz</span>
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

            <x-collapsible-instructions class="mb-4">
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li><strong>+ New</strong> creates a quiz. Open <strong>Edit</strong> to change questions and which <strong>child</strong> devices can take it.</li>
                    <li><strong>Import / export quiz</strong> opens the Excel import page (template, import, and optional export).</li>
                    <li>Set <strong>Search</strong>, <strong>Level</strong>, <strong>Subject</strong>, or <strong>Status</strong> as needed, then tap <strong>Apply Filters</strong>. <strong>Reset</strong> clears those choices.</li>
                    <li><strong>Random Quiz Settings</strong> (below) sets minutes per correct answer, retry limits, and which school levels each child device pulls from the bank. Tap <strong>Save</strong> when you change that block.</li>
                </ul>
            </x-collapsible-instructions>

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

            {{-- Random Quiz Mode: global time settings + per-device bank levels (collapsible, same pattern as /reports Advanced options) --}}
            @php
                $openRandomQuizSettings = $errors->has('minutes_per_correct')
                    || $errors->has('retry_cooldown_minutes')
                    || $errors->has('max_passes_per_day')
                    || collect($errors->keys())->contains(fn ($k) => str_starts_with((string) $k, 'device_random_levels'));
            @endphp
            <details class="mb-4 min-w-0 bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5" @if($openRandomQuizSettings) open @endif>
                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-sm text-gray-700 hover:bg-yellow-100 select-none [&::-webkit-details-marker]:hidden">
                    <span class="font-medium">Random Quiz Settings</span>
                    <span class="text-xs text-gray-500">(time reward, question bank levels per child device)</span>
                </summary>
                <div class="mt-5 space-y-5 border-t border-gray-100 pt-5">
                    <p class="text-sm text-gray-600">Set minutes and limits once, then choose which school levels each child device uses from the question bank. Leave all levels unchecked on a device to turn random mode off for that device.</p>
                    <form method="POST" action="{{ route('quizzes.random-mode.update') }}">
                        @csrf
                        @php
                            $levelChoices = \App\Support\QuizSchoolLevel::levels();
                        @endphp
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label for="minutes_per_correct" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Minutes per correct answer</label>
                                <input
                                    type="number"
                                    id="minutes_per_correct"
                                    name="minutes_per_correct"
                                    min="1"
                                    max="60"
                                    value="{{ old('minutes_per_correct', $randomModeQuiz->minutes_per_correct ?? 1) }}"
                                    required
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                >
                            </div>
                            <div>
                                <label for="retry_cooldown_minutes" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Retry interval (minutes)</label>
                                <input
                                    type="number"
                                    id="retry_cooldown_minutes"
                                    name="retry_cooldown_minutes"
                                    min="0"
                                    max="10080"
                                    value="{{ old('retry_cooldown_minutes', $randomModeQuiz->retry_cooldown_minutes) }}"
                                    placeholder="0 or blank = no wait"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                >
                            </div>
                            <div>
                                <label for="max_passes_per_day" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Max uses per day</label>
                                <input
                                    type="number"
                                    id="max_passes_per_day"
                                    name="max_passes_per_day"
                                    min="1"
                                    max="500"
                                    value="{{ old('max_passes_per_day', $randomModeQuiz->max_passes_per_day) }}"
                                    placeholder="Blank = unlimited"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                >
                            </div>
                        </div>

                        <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                            <table class="min-w-[640px] w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Child device</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Question bank levels (this device only)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse(($assignableDevices ?? []) as $device)
                                        @php
                                            $pivotDev = $randomModeQuiz->devices->firstWhere('id', $device->id);
                                            $pivotLevels = $pivotDev?->pivot?->random_bank_levels;
                                            if (! is_array($pivotLevels)) {
                                                $pivotLevels = [];
                                            }
                                            $oldRow = old('device_random_levels.'.$device->id);
                                            $selected = is_array($oldRow) ? $oldRow : $pivotLevels;
                                            $selected = array_values(array_intersect($levelChoices, $selected));
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 align-top text-sm font-medium text-gray-900">
                                                <div>{{ $device->name }}</div>
                                                <div class="mt-0.5 text-xs font-normal text-gray-500 font-mono">{{ $device->mac_address }}</div>
                                                @if($selected === [])
                                                    <p class="mt-2 text-xs text-amber-800">Random quiz mode is <strong>off</strong> for this device until you choose at least one level.</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <div class="flex flex-wrap gap-x-4 gap-y-2">
                                                    @foreach($levelChoices as $lvl)
                                                        <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                                            <input
                                                                type="checkbox"
                                                                name="device_random_levels[{{ $device->id }}][]"
                                                                value="{{ $lvl }}"
                                                                @checked(in_array($lvl, $selected, true))
                                                                class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                                            >
                                                            <span>{{ $lvl }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error('device_random_levels.'.$device->id)
                                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">No registered child devices yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                            <button type="submit" class="rounded-md bg-yellow-400 px-4 py-2 text-sm font-semibold text-black hover:bg-yellow-500">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </details>

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
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question bank</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Per attempt</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passing Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Reward</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100/60">
                                    @foreach($quizzes as $quiz)
                                        @php
                                            $subjectStyle = \App\Support\QuizSubjectPalette::forSubject($quiz->subject);
                                            $levelBadge = match ($quiz->level) {
                                                'Kindergarten' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
                                                'Elementary' => ['bg' => '#FDE68A', 'text' => '#78350F'],
                                                'High School' => ['bg' => '#FCD34D', 'text' => '#713F12'],
                                                'Senior High School' => ['bg' => '#FFDE15', 'text' => '#1C1917'],
                                                default => ['bg' => '#F5F5F4', 'text' => '#44403C'],
                                            };
                                        @endphp
                                        <tr style="background-color: {{ $subjectStyle['bg'] }}; color: {{ $subjectStyle['text'] }};">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold">{{ $quiz->title }}</div>
                                                @if($quiz->description)
                                                    <div class="text-sm" style="color: {{ $subjectStyle['muted'] }};">{{ Str::limit($quiz->description, 50) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-black/5" style="background-color: {{ $levelBadge['bg'] }}; color: {{ $levelBadge['text'] }};">
                                                    {{ $quiz->level ?? 'Legacy' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold" style="border-color: {{ $subjectStyle['border'] }}; background-color: rgba(255,255,255,0.45); color: {{ $subjectStyle['text'] }};">
                                                    {{ $quiz->subject ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                {{ $quiz->total_questions_in_pool ?? $quiz->totalQuestionsInPool() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                {{ $quiz->questionsPerChildAttempt() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                {{ $quiz->passing_score }}%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                {{ $quiz->time_reward_minutes }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($quiz->is_active)
                                                    <span class="rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white">Active</span>
                                                @else
                                                    <span class="rounded-full bg-stone-400 px-2 py-1 text-xs font-semibold text-white">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
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

