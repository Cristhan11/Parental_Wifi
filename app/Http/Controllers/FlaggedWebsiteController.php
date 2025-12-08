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
 * Flagged Website Controller
 * 
 * This controller handles all flagged website management operations for parents.
 * Flagged websites are monitored (not blocked) - they're allowed but logged when visited.
 * 
 * Key Responsibilities:
 * 1. List flagged websites (filterable by device)
 * 2. Create new flagged websites
 * 3. Update existing flagged websites
 * 4. Delete flagged websites
 * 
 * Authorization:
 * - All methods require authentication (user must be logged in)
 * - FlaggedWebsitePolicy ensures users can only manage websites for their own devices
 * - Uses $this->authorize() to check permissions before operations
 * 
 * Note: This is simpler than BlockedWebsiteController because:
 * - No DNS blocking (flagged sites are allowed)
 * - No related domains (not needed for monitoring)
 * - Just URL validation and domain extraction
 */
class FlaggedWebsiteController extends Controller
{
    use AuthorizesRequests;

    /**
     * DomainBlockingService instance (used only for domain normalization).
     * 
     * @var DomainBlockingService
     */
    protected DomainBlockingService $domainBlockingService;

    /**
     * Constructor - Initialize FlaggedWebsiteController with DomainBlockingService.
     * 
     * @param DomainBlockingService $domainBlockingService Domain blocking service (injected by Laravel)
     */
    public function __construct(DomainBlockingService $domainBlockingService)
    {
        $this->domainBlockingService = $domainBlockingService;
    }

    /**
     * Display a listing of flagged websites.
     * 
     * Route: GET /flagged-websites
     * 
     * Shows all flagged websites for the authenticated user's devices.
     * Filterable by device.
     * 
     * @param Request $request HTTP request (may contain filter parameters)
     * @return View The flagged websites index view
     */
    public function index(Request $request): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        // Build query for flagged websites
        $query = FlaggedWebsite::whereHas('device', function ($q) {
            $q->where('user_id', Auth::id());
        })->with('device');

        // Filter by device if provided
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Search by domain/URL if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Order by created date (newest first)
        $flaggedWebsites = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('flagged-websites.index', compact('flaggedWebsites', 'devices'));
    }

    /**
     * Show the form for creating a new flagged website.
     * 
     * Route: GET /flagged-websites/create
     * 
     * @return View The create flagged website form
     */
    public function create(): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        return view('flagged-websites.create', compact('devices'));
    }

    /**
     * Store a newly created flagged website.
     * 
     * Route: POST /flagged-websites
     * 
     * Creates a new flagged website. Domain is auto-extracted from URL.
     * 
     * @param StoreFlaggedWebsiteRequest $request Validated form request
     * @return RedirectResponse Redirect to index with success message
     */
    public function store(StoreFlaggedWebsiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Extract domain from URL
        $validated['domain'] = $this->domainBlockingService->normalizeDomain($validated['url']);
        
        // Create flagged website
        FlaggedWebsite::create($validated);
        
        return redirect()->route('flagged-websites.index')
            ->with('success', 'Website flagged successfully.');
    }

    /**
     * Show the form for editing the specified flagged website.
     * 
     * Route: GET /flagged-websites/{flaggedWebsite}/edit
     * 
     * @param FlaggedWebsite $flaggedWebsite The flagged website to edit (route model binding)
     * @return View The edit flagged website form
     */
    public function edit(FlaggedWebsite $flaggedWebsite): View
    {
        // Check authorization
        $this->authorize('update', $flaggedWebsite);
        
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();
        
        return view('flagged-websites.edit', compact('flaggedWebsite', 'devices'));
    }

    /**
     * Update the specified flagged website.
     * 
     * Route: PUT /flagged-websites/{flaggedWebsite}
     * 
     * Updates a flagged website. Domain is re-extracted from URL if URL changed.
     * 
     * @param UpdateFlaggedWebsiteRequest $request Validated form request
     * @param FlaggedWebsite $flaggedWebsite The flagged website to update (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function update(UpdateFlaggedWebsiteRequest $request, FlaggedWebsite $flaggedWebsite): RedirectResponse
    {
        // Check authorization
        $this->authorize('update', $flaggedWebsite);
        
        $validated = $request->validated();
        
        // Re-extract domain from URL if URL changed
        if (isset($validated['url']) && $validated['url'] !== $flaggedWebsite->url) {
            $validated['domain'] = $this->domainBlockingService->normalizeDomain($validated['url']);
        }
        
        // Update flagged website
        $flaggedWebsite->update($validated);
        
        return redirect()->route('flagged-websites.index')
            ->with('success', 'Flagged website updated successfully.');
    }

    /**
     * Remove the specified flagged website.
     * 
     * Route: DELETE /flagged-websites/{flaggedWebsite}
     * 
     * Deletes a flagged website.
     * 
     * @param FlaggedWebsite $flaggedWebsite The flagged website to delete (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function destroy(FlaggedWebsite $flaggedWebsite): RedirectResponse
    {
        // Check authorization
        $this->authorize('delete', $flaggedWebsite);
        
        // Delete flagged website
        $flaggedWebsite->delete();
        
        return redirect()->route('flagged-websites.index')
            ->with('success', 'Flagged website removed successfully.');
    }
}
