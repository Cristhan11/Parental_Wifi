<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use App\Services\DeviceService;
use App\Services\NetworkService;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Device Controller
 * 
 * This controller handles all device management operations for parents.
 * It provides CRUD operations, status management, time allocation, and
 * integration with network services for device blocking/unblocking.
 * 
 * What is a Controller?
 * - A controller handles HTTP requests and responses
 * - It receives requests from routes, processes them, and returns responses
 * - It coordinates between models (database), services (business logic), and views (UI)
 * 
 * Key Responsibilities:
 * 1. List devices (Accounts view - Image 4, Child Devices stats - Image 3)
 * 2. Create new devices
 * 3. Update existing devices
 * 4. Delete devices
 * 5. Update device status (active/blocked/whitelisted)
 * 6. Manage time allocation
 * 7. Display device statistics (time usage, quiz scores, website history)
 * 8. Get real-time connected devices
 * 
 * Authorization:
 * - All methods require authentication (user must be logged in)
 * - DevicePolicy ensures users can only manage their own devices
 * - Uses $this->authorize() to check permissions before operations
 * 
 * Integration Points:
 * - NetworkService: For network-level blocking/unblocking
 * - TimeTrackingService: For time allocation and tracking
 * - DeviceService: For MAC address normalization and utilities
 * 
 * Usage Example:
 * ```php
 * // Route automatically calls controller method
 * Route::get('/devices', [DeviceController::class, 'index']);
 * // When user visits /devices, index() method is called
 * ```
 */
class DeviceController extends Controller
{
    /**
     * AuthorizesRequests trait provides the authorize() method for policy checks.
     * This allows us to use $this->authorize() in controller methods.
     */
    use AuthorizesRequests;

    /**
     * DeviceService instance for device management utilities.
     * 
     * DeviceService provides helper methods for:
     * - MAC address normalization
     * - Device statistics calculation
     * - Status synchronization
     * 
     * @var DeviceService
     */
    protected DeviceService $deviceService;

    /**
     * NetworkService instance for network-level operations.
     * 
     * NetworkService handles:
     * - Blocking/unblocking devices at network level
     * - Getting connected devices
     * - Checking network status
     * 
     * @var NetworkService
     */
    protected NetworkService $networkService;

    /**
     * TimeTrackingService instance for time management.
     * 
     * TimeTrackingService handles:
     * - Calculating remaining time
     * - Checking time expiration
     * - Managing time allocation
     * 
     * @var TimeTrackingService
     */
    protected TimeTrackingService $timeTrackingService;

    /**
     * Constructor - Called automatically when controller is created.
     * 
     * Laravel's dependency injection automatically provides these services.
     * This is called "dependency injection" - Laravel creates the services for us.
     * 
     * @param DeviceService $deviceService Device management utilities
     * @param NetworkService $networkService Network-level operations
     * @param TimeTrackingService $timeTrackingService Time management
     */
    public function __construct(
        DeviceService $deviceService,
        NetworkService $networkService,
        TimeTrackingService $timeTrackingService
    ) {
        $this->deviceService = $deviceService;
        $this->networkService = $networkService;
        $this->timeTrackingService = $timeTrackingService;
    }

    /**
     * Display the Accounts/Device Management view (Image 4).
     * 
     * Route: GET /accounts
     * 
     * This is the main device management interface showing all devices in a table.
     * Based on design reference Image 4: "ACCOUNTS" tab with device management table.
     * 
     * What It Shows:
     * - Device MAC addresses
     * - Assigned roles (CHILD, GUEST, PARENT)
     * - Device names
     * - Blocklist/Whitelist functionality
     * 
     * Layout Structure (from Image 4):
     * - Yellow header bar with "ACCOUNTS" title
     * - Action buttons: Blocklist, Whitelist, + New
     * - Main table with columns: DEVICES MAC ADDRESS, ASSIGNED ROLES, NAME
     * 
     * @return View The accounts/device management view
     * 
     * Usage:
     * User visits /accounts to see all their devices in a table.
     */
    public function accounts(): View
    {
        // Get all devices for the authenticated user (parent)
        // Auth::user() gets the currently logged-in user
        // ->devices() gets all devices owned by this parent (via relationship)
        // ->orderBy('name') orders devices alphabetically by name
        // ->get() executes query and returns collection
        $devices = Auth::user()->devices()
            ->orderBy('name')
            ->get();

        // Return the accounts view with devices data
        // compact('devices') creates ['devices' => $devices] array
        // View will display devices in a table matching Image 4 design
        return view('devices.accounts', compact('devices'));
    }

