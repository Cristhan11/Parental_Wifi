<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $pendingParents = User::query()
            ->where('role', User::ROLE_PARENT)
            ->whereNull('approved_at')
            ->whereNull('rejected_at')
            ->orderBy('created_at')
            ->count();

        $parentCount = User::query()
            ->whereIn('role', [User::ROLE_PARENT, User::ROLE_PARENT_ADMIN])
            ->whereNotNull('approved_at')
            ->whereNull('rejected_at')
            ->count();
        $deviceCount = Device::query()->count();

        return view('admin.dashboard', compact('pendingParents', 'parentCount', 'deviceCount'));
    }
}
