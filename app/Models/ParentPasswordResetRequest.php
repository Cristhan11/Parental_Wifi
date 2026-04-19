<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentPasswordResetRequest extends Model
{
    protected $fillable = [
        'user_id',
        'processed_at',
        'processed_by_actor_id',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedByActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_actor_id');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePending($query)
    {
        return $query->whereNull('processed_at');
    }

    public function isPending(): bool
    {
        return $this->processed_at === null;
    }
}
