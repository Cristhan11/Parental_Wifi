<?php

namespace App\Http\Requests;

use App\Models\ReportingRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the single-form “save all recipients” POST from the Reports page.
 */
class BulkSaveReportingRecipientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $recipients = $this->input('recipients');
        if (! is_array($recipients)) {
            $this->merge(['recipients' => []]);

            return;
        }

        $filtered = collect($recipients)
            ->filter(fn ($r) => is_array($r) && filled(trim((string) ($r['email'] ?? ''))))
            ->values()
            ->map(function (array $r): array {
                $id = $r['id'] ?? null;
                if ($id === '' || $id === false) {
                    $id = null;
                }

                return [
                    'id' => $id !== null ? (int) $id : null,
                    'label' => $r['label'] ?? null,
                    'email' => trim((string) ($r['email'] ?? '')),
                    'is_enabled' => filter_var($r['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->all();

        $this->merge(['recipients' => $filtered]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '_form' => ['sometimes', 'string'],
            'recipients' => ['present', 'array'],
            'recipients.*.id' => [
                'nullable',
                'integer',
                Rule::exists('reporting_recipients', 'id')->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
            'recipients.*.label' => ['nullable', 'string', 'max:100'],
            'recipients.*.email' => ['required', 'email', 'max:255'],
            'recipients.*.is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $recipients = $this->input('recipients', []);
            if (! is_array($recipients)) {
                return;
            }

            $emails = collect($recipients)->map(fn ($r) => is_array($r) ? strtolower((string) ($r['email'] ?? '')) : '')->filter();
            if ($emails->duplicates()->isNotEmpty()) {
                $validator->errors()->add('recipients', 'Each email address must appear only once in this list.');
            }

            $userId = (int) $this->user()->id;
            foreach (array_values($recipients) as $index => $r) {
                if (! is_array($r) || ! filled($r['email'] ?? null)) {
                    continue;
                }
                $email = strtolower(trim((string) $r['email']));
                $id = isset($r['id']) && $r['id'] !== '' && $r['id'] !== null ? (int) $r['id'] : null;

                $q = ReportingRecipient::query()
                    ->where('user_id', $userId)
                    ->whereRaw('LOWER(email) = ?', [$email]);

                if ($id) {
                    $q->where('id', '!=', $id);
                }

                if ($q->exists()) {
                    $validator->errors()->add("recipients.{$index}.email", 'This email is already used for another recipient.');
                }
            }
        });
    }
}
