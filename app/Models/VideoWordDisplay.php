<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Video Word Display Model
 * 
 * Tracks which dictionary words were displayed during a specific
 * video viewing session. Used for word validation at the end.
 */
class VideoWordDisplay extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'video_completion_id',
        'dictionary_word_id',
        'displayed_at_timestamp',
        'word_text',
    ];

    /**
     * Get the video completion this word display belongs to.
     * 
     * Relationship: belongsTo - One word display belongs to one video completion
     * 
     * Usage Example:
     * $wordDisplay = VideoWordDisplay::find(1);
     * $completion = $wordDisplay->videoCompletion; // Gets the VideoCompletion
     * echo "Word '{$wordDisplay->word_text}' was shown during video completion #{$completion->id}";
     */
    public function videoCompletion(): BelongsTo
    {
        return $this->belongsTo(VideoCompletion::class);
    }

    /**
     * Get the dictionary word that was displayed.
     * 
     * Relationship: belongsTo - One word display belongs to one dictionary word
     * 
     * Usage Example:
     * $wordDisplay = VideoWordDisplay::find(1);
     * $dictionaryWord = $wordDisplay->dictionaryWord; // Gets the DictionaryWord
     * echo "Word: {$dictionaryWord->word}";
     * echo "Definition: {$dictionaryWord->definition}";
     * echo "Shown at: {$wordDisplay->displayed_at_timestamp} seconds";
     */
    public function dictionaryWord(): BelongsTo
    {
        return $this->belongsTo(DictionaryWord::class);
    }
}

