<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for PUT/PATCH when editing an existing recipient.
 *
 * Route model binding injects `ReportingRecipient $recipient` — here we read it from the route
 * so we can `ignore($recipientId)` on the unique rule (otherwise you could not save without changing email).
 */
class UpdateReportingRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // In tests or malformed routes, `recipient` might not be an object — guard before accessing ->id.
        $recipient = $this->route('recipient');
        $recipientId = is_object($recipient) ? $recipient->id : null;

        return [
            'label' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('reporting_recipients', 'email')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id))
                    ->ignore($recipientId),
            ],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
