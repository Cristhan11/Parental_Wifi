<?php

namespace Tests\Unit;

use App\Models\FlaggedWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlaggedWebsiteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_website_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertInstanceOf(User::class, $flaggedWebsite->user);
        $this->assertEquals($user->id, $flaggedWebsite->user->id);
    }

    public function test_user_has_many_flagged_websites(): void
    {
        $user = User::factory()->create();

        $flagged1 = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example1.com',
            'domain' => 'example1.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example2.com',
            'domain' => 'example2.com',
        ]);

        $this->assertCount(2, $user->flaggedWebsites);
        $this->assertTrue($user->flaggedWebsites->contains($flagged1));
        $this->assertTrue($user->flaggedWebsites->contains($flagged2));
    }

    public function test_flagged_website_has_fillable_attributes(): void
    {
        $user = User::factory()->create();

        $data = [
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => 'Test reason',
        ];

        $flaggedWebsite = FlaggedWebsite::create($data);

        $this->assertEquals($data['user_id'], $flaggedWebsite->user_id);
        $this->assertEquals($data['url'], $flaggedWebsite->url);
        $this->assertEquals($data['domain'], $flaggedWebsite->domain);
        $this->assertEquals($data['reason'], $flaggedWebsite->reason);
    }

    public function test_reason_can_be_null(): void
    {
        $user = User::factory()->create();

        $flaggedWebsite = FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => null,
        ]);

        $this->assertNull($flaggedWebsite->reason);
    }

    public function test_timestamps_are_automatically_set(): void
    {
        $user = User::factory()->create();

        $flaggedWebsite = FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertNotNull($flaggedWebsite->created_at);
        $this->assertNotNull($flaggedWebsite->updated_at);
    }

    public function test_flagged_website_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $user->delete();

        $this->assertDatabaseMissing('flagged_websites', [
            'id' => $flaggedWebsite->id,
        ]);
    }
}
