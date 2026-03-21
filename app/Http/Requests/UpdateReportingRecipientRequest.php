<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportingRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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

