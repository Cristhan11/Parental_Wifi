<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per reporting-recipient action for the unified Logs UI (Parent/Admin Changes).
 *
 * Populated by {@see \App\Observers\ReportingRecipientObserver}; not derived from
 * {@see ReportingRecipient} timestamps so deletes remain visible.
 */
class ReportingRecipientEvent extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'action',
        'summary',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
