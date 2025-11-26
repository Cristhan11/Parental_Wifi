<?php

/**
 * StoreVideoRequest - Form Validation for Video Creation
 * 
 * This class validates the form data when a parent creates a new educational video.
 * Laravel automatically calls these validation rules before the controller
 * method runs, ensuring only valid data reaches the database.
 * 
 * How it works:
 * 1. Parent submits video creation form (with file upload)
 * 2. Laravel intercepts the request
 * 3. This class validates all fields including video file
 * 4. If valid: Request continues to VideoController@store
 * 5. If invalid: Returns to form with error messages
 * 
 * Why separate validation? Keeps controller code clean and ensures
 * data integrity. Validation rules are reusable and testable.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization (who can access) is handled by the 'auth' middleware
     * on the route, so we return true here. This method is for additional
     * permission checks if needed (e.g., "only admins can create videos").
     * 
     * @return bool Always true (authorization handled by route middleware)
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * These rules ensure the form data is valid before saving to database.
     * 
     * Validation Rules Explained:
     * - 'required' = Field must be filled in
     * - 'string' = Must be text (not number, array, etc.)
     * - 'max:255' = Maximum 255 characters
     * - 'integer' = Must be a whole number
     * - 'min:1' = Minimum value is 1
     * - 'boolean' = Must be true/false (checkbox)
     * - 'mimes:mp4,webm,ogg' = File must be one of these video formats
     * - 'max:512000' = Maximum file size in KB (512MB = 512000 KB)
     * - 'array' = Must be an array (for device IDs)
     * - 'exists:devices,id' = Device ID must exist in devices table
     * 
     * Special Rules:
     * - 'word_count' is required only if dictionary_words_enabled is true
     * - Video file is required for creation (not optional)
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Video Title: Required, must be text, max 255 characters
            'title' => ['required', 'string', 'max:255'],
            
            // Description: Optional (nullable), if provided must be text, max 1000 characters
            'description' => ['nullable', 'string', 'max:1000'],
            
            // Video File: Required, must be video file (mp4, webm, ogg), max 512MB
            // 512MB = 512000 KB (Raspberry Pi storage consideration)
            // mimes validation checks file extension and MIME type
            'video_file' => [
                'required',
                'file',
                'mimes:mp4,webm,ogg',
                'max:512000', // 512MB in KB
            ],
            
            // Duration: Required, must be integer (seconds), at least 1 second
            // Duration is typically extracted from video file or entered manually
            'duration_seconds' => ['required', 'integer', 'min:1'],
            
            // Dictionary Words Enabled: Optional boolean checkbox
            // If checked, dictionary words will be displayed during video playback
            'dictionary_words_enabled' => ['sometimes', 'boolean'],
            
            // Word Count: Required if dictionary_words_enabled is true
            // Must be integer, at least 1 word
            // This determines how many random words to display during video
            'word_count' => [
                'required_if:dictionary_words_enabled,true',
                'nullable',
                'integer',
                'min:1',
            ],
            
            // Time Reward: Required, must be integer, at least 1 minute
            // Minutes granted to child after successfully completing video
            'time_reward_minutes' => ['required', 'integer', 'min:1'],
            
            // Active Status: Optional boolean checkbox
            // If unchecked, video won't appear in portal (disabled)
            'is_active' => ['sometimes', 'boolean'],
            
            // Device Assignment: Optional array of device IDs
            // Allows parent to assign video to specific devices
            // Each device ID must exist in devices table
            'devices' => ['nullable', 'array'],
            'devices.*' => ['exists:devices,id'], // Each device ID must exist
        ];
    }

    /**
     * Get custom messages for validator errors.
     * 
     * When validation fails, Laravel shows these custom error messages
     * instead of generic ones. This makes errors more user-friendly.
     * 
     * Format: 'field.rule' => 'Custom error message'
     * Example: 'title.required' => 'Video title is required.'
     * 
     * If validation fails, parent sees these messages on the form,
     * helping them understand what needs to be fixed.
     * 
     * @return array<string, string> Array of field.rule => error message
     */
    public function messages(): array
    {
        return [
            // Video metadata validation messages
            'title.required' => 'Video title is required.',
            'title.max' => 'Video title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            
            // Video file validation messages
            'video_file.required' => 'Video file is required.',
            'video_file.file' => 'The uploaded file must be a valid file.',
            'video_file.mimes' => 'Video must be in MP4, WebM, or OGG format.',
            'video_file.max' => 'Video file size cannot exceed 512MB.',
            
            // Duration validation messages
            'duration_seconds.required' => 'Video duration is required.',
            'duration_seconds.integer' => 'Duration must be a whole number (seconds).',
            'duration_seconds.min' => 'Duration must be at least 1 second.',
            
            // Dictionary words validation messages
            'word_count.required_if' => 'Word count is required when dictionary words are enabled.',
            'word_count.integer' => 'Word count must be a whole number.',
            'word_count.min' => 'Word count must be at least 1.',
            
            // Time reward validation messages
            'time_reward_minutes.required' => 'Time reward is required.',
            'time_reward_minutes.integer' => 'Time reward must be a whole number (minutes).',
            'time_reward_minutes.min' => 'Time reward must be at least 1 minute.',
            
            // Device assignment validation messages
            'devices.array' => 'Device assignment must be an array.',
            'devices.*.exists' => 'One or more selected devices do not exist.',
        ];
    }
}

