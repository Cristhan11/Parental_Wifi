<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for POST “add recipient” on the Reports page.
 *
 * `Rule::unique(...)` adds a database uniqueness check:
 * - Same email may exist for a *different* parent — the `where('user_id', ...)` scopes uniqueness per account.
 * - The closure receives the query builder `$query` so we can chain extra WHERE clauses.
 */
class StoreReportingRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional display name in the UI.
            'label' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',   // Must look like a valid email address format.
                'max:255',
                Rule::unique('reporting_recipients', 'email')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            // Checkbox: may be absent; boolean rule accepts truthy/falsy strings from forms.
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
