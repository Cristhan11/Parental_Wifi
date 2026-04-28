<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quiz Attempt Model
 *
 * Tracks each time a child attempts a quiz.
 * Stores answers, calculates score, and records if they passed.
 */
class QuizAttempt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'quiz_id',
        'answers',
        'score',
        'correct_count',
        'total_questions',
        'passed',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'passed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the device that attempted this quiz.
     *
     * Relationship: belongsTo - One attempt belongs to one device
     *
     * Usage Example:
     * $attempt = QuizAttempt::find(1);
     * $device = $attempt->device; // Gets the Device that took the quiz
     * echo $device->name; // "John's iPhone"
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the quiz that was attempted.
     *
     * Relationship: belongsTo - One attempt belongs to one quiz
     *
     * Usage Example:
     * $attempt = QuizAttempt::find(1);
     * $quiz = $attempt->quiz; // Gets the Quiz that was attempted
     * echo $quiz->title; // "Math Quiz - Addition"
     * echo "Score: {$attempt->score}%";
     * echo "Passed: " . ($attempt->passed ? 'Yes' : 'No');
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
