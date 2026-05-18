@php
    $totalPool = max(0, (int) ($totalQuestionsInPool ?? 0));
    $perAttempt = (int) old('question_count', isset($quiz) ? ($quiz->question_count ?? 15) : 15);
    $maxPerAttempt = max(1, $totalPool > 0 ? $totalPool : 500);
    if ($perAttempt > $maxPerAttempt) {
        $perAttempt = $maxPerAttempt;
    }
@endphp
<div>
    <label for="question_count" class="mb-2 block text-sm font-medium text-gray-700">Questions per child attempt *</label>
    <input
        type="number"
        name="question_count"
        id="question_count"
        value="{{ $perAttempt }}"
        min="1"
        max="{{ $maxPerAttempt }}"
        required
        class="required-field w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:outline-none focus:ring-2"
        data-bank-total="{{ $totalPool }}"
    >
    <p class="mt-1 text-sm text-gray-500">
        Each attempt randomly shows this many questions from
        <strong id="questionPoolTotal">{{ $totalPool > 0 ? $totalPool : 'your question list' }}</strong>
        in the bank. Default is 15. Children never see the full bank in one attempt.
    </p>
    @error('question_count')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
