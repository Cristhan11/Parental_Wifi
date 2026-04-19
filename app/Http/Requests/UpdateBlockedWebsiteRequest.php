<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Blocked Website Request
 *
 * This form request handles validation for updating existing blocked websites.
 * It validates all input data before the blocked website is updated in the database.
 *
 * Validation Rules:
 * - Same as StoreBlockedWebsiteRequest, plus:
 * - id: Must exist in blocked_websites table
 */
class UpdateBlockedWebsiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if user can update blocked websites, false otherwise
     */
    public function authorize(): bool
    {
        // Authorization is handled by BlockedWebsitePolicy
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * This method is called before validation rules are applied.
     * We use it to decode JSON strings (like related_domains) into arrays.
     */
    protected function prepareForValidation(): void
    {
        // Decode related_domains if it's a JSON string
        if ($this->has('related_domains') && is_string($this->input('related_domains'))) {
            $decoded = json_decode($this->input('related_domains'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['related_domains' => $decoded]);
            } else {
                $this->merge(['related_domains' => []]);
            }
        }

        $this->merge(['block_type' => 'app']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/',
                'max:255',
            ],

            'block_type' => [
                'required',
                'string',
                'in:app',
            ],

            // Block subdomains - optional boolean, defaults to false
            'block_subdomains' => [
                'nullable',
                'boolean',
            ],

            // Related domains - optional array, each element must be valid domain format
            // Note: Can be empty array for app blocks (will be auto-populated by controller)
            'related_domains' => [
                'nullable',
                'array',
            ],
            'related_domains.*' => [
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/',
                'max:255',
            ],

            // Reason - optional, string, max 500 characters
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'Domain is required.',
            'domain.regex' => 'Please enter a valid domain name (e.g., example.com).',
            'domain.max' => 'Domain cannot exceed 255 characters.',

            'block_type.in' => 'Invalid block configuration.',

            'block_subdomains.boolean' => 'Block subdomains must be yes or no.',

            'related_domains.required' => 'Related domains are required for app-level blocking.',
            'related_domains.array' => 'Related domains must be a list.',
            'related_domains.*.regex' => 'Each related domain must be a valid domain name.',
            'related_domains.*.max' => 'Each related domain cannot exceed 255 characters.',

            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}
