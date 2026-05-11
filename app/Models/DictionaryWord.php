<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dictionary Word Model
 * 
 * Stores educational dictionary words with definitions.
 * Used during video playback for educational purposes.
 */
class DictionaryWord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'word',
        'definition',
        'is_built_in',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_built_in' => 'boolean',
        ];
    }

    /**
     * Get the parent user who added this word (if custom word).
     * 
     * Relationship: belongsTo - One word can belong to one user (if parent-added, not built-in)
     * Note: Built-in words (from seeder) have user_id = null
     * 
     * Usage Example:
     * $word = DictionaryWord::find(1);
     * if ($word->is_built_in) {
     *     echo "This is a built-in system word";
     * } else {
     *     $parent = $word->user; // Gets the User who added this custom word
     *     echo "Added by: {$parent->name}";
     * }
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all video word displays that used this word.
     * 
     * Relationship: hasMany - One word can be displayed many times in different videos
     * 
     * Usage Example:
     * $word = DictionaryWord::find(1);
     * $displays = $word->videoWordDisplays; // All times this word was shown
     * echo "Word '{$word->word}' has been shown " . $displays->count() . " times";
     */
    public function videoWordDisplays(): HasMany
    {
        return $this->hasMany(VideoWordDisplay::class);
    }
}

