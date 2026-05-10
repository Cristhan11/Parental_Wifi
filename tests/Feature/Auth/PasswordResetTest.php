<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $this->get('/reset-password/'.$plainToken.'?email='.urlencode($user->email))
            ->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => Hash::make($plainToken), 'created_at' => now()]
        );

        $this->post('/reset-password', [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_broker_sends_numeric_code_mail_not_url_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Password::sendResetLink(['email' => $user->email]);

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($user) {
            return $mail->user->is($user);
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
