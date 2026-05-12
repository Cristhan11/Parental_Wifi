<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function approvedParentWithForcedChange(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'force_password_change' => true,
            'password' => AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD,
        ]);
    }

    public function test_login_redirects_parent_with_forced_change_to_force_change_page(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->post('/login', [
            'email' => $parent->email,
            'password' => AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD,
        ])->assertRedirect(route('password.force-change'));

        $this->assertAuthenticatedAs($parent);
    }

    public function test_dashboard_access_redirects_to_force_change_page(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.force-change'));
    }

    public function test_profile_access_redirects_to_force_change_page(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->actingAs($parent)
            ->get(route('profile.edit'))
            ->assertRedirect(route('password.force-change'));
    }

    public function test_force_change_page_renders_for_eligible_parent(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->actingAs($parent)
            ->get(route('password.force-change'))
            ->assertOk();
    }

    public function test_force_change_page_redirects_to_dashboard_when_flag_already_clear(): void
    {
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'force_password_change' => false,
        ]);

        $this->actingAs($parent)
            ->get(route('password.force-change'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_parent_can_complete_force_change_and_reach_dashboard(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->actingAs($parent)
            ->post(route('password.force-change.update'), [
                'password' => 'Brand-New-Pw-2026!',
                'password_confirmation' => 'Brand-New-Pw-2026!',
            ])
            ->assertRedirect(route('dashboard'));

        $parent->refresh();
        $this->assertFalse($parent->force_password_change);
        $this->assertTrue(Hash::check('Brand-New-Pw-2026!', $parent->password));

        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_parent_cannot_set_password_back_to_default(): void
    {
        $parent = $this->approvedParentWithForcedChange();

        $this->actingAs($parent)
            ->post(route('password.force-change.update'), [
                'password' => AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD,
                'password_confirmation' => AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD,
            ])
            ->assertSessionHasErrors('password');

        $parent->refresh();
        $this->assertTrue($parent->force_password_change);
    }

    public function test_admin_capable_user_with_forced_change_is_routed_to_owner_onboarding(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'force_password_change' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('password.force-change'))
            ->assertRedirect(route('owner.onboarding.edit'));
    }
}
