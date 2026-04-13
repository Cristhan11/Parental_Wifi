<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Video Model
 * 
 * Represents an educational video that children can watch
 * to earn additional internet time.
 */
class Video extends Model
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
        'video_path',
        'duration_seconds',
        'dictionary_words_enabled',
        'word_count',
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
            'dictionary_words_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent user who added this video.
     * 
     * Relationship: belongsTo - One video belongs to one user (parent)
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $parent = $video->user; // Gets the User who added this video
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all video completions for this video.
     * 
     * Relationship: hasMany - One video can have many completions (by different devices)
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $completions = $video->completions; // All times this video was completed
     * $successfulCompletions = $video->completions()->where('passed_validation', true)->count();
     */
    public function completions(): HasMany
    {
        return $this->hasMany(VideoCompletion::class);
    }

    /**
     * Get all devices assigned to this video.
     * 
     * Relationship: belongsToMany - Many-to-many (video can be assigned to many devices)
     * Uses pivot table: 'device_video'
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $devices = $video->devices; // All devices that can watch this video
     * 
     * // Assign video to a device
     * $video->devices()->attach(3); // Assigns to device ID 3
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_video')
            ->withTimestamps();
    }

    /**
     * Get the full path to the video file on the server.
     * 
     * Returns the absolute file system path (for server-side operations)
     *
     * @return string Full file path (e.g., "/var/www/storage/app/videos/educational_video_1.mp4")
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $video->video_path = "videos/educational_video_1.mp4";
     * $fullPath = $video->getFullPath();
     * // Returns: "/var/www/storage/app/videos/educational_video_1.mp4"
     * 
     * // Check if file exists
     * if (file_exists($video->getFullPath())) {
     *     echo "Video file exists";
     * }
     */
    public function getFullPath(): string
    {
        // Files are stored on the public disk: storage/app/public/...
        return storage_path('app/public/' . $this->video_path);
    }

    /**
     * MIME type for the HTML video type attribute (matches StoreVideoRequest mimes).
     */
    public function getMimeType(): string
    {
        $ext = strtolower(pathinfo($this->video_path, PATHINFO_EXTENSION));

        return match ($ext) {
            'webm' => 'video/webm',
            'ogg', 'ogv' => 'video/ogg',
            default => 'video/mp4',
        };
    }

    /**
     * Get the video URL for streaming in the browser.
     * 
     * Returns a URL that can be used in HTML <video> tags or for streaming
     *
     * @return string Public URL (e.g., "http://example.com/storage/videos/educational_video_1.mp4")
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $url = $video->getVideoUrl();
     * // Returns: "http://example.com/storage/videos/educational_video_1.mp4"
     * 
     * // Use in Blade template
     * // <video src="{{ $video->getVideoUrl() }}" controls></video>
     */
    public function getVideoUrl(): string
    {
        // Root-relative URL so playback works when APP_URL does not match the client host (e.g. captive gateway).
        return '/storage/' . ltrim($this->video_path, '/');
    }

    /**
     * Get duration in a human-readable format.
     * 
     * Converts seconds to "H:MM:SS" or "M:SS" format
     *
     * @return string Formatted duration (e.g., "1:30:45" or "5:30")
     * 
     * Usage Example:
     * $video = Video::find(1);
     * $video->duration_seconds = 3665; // 1 hour, 1 minute, 5 seconds
     * echo $video->getDurationFormatted(); // Output: "1:01:05"
     * 
     * $video->duration_seconds = 330; // 5 minutes, 30 seconds
     * echo $video->getDurationFormatted(); // Output: "5:30"
     */
    public function getDurationFormatted(): string
    {
        // Calculate hours, minutes, seconds
        $hours = floor($this->duration_seconds / 3600);              // 3665 / 3600 = 1
        $minutes = floor(($this->duration_seconds % 3600) / 60);     // (3665 % 3600) / 60 = 1
        $seconds = $this->duration_seconds % 60;                     // 3665 % 60 = 5

        // If there are hours, show H:MM:SS format
        if ($hours > 0) {
            // %02d means: format as integer with 2 digits, pad with 0 if needed
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        // If no hours, show M:SS format
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}

