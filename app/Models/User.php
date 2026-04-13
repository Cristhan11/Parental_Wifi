<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all devices owned by this user (parent).
     * 
     * Relationship: hasMany - One user (parent) can have many devices
     * 
     * Usage Example:
     * $user = User::find(1);
     * $devices = $user->devices; // Collection of all devices owned by this parent
     * foreach ($devices as $device) {
     *     echo $device->name; // "John's iPhone", "Sarah's Laptop", etc.
     * }
     * 
     * // Count devices
     * $deviceCount = $user->devices()->count();
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Blocked websites for this parent (apply to all child devices).
     */
    public function blockedWebsites(): HasMany
    {
        return $this->hasMany(BlockedWebsite::class);
    }

    /**
     * Flagged websites for this parent (monitoring applies to all child devices).
     */
    public function flaggedWebsites(): HasMany
    {
        return $this->hasMany(FlaggedWebsite::class);
    }

    /**
     * Get all quizzes created by this user (parent).
     * 
     * Relationship: hasMany - One user can create many quizzes
     * 
     * Usage Example:
     * $user = User::find(1);
     * $quizzes = $user->quizzes; // All quizzes created by this parent
     * $activeQuizzes = $user->quizzes()->where('is_active', true)->get();
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get all videos added by this user (parent).
     * 
     * Relationship: hasMany - One user can add many videos
     * 
     * Usage Example:
     * $user = User::find(1);
     * $videos = $user->videos; // All videos added by this parent
     * $activeVideos = $user->videos()->where('is_active', true)->get();
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get all dictionary words added by this user (parent).
     * 
     * Relationship: hasMany - One user can add many custom dictionary words
     * Note: Built-in words (from seeder) have user_id = null
     * 
     * Usage Example:
     * $user = User::find(1);
     * $customWords = $user->dictionaryWords; // Only words added by this parent
     * // Built-in words are not included (they have user_id = null)
     */
    public function dictionaryWords(): HasMany
    {
        return $this->hasMany(DictionaryWord::class);
    }

    /**
     * Reporting preferences for this account.
     *
     * Why hasOne:
     * - one account owns one reporting preference profile controlling digest cadence
     *   and immediate alert behavior.
     */
    public function reportingPreference(): HasOne
    {
        return $this->hasOne(ReportingPreference::class);
    }

    /**
     * Email recipients that should receive this account's reports.
     *
     * Why separate table:
     * - one parent can notify multiple recipients without duplicating account records.
     */
    public function reportingRecipients(): HasMany
    {
        return $this->hasMany(ReportingRecipient::class);
    }

    /**
     * Audit rows for recipient add / edit / remove (shown on Logs → Parent/Admin Changes).
     *
     * @see \App\Models\ReportingRecipientEvent
     */
    public function reportingRecipientEvents(): HasMany
    {
        return $this->hasMany(ReportingRecipientEvent::class);
    }

    /**
     * Dispatch history for reports and alerts generated for this account ({@see ReportDispatchLog}).
     *
     * Populated by {@see \App\Jobs\DispatchDigestReportJob} and immediate listeners; shown read-only on Reports page.
     */
    public function reportDispatchLogs(): HasMany
    {
        return $this->hasMany(ReportDispatchLog::class);
    }

    /**
     * Check if user is a parent.
     * 
     * Returns true if user's role is 'parent'
     *
     * @return bool True if parent, false otherwise
     * 
     * Usage Example:
     * $user = User::find(1);
     * if ($user->isParent()) {
     *     echo "This user is a parent";
     *     // Show parent dashboard
     *     return view('dashboard.parent');
     * }
     */
    public function isParent(): bool
    {
        return $this->role === 'parent';  // === checks both type and value
    }

    /**
     * Check if user is an admin.
     * 
     * Returns true if user's role is 'admin'
     *
     * @return bool True if admin, false otherwise
     * 
     * Usage Example:
     * $user = User::find(1);
     * if ($user->isAdmin()) {
     *     echo "This user is an admin";
     *     // Show admin dashboard with all features
     *     return view('dashboard.admin');
     * }
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
