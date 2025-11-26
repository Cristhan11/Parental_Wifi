<?php

/**
 * UpdateVideoRequest - Form Validation for Video Updates
 * 
 * This class validates form data when a parent edits an existing video.
 * It's almost identical to StoreVideoRequest, but the video file is optional
 * (parent can update other fields without re-uploading the video).
 * 
 * Why separate from StoreVideoRequest? Allows different validation rules
 * for create vs update if needed in the future (e.g., prevent changing
 * certain fields after creation, or allow updating without file).
 * 
 * Key Difference: Video file is optional (only required if uploading new file)
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization is handled by route middleware and controller checks.
     * 
     * @return bool Always true
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Same validation rules as StoreVideoRequest, except:
     * - Video file is optional (only validate if provided)
     * - Duration is optional if video file is not being updated
     * 
     * This allows parents to update video metadata (title, description, etc.)
     * without re-uploading the video file.
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
            
            // Video File: Optional (only required if uploading new file)
            // If parent wants to replace existing video, they upload new file
            // If not provided, existing video file is kept
            'video_file' => [
                'sometimes', // Only validate if field is present
                'file',
                'mimes:mp4,webm,ogg',
                'max:512000', // 512MB in KB
            ],
            
            // Duration: Required if video_file is provided, otherwise optional
            // If uploading new video, duration must be provided
            // If not uploading, existing duration is kept
            'duration_seconds' => [
                'required_with:video_file', // Required if video_file is present
                'nullable', // Otherwise optional
                'integer',
                'min:1',
            ],
            
            // Dictionary Words Enabled: Optional boolean checkbox
            'dictionary_words_enabled' => ['sometimes', 'boolean'],
            
            // Word Count: Required if dictionary_words_enabled is true
            'word_count' => [
                'required_if:dictionary_words_enabled,true',
                'nullable',
                'integer',
                'min:1',
            ],
            
            // Time Reward: Required, must be integer, at least 1 minute
            'time_reward_minutes' => ['required', 'integer', 'min:1'],
            
            // Active Status: Optional boolean checkbox
            'is_active' => ['sometimes', 'boolean'],
            
            // Device Assignment: Optional array of device IDs
            'devices' => ['nullable', 'array'],
            'devices.*' => ['exists:devices,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Video metadata validation messages
            'title.required' => 'Video title is required.',
            'title.max' => 'Video title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            
            // Video file validation messages (optional for updates)
            'video_file.file' => 'The uploaded file must be a valid file.',
            'video_file.mimes' => 'Video must be in MP4, WebM, or OGG format.',
            'video_file.max' => 'Video file size cannot exceed 512MB.',
            
            // Duration validation messages
            'duration_seconds.required_with' => 'Duration is required when uploading a new video file.',
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

