<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDispatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'frequency',
        'recipient_email',
        'subject',
        'period_start',
        'period_end',
        'status',
        'meta',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

