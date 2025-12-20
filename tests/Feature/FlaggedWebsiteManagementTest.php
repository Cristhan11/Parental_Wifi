<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\FlaggedWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flagged Website Management Feature Tests
 * 
 * Tests all CRUD operations, validation, authorization, and filtering
 * for the Flagged Website Management system.
 * 
 * These tests verify:
 * - Flagged website creation, reading, updating, deletion
 * - URL validation and domain extraction
 * - Authorization (users can only manage flagged websites for their own devices)
 * - Filtering by device and search functionality
 * - Unique constraint (same domain can't be flagged twice for same device)
 * 
 * Database: Uses RefreshDatabase trait (compatible with MariaDB)
 */
class FlaggedWebsiteManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the flagged websites index page displays correctly.
     */
    public function test_index_page_displays_correctly(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com/page',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get(route('flagged-websites.index'));

        $response->assertOk();
        $response->assertSee('FLAGGED WEBSITES');
        $response->assertSee($flaggedWebsite->url);
        $response->assertSee($flaggedWebsite->domain);
    }

    /**
     * Test that users can only see flagged websites for their own devices.
     */
    public function test_users_can_only_see_their_own_flagged_websites(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $device1 = Device::factory()->create(['user_id' => $user1->id]);
        $device2 = Device::factory()->create(['user_id' => $user2->id]);

        $flagged1 = FlaggedWebsite::factory()->create([
            'device_id' => $device1->id,
            'url' => 'https://user1-site.com',
            'domain' => 'user1-site.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://user2-site.com',
            'domain' => 'user2-site.com',
        ]);

        $response = $this->actingAs($user1)->get(route('flagged-websites.index'));

        $response->assertOk();
        $response->assertSee($flagged1->url);
        $response->assertDontSee($flagged2->url);
    }

    /**
     * Test that the create form displays correctly.
     */
    public function test_create_form_displays(): void
    {
        $user = User::factory()->create();
        Device::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('flagged-websites.create'));

        $response->assertOk();
        $response->assertSee('FLAG WEBSITE');
        $response->assertSee('URL');
        $response->assertSee('Reason');
    }

    /**
     * Test creating a flagged website with valid data.
     */
    public function test_can_create_flagged_website_with_valid_data(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com/page',
            'reason' => 'Monitoring this site',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertRedirect(route('flagged-websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('flagged_websites', [
            'device_id' => $device->id,
            'url' => 'https://example.com/page',
            'domain' => 'example.com',
            'reason' => 'Monitoring this site',
        ]);
    }

    /**
     * Test that domain is auto-extracted from URL on create.
     */
    public function test_domain_is_auto_extracted_on_create(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://www.facebook.com/profile',
        ];

        $this->actingAs($user)->post(route('flagged-websites.store'), $data);

        $this->assertDatabaseHas('flagged_websites', [
            'url' => 'https://www.facebook.com/profile',
            'domain' => 'facebook.com', // Should be extracted
        ]);
    }

    /**
     * Test creating flagged website without reason (optional field).
     */
    public function test_can_create_flagged_website_without_reason(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertRedirect(route('flagged-websites.index'));
        $this->assertDatabaseHas('flagged_websites', [
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'reason' => null,
        ]);
    }

    /**
     * Test that device_id is required.
     */
    public function test_device_id_is_required(): void
    {
        $user = User::factory()->create();

        $data = [
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('device_id');
    }

    /**
     * Test that URL is required.
     */
    public function test_url_is_required(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('url');
    }

    /**
     * Test that URL must be valid format.
     */
    public function test_url_must_be_valid_format(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'not-a-valid-url',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('url');
    }

    /**
     * Test that users cannot flag websites for other users' devices.
     */
    public function test_cannot_flag_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);

        $data = [
            'device_id' => $device2->id,
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($user1)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('device_id');
    }

    /**
     * Test that same domain cannot be flagged twice for same device (unique constraint).
     */
    public function test_cannot_flag_same_domain_twice_for_same_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        // Create first flagged website
        FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com/page1',
            'domain' => 'example.com',
        ]);

        // Try to create second with same domain
        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com/page2', // Different URL, same domain
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        // Should have validation error for URL (unique domain constraint)
        $response->assertSessionHasErrors('url');
    }

    /**
     * Test that same domain can be flagged for different devices.
     */
    public function test_can_flag_same_domain_for_different_devices(): void
    {
        $user = User::factory()->create();
        $device1 = Device::factory()->create(['user_id' => $user->id]);
        $device2 = Device::factory()->create(['user_id' => $user->id]);

        // Flag for device 1
        FlaggedWebsite::factory()->create([
            'device_id' => $device1->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        // Flag for device 2 (should work)
        $data = [
            'device_id' => $device2->id,
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertRedirect(route('flagged-websites.index'));
        $this->assertDatabaseCount('flagged_websites', 2);
    }

    /**
     * Test filtering by device.
     */
    public function test_can_filter_by_device(): void
    {
        $user = User::factory()->create();
        $device1 = Device::factory()->create(['user_id' => $user->id]);
        $device2 = Device::factory()->create(['user_id' => $user->id]);

        $flagged1 = FlaggedWebsite::factory()->create([
            'device_id' => $device1->id,
            'url' => 'https://site1.com',
            'domain' => 'site1.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://site2.com',
            'domain' => 'site2.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.index', ['device_id' => $device1->id]));

        $response->assertOk();
        $response->assertSee($flagged1->url);
        $response->assertDontSee($flagged2->url);
    }

    /**
     * Test search functionality by domain.
     */
    public function test_can_search_by_domain(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $flagged1 = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://facebook.com',
            'domain' => 'facebook.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://instagram.com',
            'domain' => 'instagram.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.index', ['search' => 'facebook']));

        $response->assertOk();
        $response->assertSee($flagged1->url);
        $response->assertDontSee($flagged2->url);
    }

    /**
     * Test search functionality by URL.
     */
    public function test_can_search_by_url(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $flagged = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com/specific-page',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.index', ['search' => 'specific-page']));

        $response->assertOk();
        $response->assertSee($flagged->url);
    }

    /**
     * Test that edit form displays correctly.
     */
    public function test_edit_form_displays(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('flagged-websites.edit', $flaggedWebsite));

        $response->assertOk();
        $response->assertSee('EDIT FLAGGED WEBSITE');
        $response->assertSee($flaggedWebsite->url);
    }

    /**
     * Test that users cannot edit flagged websites for other users' devices.
     */
    public function test_cannot_edit_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user1)
            ->get(route('flagged-websites.edit', $flaggedWebsite));

        $response->assertForbidden();
    }

    /**
     * Test updating a flagged website.
     */
    public function test_can_update_flagged_website(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => 'Old reason',
        ]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://updated.com',
            'reason' => 'New reason',
        ];

        $response = $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), $data);

        $response->assertRedirect(route('flagged-websites.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'url' => 'https://updated.com',
            'domain' => 'updated.com', // Should be re-extracted
            'reason' => 'New reason',
        ]);
    }

    /**
     * Test that domain is re-extracted when URL changes on update.
     */
    public function test_domain_is_re_extracted_when_url_changes(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://facebook.com/page',
            'reason' => $flaggedWebsite->reason,
        ];

        $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), $data);

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'domain' => 'facebook.com', // Should be updated
        ]);
    }

    /**
     * Test that domain is not re-extracted when URL doesn't change on update.
     */
    public function test_domain_not_re_extracted_when_url_unchanged(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com', // Same URL
            'reason' => 'Updated reason',
        ];

        $this->actingAs($user)
            ->put(route('flagged-websites.update', $flaggedWebsite), $data);

        $this->assertDatabaseHas('flagged_websites', [
            'id' => $flaggedWebsite->id,
            'domain' => 'example.com', // Should remain unchanged
        ]);
    }

    /**
     * Test that users cannot update flagged websites for other users' devices.
     * Note: Form request validation happens before policy check, so we get redirect with errors.
     */
    public function test_cannot_update_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $data = [
            'device_id' => $device2->id,
            'url' => 'https://updated.com',
        ];

        $response = $this->actingAs($user1)
            ->put(route('flagged-websites.update', $flaggedWebsite), $data);

        // Form request validation catches device ownership first, returns redirect with errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('device_id');
    }

    /**
     * Test deleting a flagged website.
     */
    public function test_can_delete_flagged_website(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
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

    /**
     * Test that users cannot delete flagged websites for other users' devices.
     */
    public function test_cannot_delete_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user1)
            ->delete(route('flagged-websites.destroy', $flaggedWebsite));

        $response->assertForbidden();
    }

    /**
     * Test that reason field has max length validation.
     */
    public function test_reason_has_max_length_validation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'reason' => str_repeat('a', 501), // Exceeds 500 character limit
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('reason');
    }

    /**
     * Test that URL field has max length validation.
     */
    public function test_url_has_max_length_validation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com/' . str_repeat('a', 500), // Exceeds 500 character limit
        ];

        $response = $this->actingAs($user)
            ->post(route('flagged-websites.store'), $data);

        $response->assertSessionHasErrors('url');
    }

    /**
     * Test pagination on index page.
     */
    public function test_index_page_paginates_results(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        // Create more than 20 flagged websites (default pagination)
        // Ensure each has a unique domain to avoid unique constraint violation
        for ($i = 1; $i <= 25; $i++) {
            FlaggedWebsite::factory()->create([
                'device_id' => $device->id,
                'url' => "https://example{$i}.com/page",
                'domain' => "example{$i}.com",
            ]);
        }

        $response = $this->actingAs($user)->get(route('flagged-websites.index'));

        $response->assertOk();
        // Should show pagination links (check for "Next" or page numbers)
        $response->assertSee('Next', false);
        $response->assertSee('page=2', false);
    }
}

