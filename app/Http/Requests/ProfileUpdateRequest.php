<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Auth\ProfileEmailChangeSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $submitted = ProfileEmailChangeSession::normalizeEmail((string) $this->input('email'));
            $current = ProfileEmailChangeSession::normalizeEmail((string) $user->email);

            if ($submitted === $current) {
                return;
            }

            $verified = ProfileEmailChangeSession::verifiedEmail($this);
            if ($verified !== $submitted) {
                $validator->errors()->add(
                    'email',
                    __('Confirm the new email with the code sent to that address before saving.')
                );
            }
        });
    }
}
