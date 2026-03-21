<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportingRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('reporting_recipients', 'email')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}

