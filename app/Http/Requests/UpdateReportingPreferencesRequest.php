<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportingPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'immediate_alerts_enabled' => ['nullable', 'boolean'],
            'daily_digest_enabled' => ['nullable', 'boolean'],
            'weekly_digest_enabled' => ['nullable', 'boolean'],
            'monthly_digest_enabled' => ['nullable', 'boolean'],
            'skip_empty_digests' => ['nullable', 'boolean'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}

