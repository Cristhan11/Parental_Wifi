<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFlaggedWebsiteRequest;
use App\Http\Requests\UpdateFlaggedWebsiteRequest;
use App\Models\FlaggedWebsite;
use App\Services\DomainBlockingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Flagged websites: monitored but not blocked; household-wide per parent.
 */
class FlaggedWebsiteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DomainBlockingService $domainBlockingService
    ) {}

    public function index(Request $request): View
    {
        $query = FlaggedWebsite::where('user_id', Auth::id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $flaggedWebsites = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('flagged-websites.index', compact('flaggedWebsites'));
    }

    public function create(): View
    {
        return view('flagged-websites.create');
    }

    public function store(StoreFlaggedWebsiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $validated['domain'] = $this->domainBlockingService->normalizeDomain($validated['url']);

        FlaggedWebsite::create($validated);

        return redirect()->route('flagged-websites.index')
            ->with('success', 'Website flagged successfully.');
    }

    public function edit(FlaggedWebsite $flaggedWebsite): View
    {
        $this->authorize('update', $flaggedWebsite);

        return view('flagged-websites.edit', compact('flaggedWebsite'));
    }

    public function update(UpdateFlaggedWebsiteRequest $request, FlaggedWebsite $flaggedWebsite): RedirectResponse
    {
        $this->authorize('update', $flaggedWebsite);

        $validated = $request->validated();

        if (isset($validated['url']) && $validated['url'] !== $flaggedWebsite->url) {
            $validated['domain'] = $this->domainBlockingService->normalizeDomain($validated['url']);
        }

        $flaggedWebsite->update($validated);

        return redirect()->route('flagged-websites.index')
            ->with('success', 'Flagged website updated successfully.');
    }

    public function destroy(FlaggedWebsite $flaggedWebsite): RedirectResponse
    {
        $this->authorize('delete', $flaggedWebsite);

        $flaggedWebsite->delete();

        return redirect()->route('flagged-websites.index')
            ->with('success', 'Flagged website removed successfully.');
    }
}
