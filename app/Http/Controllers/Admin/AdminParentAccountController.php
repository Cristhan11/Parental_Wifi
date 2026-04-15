<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminParentAccountController extends Controller
{
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
            ->whereIn('role', [User::ROLE_PARENT, User::ROLE_PARENT_ADMIN])
            ->whereNotNull('approved_at')
            ->whereNull('rejected_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parents.index', compact('parents', 'q'));
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
}