    /**
     * Display the Child Devices stats view (Image 3).
     * 
     * Route: GET /devices or GET /devices/{device}
     * 
     * This view shows statistics for a selected device:
     * - TIME USAGE graph (line graph showing hours per month)
     * - QUIZ SCORE list (all quiz attempts with scores)
     * - WEBSITE HISTORY list (recently visited websites)
     * 
     * Based on design reference Image 3: "CHILD DEVICES" tab with stats.
     * 
     * Layout Structure (from Image 3):
     * - Yellow header bar with "CHILD DEVICES" title
     * - Child dropdown selector (filter by device)
     * - Card 1: TIME USAGE (line graph + time offline/online table)
     * - Card 2: QUIZ SCORE (list of quiz scores)
     * - Card 3: WEBSITE HISTORY (list of visited websites)
     * 
     * @param Request $request HTTP request (may contain 'device' query parameter)
     * @param Device|null $device Optional device to show stats for (from route parameter)
     * @return View The child devices stats view
     * 
     * Usage:
     * - GET /devices - Shows stats for first device (or empty if no devices)
     * - GET /devices?device=1 - Shows stats for device with ID 1 (from query parameter)
     * - GET /devices/1 - Shows stats for device with ID 1 (from route parameter)
     */
    public function index(Request $request, ?Device $device = null): View
    {
        // Get all devices for the authenticated user
        // Used for the child dropdown selector
        $devices = Auth::user()->devices()
            ->orderBy('name')
            ->get();

        // If device is provided via query parameter, load it
        // Query parameter format: /child_devices?device=1
        if (!$device && $request->has('device')) {
            $deviceId = $request->input('device');
            $device = Auth::user()->devices()->find($deviceId);
        }
        
        // If device is provided via route parameter (e.g., /child_devices/1), it's already loaded
        // Route model binding automatically resolves Device from route {device} parameter

        // If no device specified, use first device (or null if no devices)
        if (!$device && $devices->isNotEmpty()) {
            $device = $devices->first();
        }

        // If device is specified, check authorization
        // $this->authorize() calls DevicePolicy::view() to check if user owns device
        // If user doesn't own device, Laravel throws 403 Forbidden
        if ($device) {
            $this->authorize('view', $device);
        }

        // Initialize statistics arrays (will be populated if device exists)
        $timeUsageData = [];
        $quizScores = [];
        $websiteHistory = [];

        // If device exists, calculate statistics
        if ($device) {
            // Get time usage data for graph (aggregated by month)
            // Query DeviceSession table for this device
            // Group by month and calculate total hours per month
            $timeUsageData = $this->getTimeUsageData($device);

            // Get quiz scores for this device
            // Query QuizAttempt table and calculate scores
            $quizScores = $this->getQuizScores($device);

            // Get website history (recently visited websites)
            // Query BrowsingLog table for this device
            $websiteHistory = $this->getWebsiteHistory($device);
        }

        // Return the child_devices view with all data
        // compact() creates array with all variables
        return view('devices.child_devices', compact('devices', 'device', 'timeUsageData', 'quizScores', 'websiteHistory'));
    }

