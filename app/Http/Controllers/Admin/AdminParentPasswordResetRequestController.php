<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\ParentPasswordResetRequest;
use App\Services\SecurityAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminParentPasswordResetRequestController extends Controller
{
    public function __construct(
        private readonly SecurityAuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $requests = ParentPasswordResetRequest::query()
            ->pending()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.password-reset-requests.index', compact('requests'));
    }

    public function fulfill(ParentPasswordResetRequest $parent_password_reset_request): RedirectResponse
    {
        abort_unless($parent_password_reset_request->isPending(), 404);

        $user = $parent_password_reset_request->user;

        abort_unless($user !== null, 404);
        abort_unless($user->isEligibleForSelfServicePasswordResetRequest(), 404);

        AdminParentAccountController::setUserPasswordToDefault($user);

        $this->auditLogger->recordPasswordChanged(request(), $user->fresh(), 'admin.password-reset-requests.fulfill', [
            'via' => 'admin_default_password',
            'actor_user_id' => auth()->id(),
        ]);

        $parent_password_reset_request->forceFill([
            'processed_at' => now(),
            'processed_by_actor_id' => auth()->id(),
        ])->save();

        AdminActionLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $user->id,
            'action' => 'parent_password_reset_to_default',
            'note' => 'forgot_password_request',
        ]);

        return redirect()
            ->route('admin.password-reset-requests.index')
            ->with('status', 'Password set to the default (12345678). Ask the parent to sign in and change it under profile settings.');
    }
}
