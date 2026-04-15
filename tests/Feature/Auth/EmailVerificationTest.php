<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_with_valid_code(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addHour(),
        ])->save();

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '123456',
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_verification_code_hash);
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->unverified()->create();

        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->addHour(),
        ])->save();

        $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '999999',
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_expired_code(): void
    {
        $user = User::factory()->unverified()->create();

        $user->forceFill([
            'email_verification_code_hash' => Hash::make('123456'),
            'email_verification_code_expires_at' => now()->subMinute(),
        ])->save();

        $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '123456',
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
