<?php

namespace App\Http\Requests;

use App\Models\FlaggedWebsite;
use App\Services\DomainBlockingService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Flagged Website Request
 *
 * Same fields as store; domain uniqueness is per user (household).
 */
class UpdateFlaggedWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    try {
                        $domainBlockingService = app(DomainBlockingService::class);
                        $domain = $domainBlockingService->normalizeDomain($value);

                        $user = $this->user();
                        $flaggedWebsite = $this->route('flaggedWebsite');
                        $flaggedWebsiteId = $flaggedWebsite instanceof FlaggedWebsite ? $flaggedWebsite->id : null;

                        if ($user && $domain !== '') {
                            $exists = FlaggedWebsite::where('user_id', $user->id)
                                ->where('domain', $domain)
                                ->when($flaggedWebsiteId, fn ($q) => $q->where('id', '!=', $flaggedWebsiteId))
                                ->exists();

                            if ($exists) {
                                $fail('This domain is already flagged for your household.');
                            }
                        }
                    } catch (\Exception $e) {
                        // Invalid URL handled by url rule
                    }
                },
            ],

            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'URL is required.',
            'url.url' => 'Please enter a valid URL (e.g., https://example.com).',
            'url.max' => 'URL cannot exceed 500 characters.',

            'reason.max' => 'Reason cannot exceed 500 characters.',
        ];
    }
}
