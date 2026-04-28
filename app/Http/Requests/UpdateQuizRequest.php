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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'level' => ['required', 'in:Elementary,High School,Senior High School'],
            'subject' => ['required', 'string', 'max:100'],
            'question_count' => ['nullable', 'integer', 'in:5,10,15'],
            'minutes_per_correct' => ['nullable', 'integer', 'min:1', 'max:60'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'time_reward_minutes' => ['required', 'integer', 'min:1'],
            // Active Status: Optional (sometimes), must be boolean (true/false)
            // 'sometimes' = Only validate if field is present in request
            // This allows updating quiz without changing is_active status
            // Checkbox sends "0" (unchecked) or "1" (checked), Laravel converts to boolean
            'is_active' => ['sometimes', 'boolean'],
            'questions' => ['nullable', 'array'],
            'questions.*.question' => ['nullable', 'string', 'max:1000'],
            'questions.*.type' => ['nullable', 'string', 'in:multiple_choice,fill_blank,true_false'],
            'questions.*.options' => [
                'nullable', // Allow null/empty for fill_blank
                'array',
                function ($attribute, $value, $fail) {
                    // Custom validation: only require min 2 options if type is multiple_choice or true_false
                    $questionIndex = explode('.', $attribute)[1];
                    $type = request()->input("questions.{$questionIndex}.type");
                    if (in_array($type, ['multiple_choice', 'true_false'])) {
                        if (empty($value) || ! is_array($value)) {
                            $fail('Options are required for multiple choice and true/false questions.');
                        } elseif (count($value) < 2) {
                            $fail('At least 2 options are required for multiple choice and true/false questions.');
                        }
                    }
                },
            ],
            'questions.*.options.*' => ['required_with:questions.*.options', 'string', 'max:500'],
            'max_passes_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'retry_cooldown_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'questions.*.correct_answer' => ['nullable', 'string', 'max:500'],
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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Quiz title is required.',
            'passing_score.required' => 'Passing percentage is required.',
            'passing_score.min' => 'Passing percentage must be at least 0%.',
            'passing_score.max' => 'Passing percentage cannot exceed 100%.',
            'time_reward_minutes.required' => 'Time reward is required.',
            'time_reward_minutes.min' => 'Time reward must be at least 1 minute.',
            'questions.required' => 'At least one question is required.',
            'questions.min' => 'At least one question is required.',
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
