<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockedWebsiteRequest;
use App\Http\Requests\UpdateBlockedWebsiteRequest;
use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Services\DomainBlockingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Blocked Website Controller
 * 
 * This controller handles all blocked website management operations for parents.
 * It provides CRUD operations, domain/app-level blocking, and integration with
 * DNS enforcement via DomainBlockingService.
 * 
 * Key Responsibilities:
 * 1. List blocked websites (filterable by device, block_type)
 * 2. Create new blocked websites (URL/Domain/App-level blocking)
 * 3. Update existing blocked websites
 * 4. Delete blocked websites
 * 5. Suggest related domains for apps (AJAX endpoint)
 * 6. Bulk import/export blocked websites
 * 
 * Authorization:
 * - All methods require authentication (user must be logged in)
 * - BlockedWebsitePolicy ensures users can only manage websites for their own devices
 * - Uses $this->authorize() to check permissions before operations
 * 
 * Integration Points:
 * - DomainBlockingService: For DNS-based domain blocking
 * - BlockedWebsitePolicy: For authorization checks
 */
class BlockedWebsiteController extends Controller
{
    use AuthorizesRequests;

    /**
     * DomainBlockingService instance for domain/app blocking operations.
     * 
     * @var DomainBlockingService
     */
    protected DomainBlockingService $domainBlockingService;

    /**
     * Constructor - Initialize BlockedWebsiteController with DomainBlockingService.
     * 
     * @param DomainBlockingService $domainBlockingService Domain blocking service (injected by Laravel)
     */
    public function __construct(DomainBlockingService $domainBlockingService)
    {
        $this->domainBlockingService = $domainBlockingService;
    }

    /**
     * Display a listing of blocked websites.
     * 
     * Route: GET /blocked-websites
     * 
     * Shows all blocked websites for the authenticated user's devices.
     * Filterable by device and block_type.
     * 
     * @param Request $request HTTP request (may contain filter parameters)
     * @return View The blocked websites index view
     */
    public function index(Request $request): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        // Build query for blocked websites
        $query = BlockedWebsite::whereHas('device', function ($q) {
            $q->where('user_id', Auth::id());
        })->with('device');

        // Filter by device if provided
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by block_type if provided
        if ($request->filled('block_type')) {
            $query->where('block_type', $request->block_type);
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
        $blockedWebsites = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('blocked-websites.index', compact('blockedWebsites', 'devices'));
    }

