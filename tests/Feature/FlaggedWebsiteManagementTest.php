<?php

namespace Tests\Feature;

use App\Models\FlaggedWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRUD and validation for household-wide flagged websites.
 */
class FlaggedWebsiteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_displays_correctly(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/page',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('flagged-websites.index'));

        $response->assertOk();
        $response->assertSee('FLAGGED WEBSITES');
        $response->assertSee($flaggedWebsite->url);
        $response->assertSee($flaggedWebsite->domain);
    }

    public function test_users_can_only_see_their_own_flagged_websites(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $flagged1 = FlaggedWebsite::factory()->create([
            'user_id' => $user1->id,
            'url' => 'https://user1-site.com',
            'domain' => 'user1-site.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://user2-site.com',
            'domain' => 'user2-site.com',
        ]);

        $response = $this->actingAs($user1)->get(route('flagged-websites.index'));

        $response->assertOk();
        $response->assertSee($flagged1->url);
        $response->assertDontSee($flagged2->url);
    }

    public function test_create_form_displays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('flagged-websites.create'));

        $response->assertOk();
        $response->assertSee('FLAG WEBSITE');
        $response->assertSee('URL');
        $response->assertSee('Reason');
    }

    public function test_can_create_flagged_website_with_valid_data(): void
    {
        $user = User::factory()->create();

        $data = [
            'url' => 'https://example.com/page',
            'reason' => 'Monitoring this site',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertRedirect(route('flagged-websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('flagged_websites', [
            'user_id' => $user->id,
            'url' => 'https://example.com/page',
            'domain' => 'example.com',
            'reason' => 'Monitoring this site',
        ]);
    }

    public function test_domain_is_auto_extracted_on_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('flagged-websites.store'), [
            'url' => 'https://www.facebook.com/profile',
        ]);

        $this->assertDatabaseHas('flagged_websites', [
            'url' => 'https://www.facebook.com/profile',
            'domain' => 'facebook.com',
        ]);
    }

    public function test_can_create_flagged_website_without_reason(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), [
                'url' => 'https://example.com',
            ]);

        $response->assertRedirect(route('flagged-websites.index'));
        $this->assertDatabaseHas('flagged_websites', [
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'reason' => null,
        ]);
    }

    public function test_url_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), []);

        $response->assertSessionHasErrors('url');
    }

    public function test_url_must_be_valid_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), [
                'url' => 'not-a-valid-url',
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_cannot_flag_same_domain_twice_for_same_household(): void
    {
        $user = User::factory()->create();

        FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/page1',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), [
                'url' => 'https://example.com/page2',
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_same_domain_can_exist_for_different_households(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        FlaggedWebsite::factory()->create([
            'user_id' => $user1->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user2)
            ->post(route('flagged-websites.store'), [
                'url' => 'https://example.com',
            ]);

        $response->assertRedirect(route('flagged-websites.index'));
        $this->assertDatabaseCount('flagged_websites', 2);
    }

    public function test_can_search_by_domain(): void
    {
        $user = User::factory()->create();

        $flagged1 = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://facebook.com',
            'domain' => 'facebook.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://instagram.com',
            'domain' => 'instagram.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.index', ['search' => 'facebook']));

        $response->assertOk();
        $response->assertSee($flagged1->url);
        $response->assertDontSee($flagged2->url);
    }

    public function test_can_search_by_url(): void
    {
        $user = User::factory()->create();

        $flagged = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com/specific-page',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.index', ['search' => 'specific-page']));

        $response->assertOk();
        $response->assertSee($flagged->url);
    }

    public function test_edit_form_displays(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.edit', $flaggedWebsite));

        $response->assertOk();
        $response->assertSee('EDIT FLAGGED WEBSITE');
        $response->assertSee($flaggedWebsite->url);
    }

    public function test_cannot_edit_flagged_website_for_other_users_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user1)
            ->get(route('flagged-websites.edit', $flaggedWebsite));

        $response->assertForbidden();
    }

    public function test_can_update_flagged_website(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => 'Old reason',
        ]);

        $response = $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), [
                'url' => 'https://updated.com',
                'reason' => 'New reason',
            ]);

        $response->assertRedirect(route('flagged-websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'url' => 'https://updated.com',
            'domain' => 'updated.com',
            'reason' => 'New reason',
        ]);
    }

    public function test_domain_is_re_extracted_when_url_changes(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), [
                'url' => 'https://facebook.com/page',
                'reason' => $flaggedWebsite->reason,
            ]);

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'domain' => 'facebook.com',
        ]);
    }

    public function test_domain_not_re_extracted_when_url_unchanged(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), [
                'url' => 'https://example.com',
                'reason' => 'Updated reason',
            ]);

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'domain' => 'example.com',
        ]);
    }

    public function test_cannot_update_flagged_website_for_other_users_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user1)
            ->put(route('flagged-websites.update', $flaggedWebsite), [
                'url' => 'https://updated.com',
            ]);

        $response->assertForbidden();
    }

    public function test_can_delete_flagged_website(): void
    {
        $user = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('flagged-websites.destroy', $flaggedWebsite));

        $response->assertRedirect(route('flagged-websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('flagged_websites', [
            'id' => $flaggedWebsite->id,
        ]);
    }

    public function test_cannot_delete_flagged_website_for_other_users_household(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'user_id' => $user2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user1)
            ->delete(route('flagged-websites.destroy', $flaggedWebsite));

        $response->assertForbidden();
    }

    public function test_reason_has_max_length_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), [
                'url' => 'https://example.com',
                'reason' => str_repeat('a', 501),
            ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_url_has_max_length_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), [
                'url' => 'https://example.com/'.str_repeat('a', 500),
            ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_index_page_paginates_results(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 25; $i++) {
            FlaggedWebsite::factory()->create([
                'user_id' => $user->id,
                'url' => "https://example{$i}.com/page",
                'domain' => "example{$i}.com",
            ]);
        }

        $response = $this->actingAs($user)->get(route('flagged-websites.index'));

        $response->assertOk();
        $response->assertSee('Next', false);
        $response->assertSee('page=2', false);
    }
}
