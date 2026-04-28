<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', [
            'canRegisterParent' => $this->canRegisterParentAccounts(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAdminCapability() && ($user->requires_email_setup || $user->force_password_change)) {
            return redirect()->route('owner.onboarding.edit');
        }

        if ($user->hasAdminCapability() && ! $user->canAccessParentDashboard()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function canRegisterParentAccounts(): bool
    {
        return User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_PARENT_ADMIN])
            ->where('requires_email_setup', false)
            ->where('force_password_change', false)
            ->whereNotNull('email_verified_at')
            ->exists();
    }
}
