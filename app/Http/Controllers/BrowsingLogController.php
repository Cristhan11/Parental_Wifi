<?php

namespace App\Http\Controllers;

use App\Models\BrowsingLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Browsing Log Controller
 * 
 * This controller handles viewing browsing history for child devices.
 * Browsing logs are automatically created by the ParseNetworkLogs background job,
 * which parses network traffic logs and stores website visits in the database.
 * 
 * What is a Browsing Log?
 * - A browsing log is a record of every website a child device visits
 * - It contains: URL, domain, timestamp, bandwidth usage, and device information
 * - Logs are created automatically by the ParseNetworkLogs job (runs every 10 minutes)
 * - Parents can view these logs to monitor their children's internet activity
 * 
 * Key Responsibilities:
 * 1. List browsing logs with filtering (device, date range, search)
 * 2. Ensure parents can only view logs for their own devices (authorization)
 * 3. Provide pagination for large result sets
 * 
 * Authorization:
 * - All methods require authentication (user must be logged in)
 * - BrowsingLogPolicy ensures users can only view logs for their own devices
 * - Uses $this->authorize() to check permissions before operations
 * 
 * Data Flow:
 * 1. ParseNetworkLogs job parses network traffic logs
 * 2. Creates BrowsingLog records in database
 * 3. Parent visits /browsing-logs page
 * 4. Controller filters logs by device ownership
 * 5. Returns filtered, paginated results to view
 */
class BrowsingLogController extends Controller
{
    /**
     * AuthorizesRequests trait provides the authorize() method for policy checks.
     * This allows us to use $this->authorize() in controller methods.
     */
    use AuthorizesRequests;

    /**
     * Display a listing of browsing logs.
     * 
     * Route: GET /browsing-logs
     * 
     * Shows all browsing logs for the authenticated user's devices.
     * Filterable by device, date range, and search term.
     * 
     * Query Parameters:
     * - device_id: Filter by specific device (optional, can be pre-filled from Child Devices page)
     * - from_date: Start date for date range filter (optional, format: Y-m-d)
     * - to_date: End date for date range filter (optional, format: Y-m-d)
     * - search: Search term for domain/URL (optional)
     * 
     * How Filtering Works:
     * 1. Device Filter: Shows only logs for selected device (if device_id provided)
     * 2. Date Range Filter: Shows only logs within the specified date range
     * 3. Search Filter: Searches in both domain and URL fields using LIKE query
     * 4. All filters can be combined (e.g., device + date range + search)
     * 
     * Pagination:
     * - Results are paginated (20 per page); each row is one device + domain + minute with a visit count
     * 
     * Performance:
     * - Uses whereHas() to filter by device ownership efficiently
     * - Database indexes on device_id and visited_at support the grouped query
     * 
     * @param Request $request HTTP request (may contain filter parameters)
     * @return View The browsing logs index view
     */
    public function index(Request $request): View
    {
        // Check authorization: Can the user view browsing logs?
        // This calls BrowsingLogPolicy@viewAny() to check permissions
        $this->authorize('viewAny', BrowsingLog::class);

        // Get all devices for the authenticated user
        // This is used to populate the device filter dropdown
        $devices = Auth::user()->devices()->orderBy('name')->get();

        $minuteBucket = match (DB::getDriverName()) {
            'mysql' => "DATE_FORMAT(browsing_logs.visited_at, '%Y-%m-%d %H:%i')",
            'pgsql' => "to_char(browsing_logs.visited_at, 'YYYY-MM-DD HH24:MI')",
            default => "strftime('%Y-%m-%d %H:%M', browsing_logs.visited_at)",
        };

        $inner = BrowsingLog::query()
            ->whereHas('device', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->when($request->filled('device_id'), function ($q) use ($request) {
                $device = Auth::user()->devices()->find($request->device_id);
                if ($device) {
                    $q->where('browsing_logs.device_id', $request->device_id);
                }
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('browsing_logs.visited_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('browsing_logs.visited_at', '<=', $request->to_date);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($qq) use ($search) {
                    $qq->where('browsing_logs.domain', 'like', "%{$search}%")
                        ->orWhere('browsing_logs.url', 'like', "%{$search}%");
                });
            })
            ->selectRaw('
                MIN(browsing_logs.id) as id,
                browsing_logs.device_id,
                browsing_logs.domain,
                MAX(browsing_logs.url) as url,
                MAX(browsing_logs.visited_at) as visited_at,
                SUM(COALESCE(browsing_logs.visit_count, 1)) as visit_count
            ')
            ->groupBy('browsing_logs.device_id', DB::raw($minuteBucket), 'browsing_logs.domain');

        $browsingLogs = DB::query()
            ->fromSub($inner, 'grouped_logs')
            ->join('devices', 'devices.id', '=', 'grouped_logs.device_id')
            ->where('devices.user_id', Auth::id())
            ->select('grouped_logs.*', 'devices.name as device_name')
            ->orderByDesc('visited_at')
            ->paginate(20)
            ->withQueryString();

        return view('browsing-logs.index', compact('browsingLogs', 'devices'));
    }
}

