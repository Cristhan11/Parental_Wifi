<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Each row = one email address that should receive copies of this parent’s reporting emails.
 *
 * Why not store emails on the User model?
 * - A household may want digests to go to both parents’ inboxes without creating two login accounts.
 * - `is_enabled` lets someone temporarily pause one address without deleting the row.
 *
 * Table: `reporting_recipients` — unique (`user_id`, `email`) so the same address cannot be added twice per parent.
 */
class ReportingRecipient extends Model
{
    use HasFactory;

    /** Columns allowed for mass assignment from controllers / forms. */
    protected $fillable = [
        'user_id',
        'label',      // optional human nickname, e.g. "Mom"
        'email',
        'is_enabled', // if false, skipped by digest job and immediate listeners
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    /** Many recipients belong to one parent User. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Query scope: `ReportingRecipient::enabled()` or `$user->reportingRecipients()->enabled()`.
     * Scopes are reusable query fragments — they return the builder for chaining.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
