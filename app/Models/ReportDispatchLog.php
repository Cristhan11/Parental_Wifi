<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail for every email attempt (immediate or digest): sent, failed, or skipped.
 *
 * Why: Parents can verify delivery in the app without reading server logs; supports debugging SMTP failures.
 *
 * Interconnects:
 * - Written by {@see \App\Jobs\DispatchDigestReportJob} and immediate-alert listeners after each Mail::send attempt.
 * - Dummy/preview commands intentionally do NOT write here (see {@see \App\Console\Commands\SendDummyDigestPreview}).
 * - Listed on Reports page via {@see \App\Http\Controllers\ReportsController::index}.
 */
class ReportDispatchLog extends Model
{
    use HasFactory;

    /**
     * @var list<string> Columns jobs/listeners may mass-assign when calling `ReportDispatchLog::create([...])`.
     */
    protected $fillable = [
        'user_id',
        'report_type',      // e.g. 'digest' or immediate subtype string used by listeners
        'frequency',        // daily|weekly|monthly for digests; nullable for immediate
        'recipient_email',  // who the SMTP attempt targeted (may be null for some skipped rows)
        'subject',
        'period_start',     // digest window start (UTC in DB)
        'period_end',
        'status',           // sent | failed | skipped
        'meta',             // JSON blob: counts, etc.
        'error_message',    // exception message when status=failed
        'sent_at',
    ];

    /**
     * Casts ensure Carbon instances for dates and native array for `meta` JSON column.
     */
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

