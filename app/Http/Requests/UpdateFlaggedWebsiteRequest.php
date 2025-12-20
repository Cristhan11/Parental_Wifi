<?php

namespace App\Http\Requests;

use App\Models\Device;
use App\Models\FlaggedWebsite;
use App\Services\DomainBlockingService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Flagged Website Request
 * 
 * This form request handles validation for updating existing flagged websites.
 * 
 * Validation Rules:
 * - Same as StoreFlaggedWebsiteRequest
 */
class UpdateFlaggedWebsiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool True if user can update flagged websites, false otherwise
     */
    public function authorize(): bool
    {
        // Authorization is handled by FlaggedWebsitePolicy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Device ID - required, must exist in devices table, user must own device
            'device_id' => [
                'required',
                'integer',
                'exists:devices,id',
                function ($attribute, $value, $fail) {
                    // Check if user owns the device
                    $device = Device::find($value);
                    $user = $this->user();
                    if ($device && $user && $device->user_id !== $user->id) {
                        $fail('You can only flag websites for your own devices.');
                    }
                },
            ],

            // URL - required, must be valid URL format
            'url' => [
                'required',
                'string',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    // Check for unique domain constraint (excluding current record)
                    try {
                        $domainBlockingService = app(DomainBlockingService::class);
                        $domain = $domainBlockingService->normalizeDomain($value);
                        
                        $deviceId = $this->input('device_id');
                        $flaggedWebsiteId = $this->route('flaggedWebsite')->id ?? null;
                        
                        if ($deviceId) {
                            $exists = FlaggedWebsite::where('device_id', $deviceId)
                                ->where('domain', $domain)
                                ->when($flaggedWebsiteId, function ($query) use ($flaggedWebsiteId) {
                                    return $query->where('id', '!=', $flaggedWebsiteId);
                                })
                                ->exists();
                            
                            if ($exists) {
                                $fail('This domain is already flagged for this device.');
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignore errors during validation (e.g., invalid URL format)
                        // The 'url' rule will catch invalid URLs
                    }
                },
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

            'url.required' => 'URL is required.',
            'url.url' => 'Please enter a valid URL (e.g., https://example.com).',
            'url.max' => 'URL cannot exceed 500 characters.',

            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}