    /**
     * Show the form for creating a new device.
     * 
     * Route: GET /devices/create
     * 
     * Displays a form where parents can add a new device.
     * Form includes fields for: name, MAC address, role, status, time allocation.
     * 
     * @return View The device creation form
     * 
     * Usage:
     * User visits /devices/create to see the form for adding a new device.
     */
    public function create(): View
    {
        // Check authorization - can user create devices?
        // $this->authorize() calls DevicePolicy::create()
        // In our system, all authenticated users can create devices
        $this->authorize('create', Device::class);

        // Get connected devices from network (optional helper)
        // This shows available MAC addresses that parents can register
        // getConnectedDevices() returns array of devices currently on network
        $connectedDevices = $this->networkService->getConnectedDevices();

        // Return the create view
        // compact('connectedDevices') passes connected devices to view
        // View can display these as suggestions for MAC addresses
        return view('devices.device_create', compact('connectedDevices'));
    }

    /**
     * Store a newly created device in storage.
     * 
     * Route: POST /devices
     * 
     * What It Does:
     * 1. Validates form data (via StoreDeviceRequest)
     * 2. Normalizes MAC address to standard format
     * 3. Sets default time allocation if not provided
     * 4. Creates device in database
     * 5. Applies network-level blocking if status is 'blocked'
     * 6. Redirects to accounts view with success message
     * 
     * @param StoreDeviceRequest $request Validated form data (name, mac_address, status, etc.)
     * @return RedirectResponse Redirects to accounts view with success message
     * 
     * Usage:
     * User submits create form, this method processes it and creates the device.
     */
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        // Check authorization - can user create devices?
        $this->authorize('create', Device::class);

        // Get validated form data
        // StoreDeviceRequest ensures all required fields exist and are valid
        $validated = $request->validated();

        // Normalize MAC address to standard format (XX:XX:XX:XX:XX:XX, uppercase)
        // This ensures consistent format in database
        // Example: "aa-bb-cc-dd-ee-ff" becomes "AA:BB:CC:DD:EE:FF"
        $validated['mac_address'] = $this->deviceService->normalizeMacAddress($validated['mac_address']);

        // Set default time allocation if not provided
        // Default is 15 minutes for new devices
        if (!isset($validated['remaining_time_minutes'])) {
            $validated['remaining_time_minutes'] = 15; // Default 15 minutes
        }

        // Set total_time_allocated to match remaining_time_minutes if not provided
        // This tracks the total time allocated for reporting purposes
        if (!isset($validated['total_time_allocated'])) {
            $validated['total_time_allocated'] = $validated['remaining_time_minutes'];
        }

        // Create device in database
        // Device::create() automatically saves to database
        // Auth::id() assigns device to current user (parent)
        $device = Device::create([
            'user_id' => Auth::id(), // Link device to current parent
            'name' => $validated['name'],
            'mac_address' => $validated['mac_address'],
            'role' => $validated['role'] ?? 'child', // Default to 'child' if not provided
            'status' => $validated['status'],
            'remaining_time_minutes' => $validated['remaining_time_minutes'],
            'total_time_allocated' => $validated['total_time_allocated'],
        ]);

        // Apply network-level blocking if status is 'blocked'
        // This ensures database status matches network status
        // If device is created as 'blocked', block it at network level immediately
        if ($device->status === 'blocked') {
            // blockDevice() executes block_device.sh script to add iptables rules
            $this->networkService->blockDevice($device);
        }
        // Apply whitelisting if status is 'whitelisted'
        elseif ($device->status === 'whitelisted') {
            // whitelistDevice() executes whitelist_device.sh script to bypass restrictions
            $this->networkService->whitelistDevice($device);
        }

