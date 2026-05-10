<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminParentAccountUpdateRequest;
use App\Models\AdminActionLog;
use App\Models\User;
use App\Services\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminParentAccountController extends Controller
{
    /**
     * Plaintext password applied when an admin resets a parent account from /admin/parents.
     */
    public const DEFAULT_PARENT_RESET_PASSWORD = '12345678';

    public function __construct(
        private readonly SecurityAuditLogger $auditLogger,
    ) {}

    public function pending(): View
    {
        $parents = User::query()
            ->where('role', User::ROLE_PARENT)
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->orderBy('created_at')
            ->paginate(20);

        return view('admin.parents.pending', compact('parents'));
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $parents = User::query()
            ->whereNull('rejected_at')
            ->where(function ($query) {
                $query->where(function ($parentQuery) {
                    $parentQuery->whereIn('role', [User::ROLE_PARENT, User::ROLE_PARENT_ADMIN])
                        ->whereNotNull('approved_at');
                })->orWhere(function ($ownerQuery) {
                    $ownerQuery->where('role', User::ROLE_ADMIN)
                        ->where('requires_email_setup', false)
                        ->where('force_password_change', false)
                        ->whereNotNull('email_verified_at');
                });
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $householdOperatorCount = $this->householdOperatorCount();

        return view('admin.parents.index', compact('parents', 'q', 'householdOperatorCount'));
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->isAwaitingAdminApproval(), 404);
        abort_unless($user->hasVerifiedEmail(), 403, 'Parent must verify email before approval.');

        $user->forceFill([
            'approved_at' => now(),
            'rejected_at' => null,
            'approval_rejection_note' => null,
        ])->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_approved',
            'note' => null,
        ]);

        return redirect()->route('admin.parents.pending')->with('status', 'Parent account approved.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAwaitingAdminApproval(), 404);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'rejected_at' => now(),
            'approval_rejection_note' => $validated['note'] ?? null,
        ])->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_rejected',
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.parents.pending')->with('status', 'Registration rejected.');
    }

    public function promoteToHouseholdOperator(User $user): RedirectResponse
    {
        abort_unless($user->isStrictParentRole() && $user->isApprovedParentAccount(), 404);

        $user->forceFill([
            'role' => User::ROLE_PARENT_ADMIN,
        ])->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_promoted_parent_admin',
            'note' => null,
        ]);

        return redirect()->route('admin.parents.index')->with('status', 'Account is now a household operator (parent + admin).');
    }

    public function demoteToParentRole(User $user): RedirectResponse
    {
        abort_unless($user->isParentAdmin() && $user->isApprovedParentAccount(), 404);
        abort_if(
            $this->hasSingleHouseholdOperatorRemaining() && $user->hasAdminCapability(),
            403,
            'You cannot remove household operator access from the last remaining household operator.'
        );

        $user->forceFill([
            'role' => User::ROLE_PARENT,
        ])->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_demoted_from_parent_admin',
            'note' => null,
        ]);

        return redirect()->route('admin.parents.index')->with('status', 'Account is now a standard parent (administration access removed).');
    }

    public function edit(User $user): View
    {
        $this->assertManageableApprovedParent($user);

        return view('admin.parents.edit', compact('user'));
    }

    public function update(AdminParentAccountUpdateRequest $request, User $user): RedirectResponse
    {
        $this->assertManageableApprovedParent($user);

        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_updated',
            'note' => null,
        ]);

        return redirect()->route('admin.parents.index')->with('status', 'Parent account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->assertManageableApprovedParent($user);

        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');
        abort_if(
            ! $user->isStrictParentRole(),
            403,
            'Only standard parent accounts can be deleted.'
        );

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_deleted',
            'note' => null,
        ]);

        $user->delete();

        return redirect()->route('admin.parents.index')->with('status', 'Parent account deleted.');
    }

    public static function setUserPasswordToDefault(User $user): void
    {
        $user->forceFill([
            'password' => self::DEFAULT_PARENT_RESET_PASSWORD,
        ])->save();
    }

    public function resetPasswordToDefault(User $user): RedirectResponse
    {
        $this->assertManageableApprovedParent($user);

        self::setUserPasswordToDefault($user);

        $this->auditLogger->recordPasswordChanged(request(), $user->fresh(), 'admin.parents.reset-password-default', [
            'via' => 'admin_default_password',
            'actor_user_id' => auth()->id(),
        ]);

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_password_reset_to_default',
            'note' => null,
        ]);

        return redirect()->route('admin.parents.index')->with(
            'status',
            'Password set to the default (12345678). Ask the parent to sign in and change it under profile settings.'
        );
    }

    private function assertManageableApprovedParent(User $user): void
    {
        abort_unless($user->isApprovedParentAccount(), 404);
    }

    private function hasSingleHouseholdOperatorRemaining(): bool
    {
        return $this->householdOperatorCount() <= 1;
    }

    private function householdOperatorCount(): int
    {
        return User::query()
            ->whereNull('rejected_at')
            ->where(function ($query) {
                $query->where(function ($parentAdminQuery) {
                    $parentAdminQuery->where('role', User::ROLE_PARENT_ADMIN)
                        ->whereNotNull('approved_at');
                })->orWhere(function ($ownerQuery) {
                    $ownerQuery->where('role', User::ROLE_ADMIN)
                        ->where('requires_email_setup', false)
                        ->where('force_password_change', false)
                        ->whereNotNull('email_verified_at');
                });
            })
            ->count();
    }
}
