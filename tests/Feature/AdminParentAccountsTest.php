<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminParentAccountController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminParentAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsHouseholdOperator(): User
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);

        $this->actingAs($actor);

        return $actor;
    }

    public function test_household_operator_can_demote_self_or_peer_from_parent_admin_to_parent(): void
    {
        $this->actingAsHouseholdOperator();

        $operator = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);

        $this->post(route('admin.parents.demote', $operator))
            ->assertRedirect(route('admin.parents.index'))
            ->assertSessionHas('status');

        $operator->refresh();
        $this->assertSame(User::ROLE_PARENT, $operator->role);

        $this->assertDatabaseHas('admin_action_logs', [
            'target_user_id' => $operator->id,
            'action' => 'parent_demoted_from_parent_admin',
        ]);
    }

    public function test_admin_can_update_parent_name_and_email(): void
    {
        $this->actingAsHouseholdOperator();

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->patch(route('admin.parents.update', $parent), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])->assertRedirect(route('admin.parents.index'));

        $parent->refresh();
        $this->assertSame('New Name', $parent->name);
        $this->assertSame('new@example.com', $parent->email);
        $this->assertNull($parent->email_verified_at);

        $this->assertDatabaseHas('admin_action_logs', [
            'target_user_id' => $parent->id,
            'action' => 'parent_updated',
        ]);
    }

    public function test_admin_can_delete_other_parent_but_not_self(): void
    {
        $actor = $this->actingAsHouseholdOperator();

        $other = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
        ]);

        $this->delete(route('admin.parents.destroy', $other))
            ->assertRedirect(route('admin.parents.index'));

        $this->assertModelMissing($other);

        $this->delete(route('admin.parents.destroy', $actor))
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_household_operator_account(): void
    {
        $this->actingAsHouseholdOperator();

        $operator = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);

        $this->delete(route('admin.parents.destroy', $operator))
            ->assertForbidden();

        $this->assertModelExists($operator);
    }

    public function test_cannot_demote_last_remaining_household_operator(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->post(route('admin.parents.demote', $operator))
            ->assertForbidden();

        $operator->refresh();
        $this->assertSame(User::ROLE_PARENT_ADMIN, $operator->role);
    }

    public function test_admin_can_reset_parent_password_to_default(): void
    {
        $this->actingAsHouseholdOperator();

        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $this->post(route('admin.parents.reset-password-default', $parent))
            ->assertRedirect(route('admin.parents.index'))
            ->assertSessionHas('status');

        $parent->refresh();
        $this->assertTrue(Hash::check(AdminParentAccountController::DEFAULT_PARENT_RESET_PASSWORD, $parent->password));
        $this->assertNotNull($parent->email_verified_at);

        $this->assertDatabaseHas('admin_action_logs', [
            'target_user_id' => $parent->id,
            'action' => 'parent_password_reset_to_default',
        ]);
    }

    public function test_parent_user_cannot_access_admin_parent_routes(): void
    {
        $parent = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'approved_at' => now(),
        ]);

        $this->actingAs($parent);

        $this->get(route('admin.parents.index'))->assertForbidden();
    }
}
