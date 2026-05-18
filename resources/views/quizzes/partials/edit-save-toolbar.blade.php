{{-- Reusable save actions for quiz edit (in-form + floating). --}}
@php
    $variant = $variant ?? 'inline';
@endphp

@if($variant === 'floating')
    <div
        id="quizEditFloatingActions"
        class="quiz-edit-save-toolbar"
        role="region"
        aria-label="Save quiz"
        style="position: fixed !important; bottom: 1.25rem !important; right: 1rem !important; z-index: 99999 !important; display: flex !important; flex-wrap: wrap; gap: 0.75rem; max-width: calc(100vw - 2rem); padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background-color: #ffffff; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);"
    >
        <a href="{{ route('quizzes.index') }}" style="display: inline-block; padding: 0.5rem 1rem; border-radius: 0.375rem; border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none; white-space: nowrap;">
            Cancel
        </a>
        <button
            type="button"
            class="quiz-edit-submit-trigger"
            style="padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; background-color: #FFDE15; color: #000000; font-weight: 600; cursor: pointer; white-space: nowrap;"
        >
            Update Quiz
        </button>
    </div>
@elseif($variant === 'compact')
    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-yellow-400 bg-yellow-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-800">Save your changes here — you do not need to scroll to the bottom of the page.</p>
        <div class="flex shrink-0 justify-end gap-2">
            <a href="{{ route('quizzes.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="rounded-md px-4 py-2 text-sm font-semibold hover:opacity-90" style="background-color: #FFDE15; color: #000000;">
                Update Quiz
            </button>
        </div>
    </div>
@else
    <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
        <a href="{{ route('quizzes.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" class="rounded-md px-4 py-2 font-medium hover:opacity-90" style="background-color: #FFDE15; color: #000000;">
            Update Quiz
        </button>
    </div>
@endif
