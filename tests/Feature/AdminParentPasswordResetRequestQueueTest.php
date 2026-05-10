<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Mail\PasswordResetCodeMail;
use App\Models\ParentPasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminParentPasswordResetRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_code_mail_for_parent_account(): void
    {
        Mail::fake();

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
        ]);

        $this->post('/forgot-password', ['email' => $parent->email])
            ->assertSessionHas('status')
            ->assertRedirect(route('password.forgot.verify'));

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($parent) {
            return $mail->user->is($parent);
        });

        $this->assertDatabaseCount('parent_password_reset_requests', 0);
        $this->assertNotNull($parent->fresh()->password_reset_code_hash);
    }

    public function test_forgot_password_sends_no_mail_for_unknown_email_and_unverified_system_admin(): void
    {
        Mail::fake();

        User::factory()->admin()->unverified()->create(['email' => 'admin@example.com']);

        $this->from(route('password.request'))
            ->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertRedirect(route('password.request'));

        Mail::assertNothingSent();

        $this->from(route('password.request'))
            ->post('/forgot-password', ['email' => 'admin@example.com'])
            ->assertSessionHas('status')
            ->assertRedirect(route('password.request'));

        Mail::assertNothingSent();
    }

    public function test_forgot_password_sends_code_for_verified_system_admin(): void
    {
        Mail::fake();

        User::factory()->admin()->create(['email' => 'owner@example.com', 'email_verified_at' => now()]);

        $this->post('/forgot-password', ['email' => 'owner@example.com'])
            ->assertSessionHas('status')
            ->assertRedirect(route('password.forgot.verify'));

        Mail::assertSent(PasswordResetCodeMail::class);
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
