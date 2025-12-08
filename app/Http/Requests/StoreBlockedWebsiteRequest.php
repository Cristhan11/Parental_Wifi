<?php

namespace App\Http\Requests;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Blocked Website Request
 * 
 * This form request handles validation for creating new blocked websites.
 * It validates all input data before the blocked website is created in the database.
 * 
 * What is a Form Request?
 * - A form request is Laravel's way of validating form data
 * - It runs automatically before the controller method is called
 * - If validation fails, user is redirected back with error messages
 * - If validation passes, controller method receives validated data
 * 
 * Validation Rules:
 * - device_id: Required, must exist in devices table, user must own the device
 * - url: Required if block_type is 'url', must be valid URL format
 * - domain: Required if block_type is 'domain' or 'app', must be valid domain format
 * - block_type: Required, must be one of: 'url', 'domain', 'app'
 * - block_subdomains: Optional boolean, defaults to false
 * - related_domains: Optional array, each element must be valid domain format (if block_type is 'app')
 * - reason: Optional, string, max 500 characters
 */
class StoreBlockedWebsiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * This method checks if the user has permission to create blocked websites.
     * In our system, all authenticated parents can create blocked websites for their own devices.
     * 
     * @return bool True if user can create blocked websites, false otherwise
     */
    public function authorize(): bool
    {
        // All authenticated users (parents) can create blocked websites
        // Device ownership is validated in rules() method
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
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * This method returns an array of validation rules for each field.
     * Laravel will validate all fields against these rules before allowing
     * the controller method to be called.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $blockType = $this->input('block_type', 'domain');
        
        return [
            // Device ID - required, must exist in devices table, user must own device
            'device_id' => [
                'required',
                'integer',
                'exists:devices,id',
                function ($attribute, $value, $fail) {
                    // Check if user owns the device
                    $device = Device::find($value);
                    if ($device && $device->user_id !== $this->user()->id) {
                        $fail('You can only block websites for your own devices.');
                    }
                },
            ],

            // Domain - required if block_type is 'domain' or 'app', must be valid domain format
            'domain' => [
                Rule::requiredIf(in_array($blockType, ['domain', 'app'])),
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/',
                'max:255',
            ],

            // Block type - required, must be one of: 'domain', 'app'
            'block_type' => [
                'required',
                'string',
                'in:domain,app',
            ],

            // Block subdomains - optional boolean, defaults to false
            'block_subdomains' => [
                'nullable',
                'boolean',
            ],

            // Related domains - optional array, each element must be valid domain format
            // Only used if block_type is 'app'
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
            'device_id.required' => 'Please select a device.',
            'device_id.exists' => 'The selected device does not exist.',
            'device_id.integer' => 'Device ID must be a valid number.',

            'domain.required' => 'Domain is required.',
            'domain.regex' => 'Please enter a valid domain name (e.g., example.com).',
            'domain.max' => 'Domain cannot exceed 255 characters.',

            'block_type.required' => 'Please select a blocking type.',
            'block_type.in' => 'Blocking type must be Domain or App.',

            'block_subdomains.boolean' => 'Block subdomains must be yes or no.',

            'related_domains.required' => 'Related domains are required for app-level blocking.',
            'related_domains.array' => 'Related domains must be a list.',
            'related_domains.*.regex' => 'Each related domain must be a valid domain name.',
            'related_domains.*.max' => 'Each related domain cannot exceed 255 characters.',

            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}

