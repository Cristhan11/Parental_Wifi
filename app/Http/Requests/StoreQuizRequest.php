<?php

/**
 * StoreQuizRequest - Form Validation for Quiz Creation
 *
 * This class validates the form data when a parent creates a new quiz.
 * Laravel automatically calls these validation rules before the controller
 * method runs, ensuring only valid data reaches the database.
 *
 * How it works:
 * 1. Parent submits quiz creation form
 * 2. Laravel intercepts the request
 * 3. This class validates all fields
 * 4. If valid: Request continues to QuizController@store
 * 5. If invalid: Returns to form with error messages
 *
 * Why separate validation? Keeps controller code clean and ensures
 * data integrity. Validation rules are reusable and testable.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization (who can access) is handled by the 'auth' middleware
     * on the route, so we return true here. This method is for additional
     * permission checks if needed (e.g., "only admins can create quizzes").
     *
     * @return bool Always true (authorization handled by route middleware)
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    protected function prepareForValidation(): void
    {
        $maxPasses = $this->input('max_passes_per_day');
        $maxPasses = ($maxPasses === '' || $maxPasses === null) ? null : $maxPasses;

        $retry = $this->input('retry_cooldown_minutes');
        $retry = ($retry === '' || $retry === null || (int) $retry === 0) ? null : $retry;

        $this->merge([
            'max_passes_per_day' => $maxPasses,
            'retry_cooldown_minutes' => $retry,
        ]);
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
     * - 'min:0' = Minimum value is 0
     * - 'array' = Must be an array (for questions)
     * - 'in:value1,value2' = Must be one of the listed values
     *
     * Special Rules:
     * - 'questions.*.question' = Validates each question's text
     * - 'questions.*.type' = Validates each question's type
     * - Custom function = Validates options only for certain question types
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Quiz Title: Required, must be text, max 255 characters
            'title' => ['required', 'string', 'max:255'],

            // Description: Optional (nullable), if provided must be text, max 1000 characters
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'in:Elementary,High School,Senior High School'],
            'subject' => ['required', 'string', 'max:100'],
            'question_count' => ['nullable', 'integer', 'in:5,10,15'],
            'minutes_per_correct' => ['nullable', 'integer', 'min:1', 'max:60'],

            // Passing Score and fixed time reward only apply in pass_score mode.
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'time_reward_minutes' => ['required', 'integer', 'min:1'],

            'max_passes_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'retry_cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],

            // Questions Array: Required, must be array, at least 1 question
            // questions.* means "for each question in the array"
            'questions' => ['nullable', 'array'],

            // Each Question's Text: Required, must be string, max 1000 characters
            // Example: questions[0].question, questions[1].question, etc.
            'questions.*.question' => ['nullable', 'string', 'max:1000'],

            // Each Question's Type: Required, must be one of the allowed types
            // Prevents invalid question types from being saved
            'questions.*.type' => ['nullable', 'string', 'in:multiple_choice,fill_blank,true_false'],

            // Each Question's Options: Custom validation
            // - nullable = Allow empty for fill_blank questions
            // - array = If provided, must be an array
            // - Custom function = Only require options for multiple_choice and true_false
            'questions.*.options' => [
                'nullable', // Allow null/empty for fill_blank questions
                'array',    // If provided, must be an array
                function ($attribute, $value, $fail) {
                    // Custom validation: Check if options are required based on question type
                    // Extract question index from attribute (e.g., "questions.0.options" → "0")
                    $questionIndex = explode('.', $attribute)[1];
                    // Get the question type for this question
                    $type = request()->input("questions.{$questionIndex}.type");

                    // Only require options for multiple_choice and true_false
                    if (in_array($type, ['multiple_choice', 'true_false'])) {
                        // Check if options are missing or not an array
                        if (empty($value) || ! is_array($value)) {
                            $fail('Options are required for multiple choice and true/false questions.');
                        }
                        // Check if at least 2 options provided (minimum for multiple choice)
                        elseif (count($value) < 2) {
                            $fail('At least 2 options are required for multiple choice and true/false questions.');
                        }
                    }
                    // For fill_blank questions, options are not required (validation passes)
                },
            ],

            // Each Option Text: Required if options exist, must be string, max 500 characters
            // Example: questions[0].options[0], questions[0].options[1], etc.
            'questions.*.options.*' => ['required_with:questions.*.options', 'string', 'max:500'],

            // Correct Answer: Required, must be string, max 500 characters
            // This is the answer that will be compared against child's submission
            'questions.*.correct_answer' => ['nullable', 'string', 'max:500'],

            // Device Assignment: Optional array, but each ID must belong to current parent
            'devices' => ['nullable', 'array'],
            'devices.*' => [
                'integer',
                Rule::exists('devices', 'id')->where(function ($query) {
                    $query->where('user_id', Auth::id())->where('role', 'child');
                }),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * When validation fails, Laravel shows these custom error messages
     * instead of generic ones. This makes errors more user-friendly.
     *
     * Format: 'field.rule' => 'Custom error message'
     * Example: 'title.required' => 'Quiz title is required.'
     *
     * If validation fails, parent sees these messages on the form,
     * helping them understand what needs to be fixed.
     *
     * @return array<string, string> Array of field.rule => error message
     */
    public function messages(): array
    {
        return [
            // Quiz metadata validation messages
            'title.required' => 'Quiz title is required.',
            'passing_score.required' => 'Passing percentage is required.',
            'passing_score.min' => 'Passing percentage must be at least 0%.',
            'passing_score.max' => 'Passing percentage cannot exceed 100%.',
            'time_reward_minutes.required' => 'Time reward is required.',
            'time_reward_minutes.min' => 'Time reward must be at least 1 minute.',

            // Questions array validation messages
            'questions.required' => 'At least one question is required.',
            'questions.min' => 'At least one question is required.',

            // Individual question validation messages
            'questions.*.question.required' => 'Question text is required.',
            'questions.*.type.required' => 'Question type is required.',
            'questions.*.type.in' => 'Question type must be multiple choice, fill in the blank, or true/false.',
            'questions.*.options.required_if' => 'Options are required for multiple choice and true/false questions.',
            'questions.*.correct_answer.required' => 'Correct answer is required.',
            'devices.array' => 'Device selection must be an array.',
            'devices.*.integer' => 'Each selected device must be a valid ID.',
            'devices.*.exists' => 'One or more selected devices were not found or do not belong to you.',
        ];
    }
}
