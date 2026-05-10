@props([
    'exportQuizzesPayload' => [],
    'exportQuizLevels' => [],
    'initialExportLevel' => 'Elementary',
    'initialExportQuizIds' => [],
])

<form method="get" action="{{ route('quizzes.question-bank.export') }}" class="space-y-4"
    x-data="exportBankForm(@js($exportQuizzesPayload), @js($initialExportLevel), @js($initialExportQuizIds))"
    @submit="showErrors = true; if (selectedQuizIds.length === 0) { $event.preventDefault(); }">
    <input type="hidden" name="_import_section" value="question_bank_export">

    <p class="text-sm text-gray-700">Pick a school level, tick one or more quizzes, then export. Each distinct subject (Math, English, Science) from your selection becomes its own Excel tab.</p>

    @if(count($exportQuizzesPayload) === 0)
        <p class="text-sm text-gray-600">You need at least one standard quiz (with a school level). Random quiz mode is not exported here.</p>
    @else
        <div>
            <label for="export_quiz_level_import" class="mb-1 block text-sm font-semibold text-gray-800">Quiz level</label>
            <select id="export_quiz_level_import" name="export_level" required x-model="selectedLevel"
                class="w-full max-w-md rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-yellow-500 focus:ring-yellow-400">
                @foreach($exportQuizLevels as $lvl)
                    <option value="{{ $lvl }}">{{ $lvl }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <span class="mb-1 block text-sm font-semibold text-gray-800">Quizzes at this level</span>
            <span class="mb-2 block text-xs text-gray-500">Scroll if needed. Select all quizzes to include.</span>
            <div class="max-h-60 min-h-[8rem] overflow-y-auto rounded-md bg-gray-50/90 p-2 ring-1 ring-gray-200/90">
                <template x-if="filteredQuizzes().length === 0">
                    <p class="px-2 py-6 text-center text-sm text-gray-500">No quizzes for this level.</p>
                </template>
                <ul class="space-y-2" x-show="filteredQuizzes().length > 0">
                    <template x-for="q in filteredQuizzes()" :key="q.id">
                        <li>
                            <label class="flex cursor-pointer items-start gap-2 rounded-md bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-200/80 transition hover:ring-gray-300 has-[:checked]:ring-yellow-400 has-[:checked]:ring-2">
                                <input type="checkbox" :value="q.id" x-model="selectedQuizIds"
                                    class="mt-0.5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-400">
                                <span class="min-w-0 flex-1">
                                    <span class="font-medium text-gray-900" x-text="q.title"></span>
                                    <span class="mt-0.5 block text-xs text-gray-600" x-show="q.subject">
                                        <span x-text="q.subject"></span>
                                        <span x-show="q.level"> · <span x-text="q.level"></span></span>
                                    </span>
                                </span>
                            </label>
                        </li>
                    </template>
                </ul>
            </div>
            <p x-show="showErrors && selectedQuizIds.length === 0" x-cloak class="mt-2 text-sm text-red-600">Select at least one quiz.</p>
        </div>

        <template x-for="id in selectedQuizIds" :key="'qb-hid-'+id">
            <input type="hidden" name="quiz_ids[]" :value="id">
        </template>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit"
                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                Export quiz questions (.xlsx)
            </button>
            <span class="text-xs text-gray-500">Legacy <code class="rounded bg-gray-100 px-1 font-mono text-gray-800">Questions</code> imports still supported.</span>
        </div>
    @endif
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('exportBankForm', (quizzes, initialLevel, initialQuizIds) => ({
                    quizzes: Array.isArray(quizzes) ? quizzes : [],
                    selectedLevel: initialLevel || 'Elementary',
                    selectedQuizIds: Array.isArray(initialQuizIds)
                        ? initialQuizIds.map((id) => Number(id)).filter((id) => id > 0)
                        : [],
                    showErrors: false,
                    init() {
                        this.$watch('selectedLevel', () => this.pruneSelections());
                        this.pruneSelections();
                    },
                    filteredQuizzes() {
                        return this.quizzes.filter((q) => q.level === this.selectedLevel);
                    },
                    pruneSelections() {
                        const allowed = new Set(this.filteredQuizzes().map((q) => q.id));
                        this.selectedQuizIds = this.selectedQuizIds.filter((id) => allowed.has(id));
                    },
                }));
            });
        </script>
    @endpush
@endonce
