<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Video Completion Model
 * 
 * Tracks when a child completes watching a video, including
 * dictionary word validation results. This model represents a single
 * viewing session where a child watches a video and attempts to
 * remember the dictionary words that appeared.
 * 
 * Key Concepts:
 * - One video can have many completions (different children, different attempts)
 * - One completion belongs to one device (which child watched)
 * - One completion belongs to one video (which video was watched)
 * - One completion has many word displays (which words appeared)
 * 
 * Lifecycle:
 * 1. Completion record created when child starts watching video
 * 2. Word displays created as words are shown during playback
 * 3. Completion updated when child submits words
 * 4. Validation result stored (passed/failed)
 * 5. Time granted if validation passed
 * 
 * Retry Logic:
 * - If child fails, they can retry the video
 * - New completion record created with incremented attempt_number
 * - New random words selected for new attempt
 * - Previous attempts preserved for history
 */
class VideoCompletion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'video_id',
        'completed_at',
        'watched_duration',
        'words_shown_count',
        'words_entered',
        'words_correct',
        'passed_validation',
        'attempt_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'passed_validation' => 'boolean',
        ];
    }

    /**
     * Get the device that completed this video.
     * 
     * Relationship: belongsTo - One completion belongs to one device
     * 
     * Usage Example:
     * $completion = VideoCompletion::find(1);
     * $device = $completion->device; // Gets the Device that watched the video
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the video that was completed.
     * 
     * Relationship: belongsTo - One completion belongs to one video
     * 
     * Usage Example:
     * $completion = VideoCompletion::find(1);
     * $video = $completion->video; // Gets the Video that was watched
     * echo $video->title; // "Educational Video 1"
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get all dictionary words that were displayed during this video viewing.
     * 
     * Relationship: hasMany - One completion has many word displays
     * 
     * Usage Example:
     * $completion = VideoCompletion::find(1);
     * $words = $completion->wordDisplays; // All words shown during this viewing
     * foreach ($words as $wordDisplay) {
     *     echo $wordDisplay->word_text . " at " . $wordDisplay->displayed_at_timestamp . " seconds";
     * }
     */
    public function wordDisplays(): HasMany
    {
        return $this->hasMany(VideoWordDisplay::class);
    }

    /**
     * Get the words that were shown as an array.
     * 
     * Returns an array of word strings in the order they were displayed
     *
     * @return array Array of word strings (e.g., ["adventure", "curious", "discover"])
     * 
     * Usage Example:
     * $completion = VideoCompletion::find(1);
     * $wordsShown = $completion->getWordsShown();
     * // Returns: ["adventure", "curious", "discover"]
     * 
     * // Compare with words entered by child
     * $wordsEntered = $completion->getWordsEnteredArray();
     * $correct = array_intersect($wordsShown, $wordsEntered);
     * echo "Correct words: " . count($correct) . " out of " . count($wordsShown);
     */
    public function getWordsShown(): array
    {
        // orderBy() sorts by timestamp (order words appeared)
        // pluck() extracts only the 'word_text' column
        // toArray() converts collection to array
        return $this->wordDisplays()
            ->orderBy('displayed_at_timestamp')  // Sort by when word appeared
            ->pluck('word_text')                  // Get only word_text column
            ->toArray();                          // Convert to array
    }

    /**
     * Get the words entered by the child as an array.
     * 
     * Converts words_entered (stored as JSON or comma-separated string) to array
     *
     * @return array Array of word strings entered by child
     * 
     * Usage Example:
     * $completion = VideoCompletion::find(1);
     * $completion->words_entered = '["adventure", "curious", "discover"]'; // JSON format
     * $words = $completion->getWordsEnteredArray();
     * // Returns: ["adventure", "curious", "discover"]
     * 
     * // Or comma-separated format
     * $completion->words_entered = "adventure, curious, discover";
     * $words = $completion->getWordsEnteredArray();
     * // Returns: ["adventure", "curious", "discover"]
     */
    public function getWordsEnteredArray(): array
    {
        // If empty, return empty array
        if (empty($this->words_entered)) {
            return [];
        }

        // Try to decode as JSON first (preferred format)
        $decoded = json_decode($this->words_entered, true);  // true = return as array
        if (json_last_error() === JSON_ERROR_NONE) {        // Check if JSON decode succeeded
            return $decoded;
        }

        // If not JSON, treat as comma-separated string
        // explode() splits string by comma into array
        // array_map('trim', ...) removes whitespace from each element
        return array_map('trim', explode(',', $this->words_entered));
    }
}
