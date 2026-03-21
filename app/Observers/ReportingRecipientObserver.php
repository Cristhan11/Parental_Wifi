<?php

namespace App\Observers;

use App\Models\ReportingRecipient;
use App\Models\ReportingRecipientEvent;

/**
 * Writes human-readable rows to {@see ReportingRecipientEvent} for the Logs → Parent/Admin stream.
 */
class ReportingRecipientObserver
{
    public function created(ReportingRecipient $recipient): void
    {
        $label = $recipient->label ? " ({$recipient->label})" : '';

        ReportingRecipientEvent::create([
            'user_id' => $recipient->user_id,
            'email' => $recipient->email,
            'action' => 'added',
            'summary' => "Reporting recipient added{$label}: {$recipient->email}",
        ]);
    }

    public function updated(ReportingRecipient $recipient): void
    {
        if (! $recipient->wasChanged(['email', 'label', 'is_enabled'])) {
            return;
        }

        $parts = [];
        if ($recipient->wasChanged('email')) {
            $parts[] = sprintf(
                'email changed from %s to %s',
                (string) $recipient->getOriginal('email'),
                $recipient->email
            );
        }
        if ($recipient->wasChanged('label')) {
            $old = $recipient->getOriginal('label');
            $new = $recipient->label;
            $parts[] = sprintf(
                'label changed from %s to %s',
                $old !== null && $old !== '' ? (string) $old : '(none)',
                $new !== null && $new !== '' ? (string) $new : '(none)'
            );
        }
        if ($recipient->wasChanged('is_enabled')) {
            $parts[] = $recipient->is_enabled
                ? 'recipient enabled for notifications'
                : 'recipient disabled for notifications';
        }

        $label = $recipient->label ? " ({$recipient->label})" : '';

        ReportingRecipientEvent::create([
            'user_id' => $recipient->user_id,
            'email' => $recipient->email,
            'action' => 'updated',
            'summary' => 'Reporting recipient updated'.$label.': '.implode('; ', $parts),
        ]);
    }

    public function deleted(ReportingRecipient $recipient): void
    {
        $label = $recipient->label ? " ({$recipient->label})" : '';

        ReportingRecipientEvent::create([
            'user_id' => $recipient->user_id,
            'email' => $recipient->email,
            'action' => 'removed',
            'summary' => "Reporting recipient removed{$label}: {$recipient->email}",
        ]);
    }
}
