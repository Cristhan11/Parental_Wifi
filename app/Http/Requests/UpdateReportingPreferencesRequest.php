<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest = centralized validation (and optional authorization) for a specific HTTP action.
 *
 * Laravel automatically injects this into the controller method when type-hinted:
 * `public function updatePreferences(UpdateReportingPreferencesRequest $request)`.
 *
 * Checkbox gotcha:
 * - When an HTML checkbox is unchecked, the browser does NOT send that field at all.
 * - Validation rules use `nullable` so "missing" is OK; the controller then uses `$request->boolean('key')`
 *   which treats missing as false. See ReportsController::updatePreferences.
 */
class UpdateReportingPreferencesRequest extends FormRequest
{
    /**
     * Return false to abort with 403. We use route middleware for roles instead, so true is fine here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules use Laravel’s rule objects / strings.
     * - `nullable` — field may be missing or null.
     * - `boolean` — accepts 1/0, true/false, "on", etc.
     * - `required` — must be present (timezone is always sent from our form as a select).
     * - `timezone` — must be a valid IANA timezone string PHP recognizes.
     *
     * @return array<string, mixed>
     */
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
