<?php

namespace App\Http\Requests;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Device Schedule Request
 * 
 * This form request handles validation for creating new device schedules.
 * Schedules define time-based internet access rules for devices.
 * 
 * Validation Rules:
 * - device_id: Required, must exist in devices table, user must own the device
 * - day_of_week: Required, must be one of the valid days
 * - start_time: Required, must be valid time format
 * - end_time: Required, must be valid time format, must be after start_time
 * - duration_limit_minutes: Optional, integer between 1 and 1440 (24 hours)
 * - is_active: Optional, boolean, defaults to true
 */
class StoreDeviceScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool True if user can create schedules, false otherwise
     */
    public function authorize(): bool
    {
        // All authenticated users (parents) can create schedules
        // Device ownership is validated in rules() method
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
                        $fail('You can only create schedules for your own devices.');
                    }
                },
            ],

            // Day of week - required, must be one of the valid days
            'day_of_week' => [
                'required',
                'string',
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ],

            // Start time - required, must be valid time format
            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            // End time - required, must be valid time format, must be after start_time
            'end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    if ($startTime && $value) {
                        // Parse times and compare
                        $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                        $end = \Carbon\Carbon::createFromFormat('H:i', $value);
                        
                        // Check if end time is after start time
                        if ($end->lte($start)) {
                            $fail('End time must be after start time.');
                        }
                    }
                },
            ],

            // Duration limit - optional, integer between 1 and 1440 (24 hours)
            'duration_limit_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],

            // Is active - optional, boolean, defaults to true
            'is_active' => [
                'nullable',
                'boolean',
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

            'day_of_week.required' => 'Please select a day of the week.',
            'day_of_week.in' => 'Please select a valid day of the week.',

            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in valid time format (HH:MM).',

            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time must be in valid time format (HH:MM).',
            'end_time.after' => 'End time must be after start time.',

            'duration_limit_minutes.integer' => 'Duration limit must be a number.',
            'duration_limit_minutes.min' => 'Duration limit must be at least 1 minute.',
            'duration_limit_minutes.max' => 'Duration limit cannot exceed 1440 minutes (24 hours).',

            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }

    /**
     * Prepare the data for validation.
     * 
     * Sets default values before validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default is_active to true if not provided
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}

