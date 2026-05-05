<?php

/**
 * ImportQuizRequest - Form Validation for Excel File Upload
 *
 * This class validates the Excel file upload when parents import quizzes.
 * It ensures the file is valid before attempting to read and parse it.
 *
 * Why validate file type? Prevents errors from trying to read invalid files
 * (e.g., PDF, Word doc, corrupted files). Only Excel files (.xlsx, .xls) are allowed.
 *
 * Why limit file size? Prevents server overload from huge files.
 * 10MB is reasonable for quiz data (can contain hundreds of questions).
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportQuizRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
     * Validates the Excel file upload:
     * - 'required' = File must be uploaded (can't be empty)
     * - 'file' = Must be a file upload (not just text)
     * - 'mimes:xlsx,xls' = Must be Excel format (.xlsx or .xls)
     * - 'max:10240' = Maximum 10MB file size (10240 KB)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'excel_file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'mode' => ['required', 'in:add_new,update_existing'],
            'quiz_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => $this->input('mode') === 'update_existing'),
                Rule::exists('quizzes', 'id')->where(fn ($q) => $q->where('user_id', $this->user()?->id)),
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
            'excel_file.required' => 'Excel file is required.',
            'excel_file.file' => 'The uploaded file must be a valid file.',
            'excel_file.mimes' => 'Only .xlsx files are supported.',
            'excel_file.max' => 'The file size must not exceed 5MB.',
            'mode.required' => 'Please choose import mode: Add New or Update Existing.',
        ];
    }
}
