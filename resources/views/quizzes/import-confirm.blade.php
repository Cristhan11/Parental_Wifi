<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 rounded-lg bg-amber-300/90 px-4 py-3 text-gray-900">
            <a href="{{ route('quizzes.import') }}" class="rounded-md p-1 hover:bg-black/5" aria-label="Back to import">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold sm:text-xl">Quiz title already exists</h2>
                <p class="text-xs text-gray-800/80 sm:text-sm">Choose whether to replace that quiz&rsquo;s questions</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-lg px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-amber-200 bg-amber-50/90 p-6 shadow-sm">
                <p class="text-sm text-gray-800">
                    You already have a quiz titled <strong>{{ $duplicateTitle }}</strong>.
                    You can <strong>replace</strong> its questions with the rows in your spreadsheet, or <strong>cancel</strong> and change the Quiz title in Excel (the row labeled Quiz title) so this file creates a separate quiz.
                </p>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <form method="POST" action="{{ route('quizzes.import.pending.process', ['token' => $token]) }}">
                        @csrf
                        <input type="hidden" name="choice" value="cancel">
                        <button type="submit"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 sm:w-auto">
                            Cancel import
                        </button>
                    </form>
                    <form method="POST" action="{{ route('quizzes.import.pending.process', ['token' => $token]) }}">
                        @csrf
                        <input type="hidden" name="choice" value="replace">
                        <button type="submit"
                            class="w-full rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-semibold text-gray-900 ring-1 ring-amber-500/40 hover:bg-amber-300 sm:w-auto">
                            Replace questions for &ldquo;{{ $duplicateTitle }}&rdquo;
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
