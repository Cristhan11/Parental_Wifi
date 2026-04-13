<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Device Model
 * 
 * Represents a child device that parents want to monitor and control.
 * Each device is identified by its MAC address and belongs to a parent user.
 * 
 * Key Features:
 * - Time tracking: remaining_time_minutes, total_time_allocated
 * - Status management: active, blocked, whitelisted
 * - Relationships with all monitoring and control features
 */
class Device extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'mac_address',
        'status',
        'role',
        'ip_address',
        'remaining_time_minutes',
        'total_time_allocated',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the parent user that owns this device.
     * 
     * Relationship: belongsTo - One device belongs to one user (parent)
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $parent = $device->user; // Gets the User model
     * echo $parent->name; // "John Doe"
     * echo $parent->email; // "john@example.com"
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all time grants for this device.
     * 
     * Relationship: hasMany - One device has many time grants
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $grants = $device->timeGrants; // Collection of DeviceTimeGrant models
     * foreach ($grants as $grant) {
     *     echo $grant->minutes_granted; // 15, 30, etc.
     *     echo $grant->source; // "quiz" or "video"
     * }
     */
    public function timeGrants(): HasMany
    {
        return $this->hasMany(DeviceTimeGrant::class);
    }

    /**
     * Get all quiz attempts for this device.
     * 
     * Relationship: hasMany - One device can have many quiz attempts
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $attempts = $device->quizAttempts; // All quiz attempts
     * $passedAttempts = $device->quizAttempts()->where('passed', true)->get();
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get all video completions for this device.
     * 
     * Relationship: hasMany - One device can complete many videos
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $completions = $device->videoCompletions; // All video completions
     * $successful = $device->videoCompletions()->where('passed_validation', true)->get();
     */
    public function videoCompletions(): HasMany
    {
        return $this->hasMany(VideoCompletion::class);
    }

    /**
     * Get all schedules for this device.
     * 
     * Relationship: hasMany - One device can have many time schedules
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $schedules = $device->schedules; // All time-based access rules
     * $activeSchedules = $device->schedules()->where('is_active', true)->get();
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(DeviceSchedule::class);
    }

    /**
     * Get all browsing logs for this device.
     * 
     * Relationship: hasMany - One device has many browsing history records
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $logs = $device->browsingLogs; // All visited websites
     * $todayLogs = $device->browsingLogs()->whereDate('visited_at', today())->get();
     */
    public function browsingLogs(): HasMany
    {
        return $this->hasMany(BrowsingLog::class);
    }

    /**
     * Get all access attempts for this device.
     * 
     * Relationship: hasMany - One device has many security events
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $attempts = $device->accessAttempts; // All blocked/flagged site attempts
     * $blockedAttempts = $device->accessAttempts()->where('type', 'blocked_website')->get();
     */
    public function accessAttempts(): HasMany
    {
        return $this->hasMany(AccessAttempt::class);
    }

    /**
     * Get all active sessions for this device.
     * 
     * Relationship: hasMany - One device can have many internet sessions
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $sessions = $device->sessions; // All sessions (active and ended)
     * $activeSessions = $device->sessions()->whereNull('ended_at')->get();
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class);
    }

    /**
     * Get all quizzes assigned to this device.
     * 
     * Relationship: belongsToMany - Many-to-many (device can have many quizzes, quiz can be assigned to many devices)
     * Uses pivot table: 'device_quiz'
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $quizzes = $device->quizzes; // All assigned quizzes
     * 
     * // Assign a quiz to device
     * $device->quizzes()->attach(5); // Assigns quiz ID 5
     * 
     * // Remove a quiz assignment
     * $device->quizzes()->detach(5);
     * 
     * // Sync quizzes (removes all and adds new ones)
     * $device->quizzes()->sync([1, 2, 3]); // Only these 3 quizzes assigned
     */
    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'device_quiz')
            ->withTimestamps();
    }

    /**
     * Get all videos assigned to this device.
     * 
     * Relationship: belongsToMany - Many-to-many (device can have many videos, video can be assigned to many devices)
     * Uses pivot table: 'device_video'
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $videos = $device->videos; // All assigned videos
     * 
     * // Assign a video to device
     * $device->videos()->attach(3); // Assigns video ID 3
     * 
     * // Get only active videos
     * $activeVideos = $device->videos()->where('is_active', true)->get();
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'device_video')
            ->withTimestamps();
    }

    /**
     * Get the current active session for this device.
     * 
     * Returns the most recent session that hasn't ended yet (ended_at is NULL)
     * Returns NULL if no active session exists
     * 
     * @return DeviceSession|null The active session or null if none exists
     * 
     * Usage Example:
     * $device = Device::find(1);
     * $session = $device->activeSession();
     * if ($session) {
     *     echo "Session started: " . $session->started_at;
     *     echo "Duration: " . $session->getDurationMinutes() . " minutes";
     * } else {
     *     echo "No active session";
     * }
     */
    public function activeSession(): ?DeviceSession
    {
        return $this->sessions()
            ->whereNull('ended_at')  // Only sessions that haven't ended
            ->latest('started_at')   // Order by newest first
            ->first();               // Get the first (most recent) one
    }

    /**
     * Check if device has remaining internet time.
     * 
     * Returns true if remaining_time_minutes > 0, false otherwise
     *
     * @return bool True if time remaining, false if expired
     * 
     * Usage Example:
     * $device = Device::find(1);
     * if ($device->hasRemainingTime()) {
     *     echo "Device can still browse";
     *     echo "Time left: " . $device->getRemainingTimeFormatted();
     * } else {
     *     echo "Time expired! Redirect to portal";
     * }
     */
    public function hasRemainingTime(): bool
    {
        return $this->remaining_time_minutes > 0;
    }

    /**
     * Check if device's time has expired.
     * 
     * Returns true if remaining_time_minutes <= 0, false otherwise
     * This is the opposite of hasRemainingTime()
     *
     * @return bool True if time expired, false if still has time
     * 
     * Usage Example:
     * $device = Device::find(1);
     * if ($device->hasTimeExpired()) {
     *     // Block device and redirect to captive portal
     *     redirect()->route('portal.landing', ['mac' => $device->mac_address]);
     * }
     */
    public function hasTimeExpired(): bool
    {
        return $this->remaining_time_minutes <= 0;
    }

    /**
     * Get remaining time in a human-readable format.
     * 
     * Converts minutes to "X hours Y minutes" or "X minutes" format
     * Handles pluralization automatically (hour vs hours, minute vs minutes)
     *
     * @return string Formatted time string (e.g., "1 hour 30 minutes" or "45 minutes")
     * 
     * Usage Example:
     * $device = Device::find(1);
     * echo $device->getRemainingTimeFormatted(); 
     * // Output: "1 hour 30 minutes" (if 90 minutes remaining)
     * // Output: "45 minutes" (if 45 minutes remaining)
     * // Output: "1 minute" (if 1 minute remaining)
     */
    public function getRemainingTimeFormatted(): string
    {
        // Calculate hours and minutes
        $hours = floor($this->remaining_time_minutes / 60);  // floor() rounds down: 90/60 = 1
        $minutes = $this->remaining_time_minutes % 60;        // % gets remainder: 90%60 = 30

        // If there are hours, show both hours and minutes
        if ($hours > 0) {
            // sprintf formats string: %d = number, %s = string
            // Ternary operator: condition ? value_if_true : value_if_false
            return sprintf('%d hour%s %d minute%s', 
                $hours, 
                $hours > 1 ? 's' : '',      // Add 's' if plural
                $minutes, 
                $minutes !== 1 ? 's' : ''   // Add 's' if plural
            );
        }

        // If no hours, show only minutes
        return sprintf('%d minute%s', $minutes, $minutes !== 1 ? 's' : '');
    }

    /**
     * Grant additional time to this device.
     * 
     * Adds time to device after successful quiz completion or video completion.
     * Also creates a DeviceTimeGrant record to track when and why time was granted.
     *
     * @param int $minutes Amount of time to grant (e.g., 15, 30)
     * @param string $source Source of grant: 'quiz' or 'video'
     * @param int|null $sourceId Optional: ID of quiz_attempt or video_completion that triggered this grant
     * @return DeviceTimeGrant The created time grant record
     * 
     * Usage Example:
     * $device = Device::find(1);
     * 
     * // Grant 15 minutes after quiz completion
     * $quizAttempt = QuizAttempt::find(10);
     * $grant = $device->grantTime(15, 'quiz', $quizAttempt->id);
     * // Device's remaining_time_minutes increased by 15
     * // DeviceTimeGrant record created with source='quiz', source_id=10
     * 
     * // Grant 30 minutes after video completion
     * $videoCompletion = VideoCompletion::find(5);
     * $device->grantTime(30, 'video', $videoCompletion->id);
     */
    public function grantTime(int $minutes, string $source, ?int $sourceId = null): DeviceTimeGrant
    {
        // increment() adds to existing value: if current is 10, increment(5) makes it 15
        $this->increment('remaining_time_minutes', $minutes);  // Add to remaining time
        $this->increment('total_time_allocated', $minutes);   // Track total time ever allocated

        // Create a record in device_time_grants table to track this grant
        // timeGrants() is the relationship method, create() saves new record
        $timeGrant = $this->timeGrants()->create([
            'minutes_granted' => $minutes,      // How much time was granted
            'source' => $source,                // 'quiz' or 'video'
            'source_id' => $sourceId,           // ID of quiz_attempt or video_completion
            'granted_at' => now(),              // Current timestamp
        ]);

        return $timeGrant;  // Return the created record
    }

    /**
     * Deduct time from this device (used when device is actively browsing).
     * 
     * Removes time from remaining_time_minutes as the device uses internet.
     * Prevents negative final values by clamping to 0.
     *
     * @param int $minutes Amount of time to deduct
     * @return void No return value
     * 
     * Usage Example:
     * $device = Device::find(1);
     * // Device has 30 minutes remaining
     * 
     * // Deduct 5 minutes after 5 minutes of browsing
     * $device->deductTime(5);
     * // Now device has 25 minutes remaining
     * 
     * // If device has 3 minutes and we try to deduct 5, it becomes 0 (not -2)
     * $device->remaining_time_minutes = 3;
     * $device->deductTime(5);  // Result: 0 (max(0, 5) prevents negative)
     */
    public function deductTime(int $minutes): void
    {
        // Guard against negative input so we never "add" time by accident.
        $minutesToDeduct = max(0, $minutes);

        // Clamp final value to 0 to prevent negative remaining time.
        // Previous implementation used decrement(), which could produce negatives
        // when minutesToDeduct was greater than current remaining_time_minutes.
        $newRemaining = max(0, ($this->remaining_time_minutes ?? 0) - $minutesToDeduct);

        $this->update(['remaining_time_minutes' => $newRemaining]);
    }

    /**
     * Check if device is currently blocked.
     * 
     * Returns true if device status is 'blocked' (no internet access allowed)
     *
     * @return bool True if blocked, false otherwise
     * 
     * Usage Example:
     * $device = Device::find(1);
     * if ($device->isBlocked()) {
     *     echo "Device is blocked from internet access";
     *     // Block device at network level using iptables
     * }
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';  // === checks both type and value
    }

    /**
     * Check if device is whitelisted (unrestricted access).
     * 
     * Returns true if device status is 'whitelisted' (bypasses all time limits and restrictions)
     *
     * @return bool True if whitelisted, false otherwise
     * 
     * Usage Example:
     * $device = Device::find(1);
     * if ($device->isWhitelisted()) {
     *     echo "Device has unrestricted access";
     *     // Skip time checks, allow all websites
     * }
     */
    public function isWhitelisted(): bool
    {
        return $this->status === 'whitelisted';
    }

    /**
     * Check if device is active (can access internet if time available).
     * 
     * Returns true if device status is 'active' (normal operation, subject to time limits)
     *
     * @return bool True if active, false otherwise
     * 
     * Usage Example:
     * $device = Device::find(1);
     * if ($device->isActive() && $device->hasRemainingTime()) {
     *     echo "Device can browse the internet";
     *     // Allow internet access
     * }
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

