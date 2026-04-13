<?php

namespace Tests\Unit;

use App\Models\FlaggedWebsite;
use App\Models\User;
use App\Policies\FlaggedWebsitePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlaggedWebsitePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected FlaggedWebsitePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlaggedWebsitePolicy;
    }

    public function test_any_user_can_view_flagged_websites_list(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_can_view_flagged_website_for_own_household(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->view($user, $flaggedWebsite));
    }

    public function test_user_cannot_view_flagged_website_for_other_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->view($user1, $flaggedWebsite));
    }

    public function test_any_user_can_create_flagged_websites(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_can_update_flagged_website_for_own_household(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->update($user, $flaggedWebsite));
    }

    public function test_user_cannot_update_flagged_website_for_other_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->update($user1, $flaggedWebsite));
    }

    public function test_user_can_delete_flagged_website_for_own_household(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->delete($user, $flaggedWebsite));
    }

    public function test_user_cannot_delete_flagged_website_for_other_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->delete($user1, $flaggedWebsite));
    }
}
