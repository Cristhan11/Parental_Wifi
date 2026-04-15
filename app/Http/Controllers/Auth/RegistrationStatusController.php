<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationStatusController extends Controller
{
    public function pendingApproval(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->canAccessParentDashboard()) {
            return redirect()->route('dashboard');
        }

        if ($user->rejected_at !== null) {
            return redirect()->route('registration.account-rejected');
        }

        if (! $user->hasParentCapability()) {
            abort(403);
        }

        return view('auth.pending-approval');
    }

    public function accountRejected(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->rejected_at === null) {
            return redirect()->route('dashboard');
        }

        return view('auth.account-rejected', [
            'note' => $user->approval_rejection_note,
        ]);
    }
}
