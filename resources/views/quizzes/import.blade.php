<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 rounded-lg bg-amber-300/90 px-4 py-3 text-gray-900">
            <a href="{{ route('quizzes.index') }}" class="rounded-md p-1 hover:bg-black/5" aria-label="Back to quizzes">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold sm:text-xl">Import / export quiz</h2>
                <p class="text-xs text-gray-800/80 sm:text-sm">Upload an Excel file to create or update quizzes, or use Export Quiz Questions below</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-collapsible-instructions>
                <p class="mb-2 font-semibold">Instructions</p>
                <ul class="list-inside list-disc space-y-1">
                    <li>Upload one <strong>.xlsx</strong> file, then tap <strong>Import</strong>.</li>
                    <li><strong>Add new</strong> imports rows and can auto-create a quiz from the sheet (Quiz title row, then School level and Subject).</li>
                    <li><strong>Update existing</strong> lets you type a subject/quiz name, pick a quiz, then replace its bank with your sheet rows.</li>
                    <li>In the spreadsheet, a blank <strong>Question text</strong> cell ends the list for that sheet—rows below it are not imported.</li>
                    <li>Use <strong>Download template</strong> for the correct format.</li>
                </ul>
            </x-collapsible-instructions>

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900">Import from Excel</h3>
                <p class="mt-1 text-sm text-gray-600">Choose your file and import mode, then run the import.</p>

                @php
                    $quizPickerItems = collect($updateTargetQuizzes ?? [])->map(function ($quiz) {
                        $subject = trim((string) ($quiz->subject ?? ''));
                        $level = trim((string) ($quiz->level ?? ''));
                        $meta = trim($subject.' '.$level);
                        $label = $quiz->title.($meta !== '' ? ' — '.$meta : '');
                        return [
                            'id' => (string) $quiz->id,
                            'label' => $label,
                            'search' => strtolower($quiz->title.' '.$subject.' '.$level),
                        ];
                    })->values();
                @endphp

                <form
                    action="{{ route('quizzes.import.process') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="mt-6 space-y-5"
                    x-data="{
                        mode: @js(old('mode', 'add_new')),
                        quizSearch: @js(old('quiz_search', '')),
                        selectedQuizId: @js((string) old('quiz_id', '')),
                        quizzes: @js($quizPickerItems),
                        get filteredQuizzes() {
                            const term = this.quizSearch.trim().toLowerCase();
                            if (!term) return this.quizzes.slice(0, 8);
                            return this.quizzes.filter((quiz) => quiz.search.includes(term)).slice(0, 8);
                        },
                        selectQuiz(quiz) {
                            this.selectedQuizId = String(quiz.id);
                            this.quizSearch = quiz.label;
                        }
                    }"
                >
                    @csrf

                    <div>
                        <span class="block text-sm font-medium text-gray-800">Excel file</span>
                        <div class="mt-1.5 rounded-lg focus-within:outline-none focus-within:ring-2 focus-within:ring-amber-500 focus-within:ring-offset-2"
                            x-data="{ fileName: '' }">
                            <div class="flex min-h-[2.75rem] flex-wrap items-center gap-3">
                                <input type="file" name="excel_file" id="excel_file" accept=".xlsx" required
                                    class="sr-only"
                                    @change="fileName = $event.target.files?.length ? $event.target.files[0].name : ''">
                                <label for="excel_file"
                                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg bg-amber-200 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-2 ring-amber-400/80 transition hover:bg-amber-300 hover:ring-amber-500 active:translate-y-px">
                                    Choose file
                                </label>
                                <span class="min-w-0 flex-1 truncate text-sm text-gray-600" x-text="fileName || 'No file chosen'"></span>
                            </div>
                        </div>
                        @error('excel_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-500">.xlsx only, max 5MB</p>
                    </div>

                    <div>
                        <label for="mode" class="block text-sm font-medium text-gray-800">Import mode</label>
                        <select name="mode" id="mode" required
                            x-model="mode"
                            class="mt-1.5 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="add_new" {{ old('mode', 'add_new') === 'add_new' ? 'selected' : '' }}>Add new</option>
                            <option value="update_existing" {{ old('mode') === 'update_existing' ? 'selected' : '' }}>Update existing</option>
                        </select>
                        @error('mode')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="mode === 'update_existing'" x-cloak>
                        <label for="quiz_search" class="block text-sm font-medium text-gray-800">Quiz to update</label>
                        <input type="hidden" name="quiz_id" :value="selectedQuizId">
                        <input
                            type="text"
                            id="quiz_search"
                            name="quiz_search"
                            x-model="quizSearch"
                            @input="selectedQuizId = ''"
                            autocomplete="off"
                            placeholder="Type subject or quiz name (e.g., Math)"
                            class="mt-1.5 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                        <div class="mt-2 max-h-44 overflow-auto rounded-lg border border-gray-200 bg-white" x-show="filteredQuizzes.length > 0">
                            <template x-for="quiz in filteredQuizzes" :key="quiz.id">
                                <button
                                    type="button"
                                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm text-gray-700 hover:bg-amber-50 last:border-b-0"
                                    @click="selectQuiz(quiz)"
                                    x-text="quiz.label"
                                ></button>
                            </template>
                        </div>
                        <p class="mt-1 text-xs text-green-700" x-show="selectedQuizId">Selected quiz is ready to update.</p>
                        @error('quiz_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-500">When you import in Update existing mode, the questions in your sheet will replace this quiz&rsquo;s question bank.</p>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <a href="{{ route('quizzes.template.download') }}"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 sm:justify-start">
                            Download template for quiz
                        </a>
                        <div class="flex justify-end gap-2 sm:justify-end">
                            <a href="{{ route('quizzes.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                            <button type="submit" class="rounded-lg bg-amber-300 px-4 py-2.5 text-sm font-semibold text-gray-900 ring-1 ring-amber-400/50 hover:bg-amber-200">Import</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Collapsible export section (same details/summary pattern as Reporting) --}}
            <details class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-5" @if(old('_import_section') === 'question_bank_export') open @endif>
                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 select-none [&::-webkit-details-marker]:hidden">
                    <span class="font-medium text-gray-700">Export Quiz Questions</span>
                </summary>
                <div class="mt-5 space-y-4 border-t border-gray-100 pt-5">
                    <h3 class="text-base font-semibold text-gray-900">Export to Excel</h3>
                    <p class="text-xs leading-relaxed text-gray-500">
                        Optional: download your quiz questions for backup or offline editing. Same layout and columns as <strong>Download template for quiz</strong> above (including Question Type).
                    </p>
                    <x-question-bank-export-form
                        :exportQuizzesPayload="$exportQuizzesPayload"
                        :exportQuizLevels="$exportQuizLevels"
                        :initialExportLevel="$initialExportLevel"
                        :initialExportQuizIds="$initialExportQuizIds"
                    />
                </div>
            </details>
        </div>
    </div>
</x-app-layout>