        // Redirect to accounts view with success message
        // ->with() stores message in session that displays on next page
        return redirect()->route('accounts.index')
            ->with('success', 'Device created successfully!');
    }

    /**
     * Show the form for editing the specified device.
     * 
     * Route: GET /devices/{device}/edit
     * 
     * Displays a form pre-filled with existing device data.
     * Also shows device statistics (connection status, sessions, logs).
     * 
     * @param Device $device The device to edit (Laravel automatically finds it by ID from URL)
     * @return View The device edit form with existing data and statistics
     * 
     * Usage:
     * User visits /devices/1/edit to edit device with ID 1.
     */
    public function edit(Device $device): View
    {
        // Check authorization - does user own this device?
        // $this->authorize() calls DevicePolicy::update() to check ownership
        // If user doesn't own device, Laravel throws 403 Forbidden
        $this->authorize('update', $device);

        // Get device statistics
        // getDeviceStats() returns array with: sessions_count, logs_count, etc.
        $stats = $this->deviceService->getDeviceStats($device);

        // Get connected devices from network
        // Used to check if this device is currently connected
        $connectedDevices = $this->networkService->getConnectedDevices();

        // Check if device is currently connected
        // Match by MAC address to see if device is on network
        $isConnected = false;
        $deviceIp = null;
        foreach ($connectedDevices as $connectedDevice) {
            if (strtoupper($connectedDevice['mac_address'] ?? '') === strtoupper($device->mac_address)) {
                $isConnected = true;
                $deviceIp = $connectedDevice['ip_address'] ?? null;
                break;
            }
        }

        // Return the edit view with device data and statistics
        return view('devices.device_edit', compact('device', 'stats', 'isConnected', 'deviceIp'));
    }

    /**
     * Update the specified device in storage.
     * 
     * Route: PUT /devices/{device}
     * 
     * What It Does:
     * 1. Validates form data (via UpdateDeviceRequest)
     * 2. Checks authorization (user owns device)
     * 3. Normalizes MAC address if changed
     * 4. Updates device in database
     * 5. Syncs network-level blocking if status changed
     * 6. Redirects with success message
     * 
     * @param UpdateDeviceRequest $request Validated form data
     * @param Device $device The device to update (found by ID from URL)
     * @return RedirectResponse Redirects to accounts view with success message
     * 
     * Usage:
     * User submits edit form, this method processes it and updates the device.
     */
    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        // Check authorization - does user own this device?
        $this->authorize('update', $device);

        // Get validated form data
        $validated = $request->validated();

        // Store old status to check if it changed
        // If status changes, we need to update network-level blocking
        $oldStatus = $device->status;

        // Normalize MAC address to standard format
        $validated['mac_address'] = $this->deviceService->normalizeMacAddress($validated['mac_address']);

        // Update device in database
        // update() saves changes to database
        $device->update($validated);

        // Sync network-level blocking if status changed
        // This ensures database status matches network status
        if ($oldStatus !== $device->status) {
            // If status changed to 'blocked', block at network level
            if ($device->status === 'blocked') {
                $this->networkService->blockDevice($device);
            }
            // If status changed to 'active', unblock at network level
            elseif ($device->status === 'active') {
                $this->networkService->unblockDevice($device);
            }
            // If status changed to 'whitelisted', whitelist at network level
            elseif ($device->status === 'whitelisted') {
                $this->networkService->whitelistDevice($device);
            }
        }

        // Redirect to accounts view with success message
        return redirect()->route('accounts.index')
            ->with('success', 'Device updated successfully!');
    }

    /**
     * Remove the specified device from storage.
     * 
     * Route: DELETE /devices/{device}
     * 
     * What It Does:
     * 1. Checks authorization (user owns device)
     * 2. Unblocks device at network level (if blocked)
     * 3. Deletes device from database (cascades to related records)
     * 4. Redirects with success message
     * 
     * Important:
     * - Deleting a device will cascade delete related records (browsing logs, sessions, etc.)
     * - This is handled by database foreign key constraints
     * 
     * @param Device $device The device to delete (found by ID from URL)
     * @return RedirectResponse Redirects to accounts view with success message
     * 
     * Usage:
     * User clicks delete button, this method removes the device.
     */
    public function destroy(Device $device): RedirectResponse
    {
        // Check authorization - does user own this device?
        $this->authorize('delete', $device);

        // Unblock device at network level before deletion
        // This removes iptables rules so device can access internet again
        // Important: Do this before deletion so we can still access device data
        if ($device->status === 'blocked') {
            $this->networkService->unblockDevice($device);
        }

        // Delete device from database
        // delete() removes device record
        // Related records (browsing logs, sessions, etc.) are cascade deleted
        $device->delete();

        // Redirect to accounts view with success message
        return redirect()->route('accounts.index')
            ->with('success', 'Device deleted successfully!');
    }

    /**
     * Update device status (active/blocked/whitelisted).
     * 
     * Route: POST /devices/{device}/status
     * 
     * This method allows quick status updates without full form submission.
     * Used by AJAX requests or quick action buttons.
     * 
     * What It Does:
     * 1. Validates status value
     * 2. Checks authorization
     * 3. Updates device status
     * 4. Syncs network-level blocking
     * 5. Returns JSON response (for AJAX) or redirects
     * 
     * @param Request $request HTTP request containing 'status' field
     * @param Device $device The device to update
     * @return JsonResponse|RedirectResponse JSON response for AJAX, redirect for regular requests
     * 
     * Usage:
     * - AJAX: POST /devices/1/status with {status: 'blocked'}
     * - Form: Submit form with status field
     */
    public function updateStatus(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        // Check authorization
        $this->authorize('update', $device);

        // Validate status value
        $request->validate([
            'status' => 'required|in:active,blocked,whitelisted',
        ]);

        // Store old status
        $oldStatus = $device->status;
        $newStatus = $request->input('status');

        // Update device status
        $device->update(['status' => $newStatus]);

        // Sync network-level blocking if status changed
        if ($oldStatus !== $newStatus) {
            if ($newStatus === 'blocked') {
                $this->networkService->blockDevice($device);
            } elseif ($newStatus === 'active') {
                $this->networkService->unblockDevice($device);
            } elseif ($newStatus === 'whitelisted') {
                $this->networkService->whitelistDevice($device);
            }
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Device status updated successfully!',
                'status' => $newStatus,
            ]);
        }

        // Redirect for regular form submissions
        return redirect()->back()
            ->with('success', 'Device status updated successfully!');
    }

    /**
     * Update device time allocation.
     * 
     * Route: POST /devices/{device}/time
     * 
     * This method allows updating time allocation without full form submission.
     * Used by quick action buttons or AJAX requests.
     * 
     * What It Does:
     * 1. Validates time values
     * 2. Checks authorization
     * 3. Updates time allocation
     * 4. Returns JSON response (for AJAX) or redirects
     * 
     * @param Request $request HTTP request containing time fields
     * @param Device $device The device to update
     * @return JsonResponse|RedirectResponse JSON response for AJAX, redirect for regular requests
     * 
     * Usage:
     * - AJAX: POST /devices/1/time with {remaining_time_minutes: 60, total_time_allocated: 120}
     * - Form: Submit form with time fields
     */
    public function updateTimeAllocation(Request $request, Device $device): JsonResponse|RedirectResponse
    {
        // Check authorization
        $this->authorize('update', $device);

        // Validate time values
        $request->validate([
            'remaining_time_minutes' => 'nullable|integer|min:0|max:9999',
            'total_time_allocated' => 'nullable|integer|min:0|max:9999',
        ]);

        // Update time allocation
        $device->update([
            'remaining_time_minutes' => $request->input('remaining_time_minutes', $device->remaining_time_minutes),
            'total_time_allocated' => $request->input('total_time_allocated', $device->total_time_allocated),
        ]);

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Time allocation updated successfully!',
                'remaining_time_minutes' => $device->remaining_time_minutes,
                'total_time_allocated' => $device->total_time_allocated,
            ]);
        }

        // Redirect for regular form submissions
        return redirect()->back()
            ->with('success', 'Time allocation updated successfully!');
    }

    /**
     * Get real-time connected devices (AJAX endpoint).
     * 
     * Route: GET /devices/api/connected
     * 
     * This method returns a list of devices currently connected to the network.
     * Used by the UI to show connection status in real-time.
     * 
     * @return JsonResponse JSON response with connected devices array
     * 
     * Usage:
     * - AJAX: GET /devices/api/connected
     * - Returns: {success: true, devices: [{mac_address: "...", ip_address: "...", ...}]}
     */
    public function getConnectedDevices(): JsonResponse
    {
        // Get connected devices from network
        // getConnectedDevices() queries network to find devices currently connected
        $connectedDevices = $this->networkService->getConnectedDevices();

        // Return JSON response
        return response()->json([
            'success' => true,
            'devices' => $connectedDevices,
        ]);
    }

    /**
     * Get time usage data for a device (aggregated by month).
     * 
     * This is a helper method used by index() to calculate time usage graph data.
     * 
     * What It Does:
     * 1. Queries DeviceSession table for this device
     * 2. Groups sessions by month (JAN to DEC)
     * 3. Calculates total hours per month
     * 4. Returns array with month => hours mapping
     * 
     * @param Device $device The device to get time usage for
     * @return array<string, float> Array with month abbreviations as keys, hours as values
     * 
     * Usage:
     * Called internally by index() method to populate time usage graph.
     */
    protected function getTimeUsageData(Device $device): array
    {
        // Query DeviceSession table for this device
        // where('device_id', $device->id) filters to this device only
        // whereNotNull('ended_at') only includes completed sessions (not active ones)
        // selectRaw() calculates total hours per month
        // Use database-agnostic approach for month extraction
        $driver = DB::connection()->getDriverName();
        
        if (in_array($driver, ['mysql', 'mariadb'])) {
            // MySQL/MariaDB: Use MONTH() function
            $sessions = DB::table('device_sessions')
                ->where('device_id', $device->id)
                ->whereNotNull('ended_at') // Only completed sessions
                ->selectRaw('MONTH(started_at) as month, SUM(duration_seconds / 3600.0) as total_hours')
                ->groupBy('month')
                ->get();
        } else {
            // SQLite: Use strftime() function
            $sessions = DB::table('device_sessions')
                ->where('device_id', $device->id)
                ->whereNotNull('ended_at') // Only completed sessions
                ->selectRaw('CAST(strftime("%m", started_at) AS INTEGER) as month, SUM(duration_seconds / 3600.0) as total_hours')
                ->groupBy('month')
                ->get();
        }

        // Initialize array with all months set to 0
        // This ensures all months appear in graph even if no data
        $timeUsageData = [];
        $monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        foreach ($monthNames as $index => $month) {
            $timeUsageData[$month] = 0.0;
        }

        // Populate array with actual data
        foreach ($sessions as $session) {
            $monthIndex = (int) $session->month - 1; // Convert to 0-based index
            if (isset($monthNames[$monthIndex])) {
                $timeUsageData[$monthNames[$monthIndex]] = (float) $session->total_hours;
            }
        }

        return $timeUsageData;
    }

    /**
     * Get quiz scores for a device.
     * 
     * This is a helper method used by index() to calculate quiz scores.
     * 
     * What It Does:
     * 1. Queries QuizAttempt table for this device
     * 2. Groups attempts by quiz
     * 3. Calculates scores (correct/total)
     * 4. Returns array with quiz data
     * 
     * @param Device $device The device to get quiz scores for
     * @return array<int, array<string, mixed>> Array of quiz score data
     * 
     * Usage:
     * Called internally by index() method to populate quiz scores list.
     */
    protected function getQuizScores(Device $device): array
    {
        // Query QuizAttempt table for this device
        // with('quiz') eager loads quiz data to avoid N+1 queries
        // orderBy('completed_at', 'desc') orders by most recent first
        $attempts = $device->quizAttempts()
            ->with('quiz')
            ->orderBy('completed_at', 'desc')
            ->get();

        // Group attempts by quiz and calculate scores
        $quizScores = [];
        foreach ($attempts as $attempt) {
            $quizId = $attempt->quiz_id;
            $quizTitle = $attempt->quiz->title ?? 'Unknown Quiz';

            // Get total questions from quiz
            $questions = $attempt->quiz->questions['questions'] ?? [];
            $totalQuestions = count($questions);

            // Calculate correct answers
            // score is stored as percentage, so correct = (score / 100) * total
            $correctAnswers = (int) round(($attempt->score / 100) * $totalQuestions);

            // Store quiz score data
            $quizScores[] = [
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'score' => $attempt->score,
                'correct' => $correctAnswers,
                'total' => $totalQuestions,
                'passed' => $attempt->passed,
                'completed_at' => $attempt->completed_at,
            ];
        }

        return $quizScores;
    }

    /**
     * Get website history for a device.
     * 
     * This is a helper method used by index() to get recently visited websites.
     * 
     * What It Does:
     * 1. Queries BrowsingLog table for this device
     * 2. Gets unique domains (no duplicates)
     * 3. Orders by most recent visit
     * 4. Returns array with domain names
     * 
     * @param Device $device The device to get website history for
     * @return array<string> Array of unique domain names
     * 
     * Usage:
     * Called internally by index() method to populate website history list.
     */
    protected function getWebsiteHistory(Device $device): array
    {
        // Query BrowsingLog table for this device
        // select('domain') only gets domain column (not full URL)
        // distinct() removes duplicates (same domain visited multiple times)
        // orderBy('visited_at', 'desc') orders by most recent first
        // limit(20) only gets top 20 most recent domains
        // pluck('domain') extracts domain values into array
        return $device->browsingLogs()
            ->select('domain')
            ->distinct()
            ->orderBy('visited_at', 'desc')
            ->limit(20)
            ->pluck('domain')
            ->toArray();
    }

    /**
     * Display the Blocklist page.
     * 
     * Route: GET /accounts/blocklist
     * 
     * This page shows all blocked devices and allows managing the blocklist.
     * 
     * @return View The blocklist page
     */
    public function blocklist(): View
    {
        // Get all blocked devices for the authenticated user
        $devices = Auth::user()->devices()
            ->where('status', 'blocked')
            ->orderBy('name')
            ->get();

        return view('devices.device_blocklist', compact('devices'));
    }

    /**
     * Display the Whitelist page.
     * 
     * Route: GET /accounts/whitelist
     * 
     * This page shows all whitelisted devices and allows managing the whitelist.
     * 
     * @return View The whitelist page
     */
    public function whitelist(): View
    {
        // Get all whitelisted devices for the authenticated user
        $devices = Auth::user()->devices()
            ->where('status', 'whitelisted')
            ->orderBy('name')
            ->get();

        return view('devices.device_whitelist', compact('devices'));
    }

    /**
     * Update device role.
     * 
     * Route: POST /accounts/{device}/update-role
     * 
     * This method updates the role of a device (CHILD, GUEST, PARENT).
     * Called from the accounts page dropdown.
     * 
     * @param Request $request HTTP request containing the new role
     * @param Device $device The device to update
     * @return RedirectResponse Redirects back with success message
     */
    public function updateRole(Request $request, Device $device): RedirectResponse
    {
        // Check authorization - user must own the device
        $this->authorize('update', $device);

        // Validate the role
        $request->validate([
            'role' => ['required', 'string', 'in:child,guest,parent'],
        ]);

        // Update the device role
        $device->role = $request->input('role');
        $device->save();

        // Redirect back with success message
        return redirect()->route('accounts.index')
            ->with('success', 'Device role updated successfully.');
    }
}

