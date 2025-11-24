<?php

/**
 * UpdateQuizRequest - Form Validation for Quiz Updates
 * 
 * This class validates form data when a parent edits an existing quiz.
 * It's almost identical to StoreQuizRequest, but includes an additional
 * 'is_active' field to allow parents to enable/disable quizzes.
 * 
 * Why separate from StoreQuizRequest? Allows different validation rules
 * for create vs update if needed in the future (e.g., allow changing
 * quiz ID, or prevent changing certain fields after creation).
 * 
 * Key Difference: Includes 'is_active' checkbox validation
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
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
     * Same validation rules as StoreQuizRequest, plus:
     * - 'is_active' field (optional boolean checkbox)
     * 
     * The 'is_active' field allows parents to enable/disable quizzes
     * without deleting them. Disabled quizzes won't appear in the portal.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'time_reward_minutes' => ['required', 'integer', 'min:1'],
            // Active Status: Optional (sometimes), must be boolean (true/false)
            // 'sometimes' = Only validate if field is present in request
            // This allows updating quiz without changing is_active status
            // Checkbox sends "0" (unchecked) or "1" (checked), Laravel converts to boolean
            'is_active' => ['sometimes', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:1000'],
            'questions.*.type' => ['required', 'string', 'in:multiple_choice,fill_blank,true_false'],
            'questions.*.options' => [
                'nullable', // Allow null/empty for fill_blank
                'array',
                function ($attribute, $value, $fail) {
                    // Custom validation: only require min 2 options if type is multiple_choice or true_false
                    $questionIndex = explode('.', $attribute)[1];
                    $type = request()->input("questions.{$questionIndex}.type");
                    if (in_array($type, ['multiple_choice', 'true_false'])) {
                        if (empty($value) || !is_array($value)) {
                            $fail('Options are required for multiple choice and true/false questions.');
                        } elseif (count($value) < 2) {
                            $fail('At least 2 options are required for multiple choice and true/false questions.');
                        }
                    }
                }
            ],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_answer' => ['required', 'string', 'max:500'],
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
            'title.required' => 'Quiz title is required.',
            'passing_score.required' => 'Passing score is required.',
            'passing_score.min' => 'Passing score must be at least 0%.',
            'passing_score.max' => 'Passing score cannot exceed 100%.',
            'time_reward_minutes.required' => 'Time reward is required.',
            'time_reward_minutes.min' => 'Time reward must be at least 1 minute.',
            'questions.required' => 'At least one question is required.',
            'questions.min' => 'At least one question is required.',
            'questions.*.question.required' => 'Question text is required.',
            'questions.*.type.required' => 'Question type is required.',
            'questions.*.type.in' => 'Question type must be multiple choice, fill in the blank, or true/false.',
            'questions.*.options.required_if' => 'Options are required for multiple choice and true/false questions.',
            'questions.*.correct_answer.required' => 'Correct answer is required.',
        ];
    }
}

