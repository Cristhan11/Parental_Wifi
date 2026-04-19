<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Models\ParentPasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminParentPasswordResetRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_submission_queues_request_for_parent_account(): void
    {
        Notification::fake();

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
        ]);

        $this->post('/forgot-password', ['email' => $parent->email])
            ->assertSessionHas('status');

        Notification::assertNothingSent();

        $this->assertDatabaseHas('parent_password_reset_requests', [
            'user_id' => $parent->id,
            'processed_at' => null,
        ]);
    }

    public function test_forgot_password_does_not_queue_for_unknown_or_admin_email(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status');

        $this->assertDatabaseCount('parent_password_reset_requests', 0);

        $this->post('/forgot-password', ['email' => 'admin@example.com'])
            ->assertSessionHas('status');

        $this->assertDatabaseCount('parent_password_reset_requests', 0);
    }

    public function test_admin_can_fulfill_pending_request_with_default_password(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);
        $this->actingAs($actor);

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $req = ParentPasswordResetRequest::create(['user_id' => $parent->id]);

        $this->post(route('admin.password-reset-requests.fulfill', $req))
            ->assertRedirect(route('admin.password-reset-requests.index'))
            ->assertSessionHas('status');

        $parent->refresh();
        $this->assertTrue(Hash::check(AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD, $parent->password));
        $this->assertNotNull($parent->email_verified_at);

        $req->refresh();
        $this->assertNotNull($req->processed_at);
        $this->assertSame($actor->id, $req->processed_by_actor_id);
    }
}
