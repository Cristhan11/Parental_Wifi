<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Flagged Website Model
 * 
 * Stores websites parents monitor for all child devices (household-wide list per user).
 */
class FlaggedWebsite extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'domain',
        'reason',
    ];

    /**
     * Parent account that owns this flag rule (applies to all of their child devices).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

