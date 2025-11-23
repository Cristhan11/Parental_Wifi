<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz Model
 * 
 * Represents a quiz that parents create for their children.
 * Children must pass quizzes to earn additional internet time.
 */
class Quiz extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'questions',
        'passing_score',
        'time_reward_minutes',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent user who created this quiz.
     * 
     * Relationship: belongsTo - One quiz belongs to one user (parent)
     * 
     * Usage Example:
     * $quiz = Quiz::find(1);
     * $parent = $quiz->user; // Gets the User who created this quiz
     * echo $parent->name; // "John Doe"
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all quiz attempts for this quiz.
     * 
     * Relationship: hasMany - One quiz can have many attempts
     * 
     * Usage Example:
     * $quiz = Quiz::find(1);
     * $attempts = $quiz->attempts; // All attempts on this quiz
     * $passedAttempts = $quiz->attempts()->where('passed', true)->count();
     * echo "Pass rate: " . ($passedAttempts / $quiz->attempts()->count() * 100) . "%";
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get all devices assigned to this quiz.
     * 
     * Relationship: belongsToMany - Many-to-many (quiz can be assigned to many devices)
     * Uses pivot table: 'device_quiz'
     * 
     * Usage Example:
     * $quiz = Quiz::find(1);
     * $devices = $quiz->devices; // All devices that can take this quiz
     * 
     * // Assign quiz to a device
     * $quiz->devices()->attach(5); // Assigns to device ID 5
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_quiz')
            ->withTimestamps();
    }

    /**
     * Check if a score passes this quiz.
     * 
     * Compares the given score against the quiz's passing_score (percentage)
     *
     * @param int $score The score to check (0-100 percentage)
     * @return bool True if score >= passing_score, false otherwise
     * 
     * Usage Example:
     * $quiz = Quiz::find(1);
     * $quiz->passing_score = 70; // Requires 70% to pass
     * 
     * $studentScore = 85;
     * if ($quiz->isPassingScore($studentScore)) {
     *     echo "Student passed!";
     *     // Grant time to device
     *     $device->grantTime($quiz->time_reward_minutes, 'quiz', $attemptId);
     * } else {
     *     echo "Student failed. Score: $studentScore%, Required: {$quiz->passing_score}%";
     * }
     */
    public function isPassingScore(int $score): bool
    {
        return $score >= $this->passing_score;  // >= means "greater than or equal to"
    }
}

