<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'immediate_alerts_enabled',
        'daily_digest_enabled',
        'weekly_digest_enabled',
        'monthly_digest_enabled',
        'timezone',
        'skip_empty_digests',
    ];

    protected function casts(): array
    {
        return [
            'immediate_alerts_enabled' => 'boolean',
            'daily_digest_enabled' => 'boolean',
            'weekly_digest_enabled' => 'boolean',
            'monthly_digest_enabled' => 'boolean',
            'skip_empty_digests' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

