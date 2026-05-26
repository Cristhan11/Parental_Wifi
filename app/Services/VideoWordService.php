<?php

/**
 * VideoWordService - Dictionary Word Management for Videos
 *
 * This service handles all dictionary word-related operations for video playback.
 * It provides methods for selecting random words, generating random timestamps,
 * and validating words entered by children.
 *
 * How it works:
 * - When a child starts watching a video, random words are selected from the dictionary
 * - Random timestamps are generated throughout the video duration
 * - Words are displayed at those timestamps during playback
 * - At video end, child enters the words they saw
 * - This service validates the entered words against the displayed words
 *
 * Why a separate service? Separates business logic from controllers, making code
 * more organized, testable, and reusable.
 */

namespace App\Services;

use App\Models\DictionaryWord;
use Illuminate\Support\Collection;

class VideoWordService
{
    /**
     * Select random dictionary words for a video.
     *
     * This method randomly selects words from the dictionary word pool.
     * Words are selected from all available words (built-in and custom).
     *
     * How it works:
     * 1. Gets all available dictionary words from database
     * 2. Randomly shuffles them
     * 3. Takes the first N words (where N = wordCount)
     * 4. Returns collection of selected words
     *
     * Why random? Ensures children see different words each time they watch,
     * preventing memorization and encouraging active learning.
     *
     * @param  int  $wordCount  Number of words to select (e.g., 5 words for a 10-minute video)
     * @return Collection Collection of DictionaryWord models
     *
     * Usage Example:
     * ```php
     * $service = new VideoWordService();
     * $words = $service->selectRandomWords(5);
     * // Returns: Collection of 5 random DictionaryWord models
     * ```
     */
    public function selectRandomWords(int $wordCount): Collection
    {
        // Clamp to at least 1 so we never silently return an empty collection
        // when a caller accidentally passes 0 or a negative number.
        $wordCount = max(1, $wordCount);

        // Get a random sample from the dictionary pool.
        // ->inRandomOrder() randomizes the order (shuffles results)
        // ->take($wordCount) limits to the requested number of words
        $picked = DictionaryWord::inRandomOrder()
            ->take($wordCount)
            ->get();

        // Defensive top-up: if the dictionary pool is smaller than $wordCount
        // (small seed, fresh install, or rows were removed), keep adding random
        // re-picks until we have exactly $wordCount items. This prevents the
        // "I entered 3 but only 2 words appear and are required" off-by-one.
        if ($picked->count() < $wordCount && $picked->isNotEmpty()) {
            $needed = $wordCount - $picked->count();
            for ($i = 0; $i < $needed; $i++) {
                $picked->push($picked->random());
            }
        }

        return $picked;
    }

    /**
     * Random dictionary words for the post-video chip game, excluding words already shown.
     *
     * @param  array<int>  $excludeDictionaryWordIds  IDs of words already used in this session
     * @return Collection<int, DictionaryWord>
     */
    public function selectDistractorWords(int $count, array $excludeDictionaryWordIds): Collection
    {
        if ($count <= 0) {
            return collect();
        }

        $exclude = array_values(array_unique(array_filter(array_map('intval', $excludeDictionaryWordIds))));

        $query = DictionaryWord::query();
        if ($exclude !== []) {
            $query->whereNotIn('id', $exclude);
        }

        return $query->inRandomOrder()->take($count)->get();
    }

    /**
     * Generate random timestamps for word display throughout video duration.
     *
     * This method generates random timestamps (in seconds) when words should
     * appear during video playback. Timestamps are distributed throughout the
     * video duration to ensure active watching.
     *
     * How it works:
     * 1. Calculates time intervals (divides video duration by word count)
     * 2. For each word, generates a random timestamp within its interval
     * 3. Ensures timestamps are spread throughout the video
     * 4. Returns array of timestamps in ascending order
     *
     * Example:
     * - Video duration: 600 seconds (10 minutes)
     * - Word count: 5 words
     * - Interval: 600 / 5 = 120 seconds per interval
     * - Word 1: Random between 0-120 seconds (e.g., 45 seconds)
     * - Word 2: Random between 120-240 seconds (e.g., 180 seconds)
     * - Word 3: Random between 240-360 seconds (e.g., 290 seconds)
     * - etc.
     *
     * Why distributed intervals? Prevents all words from appearing at the start
     * or end, ensuring children watch the entire video.
     *
     * @param  int  $durationSeconds  Total video duration in seconds
     * @param  int  $wordCount  Number of words to display
     * @return array Array of timestamps in seconds (e.g., [45, 180, 290, 420, 550])
     *
     * Usage Example:
     * ```php
     * $service = new VideoWordService();
     * $timestamps = $service->generateRandomTimestamps(600, 5);
     * // Returns: [45, 180, 290, 420, 550] (random timestamps in seconds)
     * ```
     */
    public function generateRandomTimestamps(int $durationSeconds, int $wordCount): array
    {
        // If no words or invalid duration, return empty array
        if ($wordCount <= 0 || $durationSeconds <= 0) {
            return [];
        }

        // Maximum legal integer timestamp for the JS handleTimeUpdate check
        // (currentTime is floor()ed, so values must fit strictly below durationSeconds).
        $maxTimestamp = max(0, $durationSeconds - 1);

        // If the video is shorter than the requested word count, we cannot give
        // each word its own distinct second. Fall back to evenly spaced ints
        // clamped to the legal range (some may end up equal on very short clips).
        if ($durationSeconds <= $wordCount) {
            $timestamps = [];
            for ($i = 0; $i < $wordCount; $i++) {
                $timestamps[] = min($i, $maxTimestamp);
            }

            return $timestamps;
        }

        // Divide the playable range into N equal float intervals and pick one
        // integer second inside each. We then walk through the list and bump any
        // collision (timestamp <= previous) to previous+1 so every word gets its
        // own second. This guarantees handleTimeUpdate fires once per word.
        $interval = $durationSeconds / $wordCount;
        $timestamps = [];

        for ($i = 0; $i < $wordCount; $i++) {
            $intervalStart = (int) floor($i * $interval);
            $intervalEnd = (int) ceil(($i + 1) * $interval) - 1;

            if ($intervalEnd < $intervalStart) {
                $intervalEnd = $intervalStart;
            }
            if ($intervalEnd > $maxTimestamp) {
                $intervalEnd = $maxTimestamp;
            }
            if ($intervalStart > $maxTimestamp) {
                $intervalStart = $maxTimestamp;
            }

            $timestamps[] = random_int($intervalStart, $intervalEnd);
        }

        sort($timestamps);

        // Enforce strictly-increasing, at-least-1s-apart timestamps so two words
        // never collide on the same second (which would visually look like a
        // single overlay and consume the same `timeupdate` tick on the client).
        for ($i = 1; $i < $wordCount; $i++) {
            if ($timestamps[$i] <= $timestamps[$i - 1]) {
                $timestamps[$i] = $timestamps[$i - 1] + 1;
            }
        }

        // If pushing the last timestamps forward overflowed the video length,
        // pack them back from the end so every word stays inside [0, max].
        if ($timestamps[$wordCount - 1] > $maxTimestamp) {
            $timestamps[$wordCount - 1] = $maxTimestamp;
            for ($i = $wordCount - 2; $i >= 0; $i--) {
                if ($timestamps[$i] >= $timestamps[$i + 1]) {
                    $timestamps[$i] = max(0, $timestamps[$i + 1] - 1);
                }
            }
        }

        return $timestamps;
    }

