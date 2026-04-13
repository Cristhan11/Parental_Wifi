<?php

namespace Tests\Unit;

use App\Http\Requests\StoreFlaggedWebsiteRequest;
use App\Http\Requests\UpdateFlaggedWebsiteRequest;
use App\Models\FlaggedWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FlaggedWebsiteRequestTest extends TestCase
{
    use RefreshDatabase;

    private function storeRequest(array $data, ?User $user = null): StoreFlaggedWebsiteRequest
    {
        $request = StoreFlaggedWebsiteRequest::create('/flagged-websites', 'POST', $data);
        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    public function test_store_request_validation_rules(): void
    {
        $request = new StoreFlaggedWebsiteRequest;
        $rules = $request->rules();

        $this->assertArrayNotHasKey('device_id', $rules);
        $this->assertArrayHasKey('url', $rules);
        $this->assertArrayHasKey('reason', $rules);
    }

    public function test_store_request_url_is_required(): void
    {
        $user = User::factory()->create();
        $request = $this->storeRequest([], $user);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url'));
    }

    public function test_store_request_url_must_be_valid(): void
    {
        $user = User::factory()->create();
        $request = $this->storeRequest(['url' => 'not-a-valid-url'], $user);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url'));
    }

    public function test_store_request_reason_is_optional(): void
    {
        $user = User::factory()->create();
        $request = $this->storeRequest(['url' => 'https://example.com'], $user);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_store_request_reason_has_max_length(): void
    {
        $user = User::factory()->create();
        $request = $this->storeRequest([
            'url' => 'https://example.com',
            'reason' => str_repeat('a', 501),
        ], $user);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('reason'));
    }

    public function test_store_request_rejects_duplicate_domain_for_same_user(): void
    {
        $user = User::factory()->create();
        FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/a',
            'domain' => 'example.com',
        ]);

        $request = $this->storeRequest(['url' => 'https://example.com/b'], $user);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url'));
    }

    public function test_update_request_validation_rules(): void
    {
        $request = new UpdateFlaggedWebsiteRequest;
        $rules = $request->rules();

        $this->assertArrayNotHasKey('device_id', $rules);
        $this->assertArrayHasKey('url', $rules);
        $this->assertArrayHasKey('reason', $rules);
    }

    public function test_update_request_has_same_rule_keys_as_store(): void
    {
        $storeRequest = new StoreFlaggedWebsiteRequest;
        $updateRequest = new UpdateFlaggedWebsiteRequest;

        $this->assertEquals(array_keys($storeRequest->rules()), array_keys($updateRequest->rules()));
    }
}
