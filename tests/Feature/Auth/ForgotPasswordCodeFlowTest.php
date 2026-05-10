<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use App\Support\Auth\ForgotPasswordSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordCodeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_forgot_password_code_flow_updates_password(): void
    {
        Mail::fake();

        $user = User::factory()->parentAdmin()->create([
            'email' => 'recover@example.com',
        ]);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status')
            ->assertRedirect(route('password.forgot.verify'));

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code, $user) {
            $code = $mail->code;

            return $mail->user->is($user);
        });
        $this->assertNotNull($code);

        $this->post('/forgot-password/verify', [
            'code' => $code,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.forgot.new'));

        $this->get(route('password.forgot.new'))
            ->assertOk()
            ->assertSee('recover@example.com', false);

        $this->post('/forgot-password/new', [
            'password' => 'NewSecure-Password-9',
            'password_confirmation' => 'NewSecure-Password-9',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecure-Password-9', $user->fresh()->password));
        $this->assertNull($user->fresh()->password_reset_code_hash);

        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_PASSWORD_CHANGED,
            'user_id' => $user->id,
            'route_name' => 'password.forgot.new.store',
        ]);
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'password_reset_code_hash' => Hash::make('111111'),
            'password_reset_code_expires_at' => now()->addHour(),
        ]);

        $this->withSession([ForgotPasswordSession::PENDING_EMAIL_KEY => $user->email])
            ->post('/forgot-password/verify', [
                'code' => '222222',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_new_password_page_requires_prior_verify(): void
    {
        $this->get(route('password.forgot.new'))
            ->assertRedirect(route('password.request'));
    }

    public function test_verify_page_without_pending_session_redirects_to_forgot_password(): void
    {
        $this->get(route('password.forgot.verify'))
            ->assertRedirect(route('password.request'));
    }
}