    /**
     * Validate words entered by child against words that were displayed.
     *
     * This method compares the words the child entered with the words that
     * were actually displayed during video playback. Validation is case-insensitive
     * and trims whitespace to be forgiving of minor input differences.
     *
     * How it works:
     * 1. Normalizes displayed words (lowercase, trimmed)
     * 2. Normalizes entered words (lowercase, trimmed)
     * 3. Compares position-by-position (same order as words appeared in the video)
     * 4. Returns validation result with counts
     *
     * Normalization:
     * - Converts to lowercase: "Adventure" = "adventure" = "ADVENTURE"
     * - Trims whitespace: " adventure " = "adventure"
     * - This makes validation forgiving of minor input differences
     *
     * Validation Result:
     * - words_shown_count: Total number of words displayed
     * - words_entered_count: Total number of words child entered
     * - words_correct: Number of words child got correct
     * - passed_validation: true if ALL words are correct, false otherwise
     *
     * @param  array  $wordsShown  Array of word strings that were displayed (e.g., ["adventure", "curious"])
     * @param  array  $wordsEntered  Array of word strings in the order the child selected them
     * @return array Validation result with counts and pass/fail status
     *
     * Usage Example:
     * ```php
     * $service = new VideoWordService();
     * $result = $service->validateWords(
     *     ["adventure", "curious", "discover"],
     *     ["adventure", "curious", "discover"]
     * );
     * // Order must match playback order; same length required to pass.
     * ```
     */
    public function validateWords(array $wordsShown, array $wordsEntered): array
    {
        // Normalize displayed words: lowercase and trim whitespace
        // array_map() applies function to each element in array
        // strtolower() converts to lowercase
        // trim() removes leading/trailing whitespace
        $normalizedShown = array_map(function ($word) {
            return strtolower(trim($word));
        }, $wordsShown);

        // Normalize entered words: lowercase and trim whitespace
        // Same normalization process for consistency
        $normalizedEntered = array_map(function ($word) {
            return strtolower(trim($word));
        }, $wordsEntered);

        $wordsShownCount = count($normalizedShown);
        $wordsEnteredCount = count($normalizedEntered);

        $wordsCorrect = 0;
        $length = min($wordsShownCount, $wordsEnteredCount);
        for ($i = 0; $i < $length; $i++) {
            if ($normalizedShown[$i] === $normalizedEntered[$i]) {
                $wordsCorrect++;
            }
        }

        $sameLength = $wordsEnteredCount === $wordsShownCount;
        $allPositionsMatch = $sameLength && $wordsCorrect === $wordsShownCount;
        $passedValidation = $allPositionsMatch && ($wordsShownCount > 0);

        // Return validation result
        return [
            'words_shown_count' => $wordsShownCount,
            'words_entered_count' => $wordsEnteredCount,
            'words_correct' => $wordsCorrect,
            'passed_validation' => $passedValidation,
        ];
    }

    /**
     * Get the correct words that were displayed (for showing in error messages).
     *
     * This method returns the words that were actually displayed, formatted
     * for display to the child when validation fails. Used in error messages
     * to show what the correct words were.
     *
     * @param  array  $wordsShown  Array of word strings that were displayed
     * @return string Comma-separated string of words (e.g., "adventure, curious, discover")
     *
     * Usage Example:
     * ```php
     * $service = new VideoWordService();
     * $correctWords = $service->getCorrectWordsString(["adventure", "curious", "discover"]);
     * // Returns: "adventure, curious, discover"
     * ```
     */
    public function getCorrectWordsString(array $wordsShown): string
    {
        // Join words with comma and space for readable display
        // implode() combines array elements into a string
        return implode(', ', $wordsShown);
    }
}