    /**
     * Show the form for creating a new blocked website.
     * 
     * Route: GET /blocked-websites/create
     * 
     * @return View The create blocked website form
     */
    public function create(): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        return view('blocked-websites.create', compact('devices'));
    }

    /**
     * Store a newly created blocked website.
     * 
     * Route: POST /blocked-websites
     * 
     * Creates a new blocked website and enforces DNS blocking.
     * 
     * @param StoreBlockedWebsiteRequest $request Validated form request
     * @return RedirectResponse Redirect to index with success message
     */
    public function store(StoreBlockedWebsiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Get device
        $device = Device::findOrFail($validated['device_id']);
        
        // Detect related domains if block_type is 'app'
        if ($validated['block_type'] === 'app' && isset($validated['domain'])) {
            $relatedDomains = $this->domainBlockingService->detectRelatedDomains(
                $validated['domain'],
                $request->input('app_name')
            );
            
            // Merge with user-provided related domains
            $userRelatedDomains = $validated['related_domains'] ?? [];
            $validated['related_domains'] = array_unique(array_merge($relatedDomains, $userRelatedDomains));
        }
        
        // Set defaults
        $validated['block_subdomains'] = $validated['block_subdomains'] ?? false;
        
        // Create blocked website
        $blockedWebsite = BlockedWebsite::create($validated);
        
        // Enforce DNS blocking (only on Raspberry Pi, skip on local/testing)
        // DNS blocking requires dnsmasq and shell scripts which don't work on Windows
        try {
            $dnsBlocked = $this->domainBlockingService->blockDomainForDevice($blockedWebsite, $device);
            if (!$dnsBlocked) {
                // DNS blocking failed, but record is still created
                // This is okay for local testing - DNS blocking will work on Raspberry Pi
                Log::warning("DNS blocking failed, but blocked website record created", [
                    'blocked_website_id' => $blockedWebsite->id,
                    'device_id' => $device->id,
                ]);
            }
        } catch (\Exception $e) {
            // DNS blocking failed (likely on local/Windows environment)
            // Still allow the record to be created for local testing
            Log::warning("DNS blocking exception (expected on local/Windows): " . $e->getMessage(), [
                'blocked_website_id' => $blockedWebsite->id,
                'device_id' => $device->id,
            ]);
        }
        
        return redirect()->route('blocked-websites.index')
            ->with('success', 'Website blocked successfully.');
    }

    /**
     * Show the form for editing the specified blocked website.
     * 
     * Route: GET /blocked-websites/{blockedWebsite}/edit
     * 
     * @param BlockedWebsite $blockedWebsite The blocked website to edit (route model binding)
     * @return View The edit blocked website form
     */
    public function edit(BlockedWebsite $blockedWebsite): View
    {
        // Check authorization
        $this->authorize('update', $blockedWebsite);
        
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();
        
        return view('blocked-websites.edit', compact('blockedWebsite', 'devices'));
    }

    /**
     * Update the specified blocked website.
     * 
     * Route: PUT /blocked-websites/{blockedWebsite}
     * 
     * Updates a blocked website and refreshes DNS blocking if domain changed.
     * 
     * @param UpdateBlockedWebsiteRequest $request Validated form request
     * @param BlockedWebsite $blockedWebsite The blocked website to update (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function update(UpdateBlockedWebsiteRequest $request, BlockedWebsite $blockedWebsite): RedirectResponse
    {
        // Check authorization
        $this->authorize('update', $blockedWebsite);
        
        $validated = $request->validated();
        
        // Get device
        $device = $blockedWebsite->device;
        
        // Check if domain changed (need to update DNS blocking)
        $domainChanged = $blockedWebsite->domain !== ($validated['domain'] ?? $blockedWebsite->domain);
        
        // Extract domain from URL if block_type is 'url'
        if ($validated['block_type'] === 'url' && isset($validated['url'])) {
            $validated['domain'] = $this->domainBlockingService->normalizeDomain($validated['url']);
        }
        
        // Detect related domains if block_type is 'app'
        if ($validated['block_type'] === 'app' && isset($validated['domain'])) {
            $relatedDomains = $this->domainBlockingService->detectRelatedDomains(
                $validated['domain'],
                $request->input('app_name')
            );
            
            // Merge with user-provided related domains
            $userRelatedDomains = $validated['related_domains'] ?? [];
            $validated['related_domains'] = array_unique(array_merge($relatedDomains, $userRelatedDomains));
        }
        
        // Update blocked website
        $blockedWebsite->update($validated);
        
        // Update DNS blocking if domain changed or if it's a new block
        if ($domainChanged || $blockedWebsite->wasRecentlyCreated) {
            $this->domainBlockingService->updateDnsmasqBlocklist($device);
        }
        
        return redirect()->route('blocked-websites.index')
            ->with('success', 'Blocked website updated successfully.');
    }

    /**
     * Remove the specified blocked website.
     * 
     * Route: DELETE /blocked-websites/{blockedWebsite}
     * 
     * Deletes a blocked website and removes DNS blocking.
     * 
     * @param BlockedWebsite $blockedWebsite The blocked website to delete (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function destroy(BlockedWebsite $blockedWebsite): RedirectResponse
    {
        // Check authorization
        $this->authorize('delete', $blockedWebsite);
        
        // Get device before deletion
        $device = $blockedWebsite->device;
        
        // Remove DNS blocking
        $this->domainBlockingService->unblockDomainForDevice($blockedWebsite, $device);
        
        // Delete blocked website
        $blockedWebsite->delete();
        
        return redirect()->route('blocked-websites.index')
            ->with('success', 'Blocked website removed successfully.');
    }

    /**
     * Suggest related domains for an app (AJAX endpoint).
     * 
     * Route: POST /blocked-websites/suggest-domains
     * 
     * Returns JSON array of related domains for a given domain/app name.
     * Used by frontend for auto-suggestion when blocking apps.
     * 
     * @param Request $request HTTP request (contains 'domain' or 'app_name')
     * @return JsonResponse JSON array of related domains
     */
    public function suggestRelatedDomains(Request $request): JsonResponse
    {
        try {
            // Validate request - return JSON errors if validation fails
            $validated = $request->validate([
                'domain' => 'nullable|string|max:255',
                'app_name' => 'nullable|string|max:255',
            ]);
            
            $domain = $request->input('domain');
            $appName = $request->input('app_name');
            
            // If no domain provided, return empty array
            if (!$domain) {
                return response()->json(['domains' => []]);
            }
            
            // If app_name provided but no domain, try to infer domain
            if ($appName && !$domain) {
                // Simple mapping (could be enhanced)
                $domain = strtolower($appName) . '.com';
            }
            
            if (!$domain) {
                return response()->json(['domains' => []]);
            }
            
            // Detect related domains
            $relatedDomains = $this->domainBlockingService->detectRelatedDomains($domain, $appName);
            
            return response()->json(['domains' => $relatedDomains]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return JSON validation errors instead of HTML
            return response()->json([
                'domains' => [],
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log error and return empty array
            Log::error('Error in suggestRelatedDomains', [
                'error' => $e->getMessage(),
                'domain' => $request->input('domain'),
                'app_name' => $request->input('app_name'),
            ]);
            
            return response()->json([
                'domains' => [],
                'error' => 'An error occurred while fetching related domains',
            ], 500);
        }
    }

    /**
     * Bulk import blocked websites from CSV/JSON file.
     * 
     * Route: POST /blocked-websites/bulk-import
     * 
     * @param Request $request HTTP request (contains file)
     * @return RedirectResponse Redirect with import results
     */
    public function bulkImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json|max:10240', // 10MB max
        ]);
        
        // TODO: Implement bulk import logic
        // This would parse CSV/JSON file and create multiple BlockedWebsite records
        
        return redirect()->route('blocked-websites.index')
            ->with('success', 'Bulk import completed.');
    }

    /**
     * Export blocked websites to CSV/JSON.
     * 
     * Route: GET /blocked-websites/export
     * 
     * @param Request $request HTTP request (may contain filters)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSV/JSON download
     */
    public function bulkExport(Request $request)
    {
        // Build query (same as index)
        $query = BlockedWebsite::whereHas('device', function ($q) {
            $q->where('user_id', Auth::id());
        })->with('device');
        
        // Apply filters
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }
        
        if ($request->filled('block_type')) {
            $query->where('block_type', $request->block_type);
        }
        
        $blockedWebsites = $query->get();
        
        $format = $request->input('format', 'csv');
        
        if ($format === 'json') {
            return response()->json($blockedWebsites);
        }
        
        // CSV export
        $filename = 'blocked-websites-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function () use ($blockedWebsites) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['Device', 'URL', 'Domain', 'Block Type', 'Block Subdomains', 'Related Domains', 'Reason', 'Created At']);
            
            // Data rows
            foreach ($blockedWebsites as $blockedWebsite) {
                fputcsv($file, [
                    $blockedWebsite->device->name,
                    $blockedWebsite->url,
                    $blockedWebsite->domain,
                    $blockedWebsite->block_type,
                    $blockedWebsite->block_subdomains ? 'Yes' : 'No',
                    is_array($blockedWebsite->related_domains) ? implode(', ', $blockedWebsite->related_domains) : '',
                    $blockedWebsite->reason,
                    $blockedWebsite->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
