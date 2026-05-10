<?php

namespace Tests\Feature;

use App\Mail\ProfileEmailChangeCodeMail;
use App\Models\ReportingRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_email_change_is_rejected_without_confirmation_code_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('email');

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_profile_information_can_be_updated_after_email_confirmation_code(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('profile.email-change.send-code'), ['email' => 'test@example.com'])
            ->assertOk()
            ->assertJson(['ok' => true, 'already_verified' => false]);

        $code = null;
        Mail::assertSent(ProfileEmailChangeCodeMail::class, function (ProfileEmailChangeCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code);

        $this->actingAs($user)
            ->postJson(route('profile.email-change.verify-code'), ['code' => $code])
            ->assertOk()
            ->assertJson(['ok' => true, 'email' => 'test@example.com']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code_hash);
        $this->assertNotNull($user->email_verification_code_expires_at);
    }

    public function test_changing_profile_email_removes_recipient_row_for_previous_address_but_keeps_others(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);
        ReportingRecipient::create([
            'user_id' => $user->id,
            'email' => 'owner@example.com',
            'label' => ReportingRecipient::LABEL_OWNER_VERIFIED_EMAIL,
            'is_enabled' => true,
        ]);
        ReportingRecipient::create([
            'user_id' => $user->id,
            'email' => 'other@example.com',
            'label' => 'Other',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('profile.email-change.send-code'), ['email' => 'newowner@example.com'])
            ->assertOk();

        $code = null;
        Mail::assertSent(ProfileEmailChangeCodeMail::class, function (ProfileEmailChangeCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->actingAs($user)
            ->postJson(route('profile.email-change.verify-code'), ['code' => $code])
            ->assertOk();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => 'newowner@example.com',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('reporting_recipients', [
            'user_id' => $user->id,
            'email' => 'owner@example.com',
        ]);
        $this->assertDatabaseHas('reporting_recipients', [
            'user_id' => $user->id,
            'email' => 'other@example.com',
        ]);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_system_admin_cannot_delete_their_account_from_profile(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('profile_delete_blocked');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
    }

    public function test_household_operator_cannot_delete_their_account_from_profile(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('profile_delete_blocked');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
    }
}
