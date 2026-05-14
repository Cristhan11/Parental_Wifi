<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use App\Models\DeviceRegistrationRequest;
use App\Models\Quiz;
use App\Support\AuditRequestSummary;
use App\Support\DeviceAuditSummary;
use App\Services\BandwidthUsageService;
use App\Services\ChildDeviceConnectionRestoreService;
use App\Services\DeviceService;
use App\Services\NetworkService;
use App\Services\TimeTrackingService;
use App\Services\UsageChartService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
     */
    protected DeviceService $deviceService;

    /**
     * NetworkService instance for network-level operations.
     *
     * NetworkService handles:
     * - Blocking/unblocking devices at network level
     * - Getting connected devices
     * - Checking network status
     */
    protected NetworkService $networkService;

    /**
     * TimeTrackingService instance for time management.
     *
     * TimeTrackingService handles:
     * - Calculating remaining time
     * - Checking time expiration
     * - Managing time allocation
     */
    protected TimeTrackingService $timeTrackingService;

    /**
     * Shared bucket/overlap logic for dashboard and per-device child stats charts.
     */
    protected UsageChartService $usageChartService;

    protected BandwidthUsageService $bandwidthUsageService;

    protected ChildDeviceConnectionRestoreService $childDeviceConnectionRestoreService;

    /**
     * Constructor - Called automatically when controller is created.
     *
     * Laravel's dependency injection automatically provides these services.
     * This is called "dependency injection" - Laravel creates the services for us.
     *
     * @param  DeviceService  $deviceService  Device management utilities
     * @param  NetworkService  $networkService  Network-level operations
     * @param  TimeTrackingService  $timeTrackingService  Time management
     * @param  UsageChartService  $usageChartService  Per-device / dashboard usage chart payload
     * @param  BandwidthUsageService  $bandwidthUsageService  Per-device bandwidth chart payload
     * @param  ChildDeviceConnectionRestoreService  $childDeviceConnectionRestoreService  Captive unblock + nds auth after provisioning
     */
    public function __construct(
        DeviceService $deviceService,
        NetworkService $networkService,
        TimeTrackingService $timeTrackingService,
        UsageChartService $usageChartService,
        BandwidthUsageService $bandwidthUsageService,
        ChildDeviceConnectionRestoreService $childDeviceConnectionRestoreService
    ) {
        $this->deviceService = $deviceService;
        $this->networkService = $networkService;
        $this->timeTrackingService = $timeTrackingService;
        $this->usageChartService = $usageChartService;
        $this->bandwidthUsageService = $bandwidthUsageService;
        $this->childDeviceConnectionRestoreService = $childDeviceConnectionRestoreService;
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

        $pendingRegistrationCount = DeviceRegistrationRequest::query()
            ->where('status', 'pending')
            ->count();

        // Return the accounts view with devices data
        // compact('devices') creates ['devices' => $devices] array
        // View will display devices in a table matching Image 4 design
        return view('devices.accounts', compact('devices', 'pendingRegistrationCount'));
    }

    public function requestRegistrationForm(): View
    {
        return view('devices.request_registration');
    }

    public function submitRegistrationRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $rawIp = (string) $request->ip();
        $ipAddress = filter_var($rawIp, FILTER_VALIDATE_IP) ? $rawIp : null;
        $source = (string) ($request->userAgent() ?? 'unknown');
        $rawMac = $request->header('X-Device-Mac') ?: session('device_mac');
        $macAddress = $rawMac ? $this->deviceService->normalizeMacAddress(trim((string) $rawMac)) : null;
        $hostname = $ipAddress ? gethostbyaddr($ipAddress) : null;

        $fingerprint = sha1(
            strtolower(trim((string) $macAddress)).'|'.strtolower((string) $hostname).'|'.$ipAddress.'|'.strtolower($source)
        );

        $limiterKey = 'device-registration-request:'.$fingerprint;
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->withErrors([
                'device_name' => 'Too many repeated registration attempts from this device. Please wait before retrying.',
            ])->withInput();
        }
        RateLimiter::hit($limiterKey, 3600);

        $existing = DeviceRegistrationRequest::query()
            ->where('fingerprint', $fingerprint)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            $existing->update([
                'device_name' => $validated['device_name'],
                'mac_address' => $macAddress,
                'hostname' => $hostname,
                'ip_address' => $ipAddress,
                'request_source' => $source,
                'seen_on_home_wifi' => $this->isPrivateIp($ipAddress),
                'requests_count' => $existing->requests_count + 1,
                'last_requested_at' => now(),
            ]);
        } else {
            DeviceRegistrationRequest::create([
                'device_name' => $validated['device_name'],
                'mac_address' => $macAddress,
                'hostname' => $hostname,
                'ip_address' => $ipAddress,
                'request_source' => $source,
                'fingerprint' => $fingerprint,
                'status' => 'pending',
                'seen_on_home_wifi' => $this->isPrivateIp($ipAddress),
                'last_requested_at' => now(),
            ]);
        }

        return back()->with('success', 'Registration request submitted. A Parent Owner will review it soon.');
    }

    public function approveRegistrationRequest(Request $request, DeviceRegistrationRequest $registrationRequest): RedirectResponse
    {
        $allowedInitialTimes = range(5, 480, 5);
        $request->validate([
            'assigned_role' => ['required', 'string', 'in:child,parent,guest'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'initial_time_minutes' => ['nullable', 'integer', 'in:'.implode(',', $allowedInitialTimes)],
        ]);

        abort_if($registrationRequest->status !== 'pending', 422, 'This request is no longer pending.');

        $assignedRole = $request->string('assigned_role')->toString();
        $deviceName = trim((string) $request->input('device_name', $registrationRequest->device_name));
        if ($deviceName === '') {
            $deviceName = $registrationRequest->device_name;
        }
        $initialTime = (int) $request->input('initial_time_minutes', 60);
        if (! in_array($initialTime, $allowedInitialTimes, true)) {
            $initialTime = 60;
        }
        $status = in_array($assignedRole, ['parent', 'guest'], true) ? 'whitelisted' : 'active';
        $macHex = strtoupper(substr(preg_replace('/[^a-f0-9]/i', '', $registrationRequest->fingerprint), 0, 12));
        $fallbackMac = implode(':', str_split(str_pad($macHex, 12, '0'), 2));
        $mac = $registrationRequest->mac_address ?: $fallbackMac;

        $device = Device::create([
            'user_id' => Auth::id(),
            'name' => $deviceName,
            'mac_address' => $this->deviceService->normalizeMacAddress($mac),
            'role' => $assignedRole,
            'status' => $status,
            'ip_address' => $registrationRequest->ip_address,
            'remaining_time_minutes' => $assignedRole === 'child' ? $initialTime : 0,
            'total_time_allocated' => $assignedRole === 'child' ? $initialTime : 0,
        ]);

        $registrationRequest->update([
            'status' => 'approved',
            'assigned_role' => $assignedRole,
            'device_name' => $deviceName,
            'user_id' => Auth::id(),
        ]);

        $this->childDeviceConnectionRestoreService->tryRestoreAfterDeviceProvisioned($device->fresh());

        $session = [
            'success' => 'Device request approved.',
        ];
        if ($this->clientIpMatchesDevicePresence($request, $device->fresh())) {
            $session['device_restore_portal_mac'] = $device->mac_address;
        }

        return redirect()->route('accounts.index')->with($session);
    }

    public function rejectRegistrationRequest(DeviceRegistrationRequest $registrationRequest): RedirectResponse
    {
        abort_if($registrationRequest->status !== 'pending', 422, 'This request is no longer pending.');

        $registrationRequest->update([
            'status' => 'rejected',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('accounts.create')->with('success', 'Device request disapproved.');
    }

    /**
     * Display the Child Devices stats view (Image 3).
     *
     * Route: GET /child_devices or GET /child_devices/{device}
     *
     * This view shows statistics for a selected device:
     * - TIME USAGE graph (daily / weekly / monthly / yearly via usage-chart JSON)
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
     * @param  Request  $request  HTTP request (may contain 'device' query parameter)
     * @param  Device|null  $device  Optional device to show stats for (from route parameter)
     * @return View The child devices stats view
     *
     * Usage:
     * - GET /child_devices - Shows stats for first eligible child device (or empty if none)
     * - GET /child_devices?device=1 - Shows stats for device 1 if it is a child device (not parent/guest, not whitelisted)
     * - GET /child_devices/1 - Same, via route parameter
     */
    public function index(Request $request, ?Device $device = null): View
    {
        // Child stats page: only devices that are role child (not parent/guest) and not whitelisted,
        // same rule as the dashboard TIME USAGE card (see Device::scopeForDashboardTimeUsage).
        $devices = Auth::user()->devices()
            ->forDashboardTimeUsage()
            ->orderBy('name')
            ->get();

        // Route model binding (e.g. /child_devices/1): ignore parent/guest/whitelisted selections
        if ($device && ! Auth::user()->devices()->forDashboardTimeUsage()->whereKey($device->getKey())->exists()) {
            $device = null;
        }

        // Query parameter format: /child_devices?device=1
        if (! $device && $request->has('device')) {
            $deviceId = $request->input('device');
            $device = Auth::user()->devices()
                ->forDashboardTimeUsage()
                ->whereKey($deviceId)
                ->first();
        }

        // If no device specified, use first eligible child device (or null if none)
        if (! $device && $devices->isNotEmpty()) {
            $device = $devices->first();
        }

        // If device is specified, check authorization
        // $this->authorize() calls DevicePolicy::view() to check if user owns device
        // If user doesn't own device, Laravel throws 403 Forbidden
        if ($device) {
            $this->authorize('view', $device);
        }

        // Initialize statistics arrays (will be populated if device exists)
        $quizScores = [];
        $websiteHistory = [];

        // If device exists, calculate statistics
        if ($device) {
            $device->load([
                'quizzes' => fn ($q) => $q
                    ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
                    ->orderBy('title'),
                'videos' => fn ($q) => $q->orderBy('title'),
            ]);

            // Get quiz scores for this device
            // Query QuizAttempt table and calculate scores
            $quizScores = $this->getQuizScores($device);

            // Get website history (recently visited websites)
            // Query BrowsingLog table for this device
            $websiteHistory = $this->getWebsiteHistory($device);
        }

        // Return the child_devices view with all data
        // compact() creates array with all variables
        return view('devices.child_devices', compact('devices', 'device', 'quizScores', 'websiteHistory'));
    }

    /**
     * JSON for the Child Devices page time-usage line chart (single selected device).
     *
     * Reuses {@see UsageChartService} so buckets and active-session rules match the dashboard graph.
     */
    public function childDeviceUsageChart(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $range = strtolower((string) $request->query('range', 'yearly'));
        if (! in_array($range, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $range = 'yearly';
        }

        $payload = $this->usageChartService->buildChartPayload(
            $request->user(),
            $range,
            (int) $device->id
        );

        return response()->json($payload);
    }

    /**
     * JSON for the Child Devices page bandwidth line chart (single selected device).
     */
    public function childDeviceBandwidthChart(Request $request, Device $device): JsonResponse
    {
        $this->authorize('view', $device);

        $range = strtolower((string) $request->query('range', 'yearly'));
        if (! in_array($range, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $range = 'yearly';
        }

        $displayUnit = strtolower((string) $request->query('display_unit', 'gb'));
        if (! in_array($displayUnit, ['gb', 'mb'], true)) {
            $displayUnit = 'gb';
        }

        $payload = $this->bandwidthUsageService->buildChartPayload(
            $request->user(),
            $range,
            (int) $device->id,
            $displayUnit
        );

        return response()->json($payload);
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
        // Standard create flow intentionally hides manual MAC input.
        return $this->renderCreateView(false);
    }

    public function createAdvanced(): View
    {
        // Advanced/debug path keeps manual MAC workflow.
        return $this->renderCreateView(true);
    }

    private function renderCreateView(bool $advancedMode): View
    {
        // Check authorization - can user create devices?
        // $this->authorize() calls DevicePolicy::create()
        // In our system, all authenticated users can create devices
        $this->authorize('create', Device::class);

        $connectedDevices = [];

        if ($advancedMode) {
            // Get connected devices from network (optional helper)
            // This shows available MAC addresses that parents can register
            // getConnectedDevices() returns array of devices currently on network
            $connectedDevices = $this->networkService->getConnectedDevices();

            // Only suggest devices not already registered for this parent (match by normalized MAC)
            $registeredMacs = Auth::user()->devices()
                ->pluck('mac_address')
                ->map(fn (string $mac) => $this->deviceService->normalizeMacAddress($mac))
                ->all();

            $deviceService = $this->deviceService;
            $connectedDevices = array_values(array_filter(
                $connectedDevices,
                function (array $row) use ($registeredMacs, $deviceService): bool {
                    $raw = $row['mac_address'] ?? '';
                    if ($raw === '') {
                        return true;
                    }

                    $normalized = $deviceService->normalizeMacAddress($raw);

                    return ! in_array($normalized, $registeredMacs, true);
                }
            ));
        }

        // Return the create view
        // compact('connectedDevices') passes connected devices to view
        // View can display these as suggestions for MAC addresses
        $pendingRegistrationRequests = DeviceRegistrationRequest::query()
            ->where('status', 'pending')
            ->latest('last_requested_at')
            ->limit(30)
            ->get();

        return view('devices.device_create', compact('connectedDevices', 'advancedMode', 'pendingRegistrationRequests'));
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
     * @param  StoreDeviceRequest  $request  Validated form data (name, mac_address, status, etc.)
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

        // Standard flow does not require manual MAC input.
        // When missing, generate a synthetic MAC reserved for local app records.
        if (! empty($validated['mac_address'])) {
            $validated['mac_address'] = $this->deviceService->normalizeMacAddress($validated['mac_address']);
        } else {
            $validated['mac_address'] = $this->generateSyntheticMac();
        }

        // Set default time allocation if not provided
        // Default is 15 minutes for new devices
        if (! isset($validated['remaining_time_minutes'])) {
            $validated['remaining_time_minutes'] = 15; // Default 15 minutes
        }

        // Set total_time_allocated to match remaining_time_minutes if not provided
        // This tracks the total time allocated for reporting purposes
        if (! isset($validated['total_time_allocated'])) {
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

        $this->attachProvisioningClientIpWhenLanArpMatches($request, $device, $validated);

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

        $this->childDeviceConnectionRestoreService->tryRestoreAfterDeviceProvisioned($device->fresh());

        $session = ['success' => 'Device created successfully!'];
        if ($this->clientIpMatchesDevicePresence($request, $device->fresh())) {
            $session['device_restore_portal_mac'] = $device->mac_address;
        }

        AuditRequestSummary::set($request, 'Added "'.$device->name.'"');

        return redirect()->route('accounts.index')->with($session);
    }

    /**
     * When adding a whitelisted parent/guest from Advanced Options, if the gateway-reported
     * connected list shows this device's MAC using the same IP as the browser submitting the
     * form, persist that IP so the portal countdown redirect matches quiz/video behavior.
     */
    private function attachProvisioningClientIpWhenLanArpMatches(Request $request, Device $device, array $validated): void
    {
        $role = strtolower((string) ($validated['role'] ?? 'child'));
        $status = strtolower((string) ($validated['status'] ?? ''));
        if ($status !== 'whitelisted' || ! in_array($role, ['parent', 'guest'], true)) {
            return;
        }

        if (! $request->boolean('advanced_mode')) {
            return;
        }

        $clientIp = trim((string) ($request->ip() ?: ''));
        if ($clientIp === '' || filter_var($clientIp, FILTER_VALIDATE_IP) === false) {
            return;
        }

        $isLoopback = in_array($clientIp, ['127.0.0.1', '::1'], true);
        if ($isLoopback && ! app()->environment(['local', 'testing'])) {
            return;
        }

        $deviceMac = $this->deviceService->normalizeMacAddress((string) ($device->mac_address ?? ''));

        try {
            foreach ($this->networkService->getConnectedDevices() as $row) {
                $rawMac = $row['mac_address'] ?? '';
                if ($rawMac === '') {
                    continue;
                }
                if ($this->deviceService->normalizeMacAddress((string) $rawMac) !== $deviceMac) {
                    continue;
                }
                $rowIp = trim((string) ($row['ip_address'] ?? ''));
                if ($rowIp !== '' && $rowIp === $clientIp) {
                    $device->update(['ip_address' => $clientIp]);

                    return;
                }
            }
        } catch (\Exception $e) {
            // Non-fatal: list may be unavailable in dev or on misconfigured gateways.
        }
    }

    /**
     * True when the HTTP client address matches the device's last known IP (e.g. the same
     * handset that submitted a registration request is approving on the same network).
     * Used to trigger the same portal redirect countdown as quiz/video success.
     */
    private function clientIpMatchesDevicePresence(Request $request, Device $device): bool
    {
        $clientIp = trim((string) ($request->ip() ?: ''));
        $deviceIp = trim((string) ($device->ip_address ?? ''));

        return $clientIp !== '' && $deviceIp !== '' && $clientIp === $deviceIp;
    }

    private function generateSyntheticMac(): string
    {
        // Use locally-administered prefix so generated values are clearly synthetic.
        // 02 indicates locally administered, unicast MAC.
        do {
            $suffix = strtoupper(Str::random(10));
            $suffix = preg_replace('/[^A-F0-9]/', 'A', $suffix);
            $mac = implode(':', str_split('02'.substr(str_pad($suffix, 10, '0'), 0, 10), 2));
        } while (Device::where('mac_address', $mac)->exists());

        return $mac;
    }

    /**
     * Show the form for editing the specified device.
     *
     * Route: GET /devices/{device}/edit
     *
     * Displays a form pre-filled with existing device data.
     * Also shows device statistics (connection status, sessions, logs).
     *
     * @param  Device  $device  The device to edit (Laravel automatically finds it by ID from URL)
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

        $portalFavoriteQuizzes = collect();
        $portalFavoriteVideos = collect();
        if (($device->role ?? 'child') === 'child') {
            $portalFavoriteQuizzes = $device->quizzes()
                ->where('is_active', true)
                ->where('title', '!=', Quiz::RANDOM_MODE_SETTINGS_TITLE)
                ->orderBy('title')
                ->get(['quizzes.id', 'quizzes.title']);
            $portalFavoriteVideos = $device->videos()
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['videos.id', 'videos.title']);
        }

        // Return the edit view with device data and statistics
        return view('devices.device_edit', compact(
            'device',
            'stats',
            'isConnected',
            'deviceIp',
            'portalFavoriteQuizzes',
            'portalFavoriteVideos'
        ));
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
     * 6. Refreshes network + captive portal when the device should have access (same as quiz/video grants),
     *    except when the parent is newly setting status to blocked (intentional block must stick).
     * 7. Redirects with success message
     *
     * @param  UpdateDeviceRequest  $request  Validated form data
     * @param  Device  $device  The device to update (found by ID from URL)
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

        $beforeSnapshot = $device->only([
            'name',
            'mac_address',
            'role',
            'status',
            'remaining_time_minutes',
            'total_time_allocated',
            'preferred_quiz_id',
            'preferred_video_id',
        ]);

        // Store old status to check if it changed
        // If status changes, we need to update network-level blocking
        $oldStatus = $device->status;

        // Normalize MAC address to standard format
        $validated['mac_address'] = $this->deviceService->normalizeMacAddress($validated['mac_address']);

        $previousRemainingMinutes = (int) ($device->remaining_time_minutes ?? 0);

        // Update device in database
        // update() saves changes to database
        $device->update($validated);

        if (array_key_exists('remaining_time_minutes', $validated) && ! $device->isWhitelisted()) {
            $newRemainingMinutes = (int) $validated['remaining_time_minutes'];
            if ($newRemainingMinutes !== $previousRemainingMinutes || $device->hasUnbilledActiveSessionTime()) {
                $device->resetActiveSessionBillingAnchorToNow();
            }
        }

        // Sync network-level blocking if status changed
        // This ensures database status matches network status
        if ($oldStatus !== $device->status) {
            if ($oldStatus === 'whitelisted' && $device->status !== 'whitelisted') {
                $this->networkService->removeWhitelistAcceptRules($device->fresh());
            }
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

        $fresh = $device->fresh();
        $intentionalNewBlock = ($oldStatus !== 'blocked' && $fresh->status === 'blocked');

        $session = ['success' => 'Device updated successfully!'];

        if (! $intentionalNewBlock) {
            try {
                $this->childDeviceConnectionRestoreService->tryRestoreAfterDeviceProvisioned($fresh);
            } catch (\Throwable $e) {
                Log::debug('tryRestoreAfterDeviceProvisioned after device update failed (non-fatal)', [
                    'device_id' => $fresh->id,
                    'mac_address' => $fresh->mac_address,
                    'error' => $e->getMessage(),
                ]);
            }

            $afterRestore = $device->fresh();
            if ($this->clientIpMatchesDevicePresence($request, $afterRestore)) {
                $session['device_restore_portal_mac'] = $afterRestore->mac_address;
            }
        }

        $device->refresh();
        AuditRequestSummary::set($request, DeviceAuditSummary::describeFullEdit($beforeSnapshot, $device));

        return redirect()->route('accounts.index')->with($session);
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
     * @param  Device  $device  The device to delete (found by ID from URL)
     * @return RedirectResponse Redirects to accounts view with success message
     *
     * Usage:
     * User clicks delete button, this method removes the device.
     */
    public function destroy(Request $request, Device $device): RedirectResponse
    {
        // Check authorization - does user own this device?
        $this->authorize('delete', $device);

        AuditRequestSummary::set($request, 'Removed "'.$device->name.'"');

        // Unblock device at network level before deletion
        // This removes iptables rules so device can access internet again
        // Important: Do this before deletion so we can still access device data
        if ($device->status === 'blocked') {
            $this->networkService->unblockDevice($device);
        } elseif ($device->status === 'whitelisted') {
            $this->networkService->removeWhitelistAcceptRules($device);
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
     * @param  Request  $request  HTTP request containing 'status' field
     * @param  Device  $device  The device to update
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
            if ($oldStatus === 'whitelisted' && $newStatus !== 'whitelisted') {
                $this->networkService->removeWhitelistAcceptRules($device->fresh());
            }
            if ($newStatus === 'blocked') {
                $this->networkService->blockDevice($device);
            } elseif ($newStatus === 'active') {
                $this->networkService->unblockDevice($device);
            } elseif ($newStatus === 'whitelisted') {
                $this->networkService->whitelistDevice($device);
            }
        }

        $human = match ($newStatus) {
            'blocked' => 'blocked',
            'active' => 'normal (timed)',
            'whitelisted' => 'unlimited',
            default => (string) $newStatus,
        };
        $dn = $device->fresh()->name;
        AuditRequestSummary::set(
            $request,
            $oldStatus !== $newStatus
                ? 'Internet set to '.$human.' for '.$dn
                : 'Confirmed internet setting for '.$dn
        );

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
     * @param  Request  $request  HTTP request containing time fields
     * @param  Device  $device  The device to update
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

        $oldRemaining = (int) ($device->remaining_time_minutes ?? 0);
        $oldTotal = (int) ($device->total_time_allocated ?? 0);
        $previousRemainingMinutes = $oldRemaining;

        $newRemainingMinutes = (int) $request->input('remaining_time_minutes', $previousRemainingMinutes);

        // Update time allocation
        $device->update([
            'remaining_time_minutes' => $request->input('remaining_time_minutes', $device->remaining_time_minutes),
            'total_time_allocated' => $request->input('total_time_allocated', $device->total_time_allocated),
        ]);

        $freshDevice = $device->fresh();
        if (! $freshDevice->isWhitelisted() && $request->has('remaining_time_minutes')
            && ($newRemainingMinutes !== $previousRemainingMinutes || $freshDevice->hasUnbilledActiveSessionTime())) {
            $freshDevice->resetActiveSessionBillingAnchorToNow();
        }

        $device->refresh();
        $dn = $device->name;
        $nRem = (int) $device->remaining_time_minutes;
        $nTot = (int) $device->total_time_allocated;
        if ($nRem !== $oldRemaining && $nTot !== $oldTotal) {
            AuditRequestSummary::set($request, 'Set time left to '.$nRem.' min, allowance to '.$nTot.' min for '.$dn);
        } elseif ($nRem !== $oldRemaining) {
            AuditRequestSummary::set($request, 'Set time left to '.$nRem.' min for '.$dn);
        } elseif ($nTot !== $oldTotal) {
            AuditRequestSummary::set($request, 'Set allowance to '.$nTot.' min for '.$dn);
        } else {
            AuditRequestSummary::set($request, 'Confirmed time settings for '.$dn);
        }

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
     * @param  Device  $device  The device to get quiz scores for
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
            $quizTitle = $attempt->quiz?->title ?? 'Unknown Quiz';

            $totalFromAttempt = (int) ($attempt->total_questions ?? 0);
            if ($totalFromAttempt > 0) {
                $totalQuestions = $totalFromAttempt;
                $correctAnswers = max(0, min($totalQuestions, (int) ($attempt->correct_count ?? 0)));
            } else {
                $questions = $attempt->quiz?->questions['questions'] ?? [];
                if (! is_array($questions)) {
                    $questions = [];
                }
                $totalQuestions = count($questions);
                $correctAnswers = $totalQuestions > 0
                    ? (int) max(0, min($totalQuestions, round($attempt->score / 100 * $totalQuestions)))
                    : 0;
            }

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
     * It queries the BrowsingLog table to get actual browsing history records.
     *
     * What It Does:
     * 1. Queries BrowsingLog table for this device
     * 2. Gets the most recent browsing logs (limit 10-15)
     * 3. Orders by visited_at DESC (newest first)
     * 4. Returns collection of BrowsingLog models (not just domain strings)
     *
     * Why Limit to 10-15 Records?
     * - The Child Devices page shows a summary view, not full history
     * - Too many records would clutter the UI
     * - Full history is available via the "Browsing History" button
     * - Improves page load performance
     *
     * @param  Device  $device  The device to get website history for
     * @return \Illuminate\Database\Eloquent\Collection Collection of BrowsingLog models
     *
     * Usage:
     * Called internally by index() method to populate website history section.
     * The view will display these logs with domain, URL, and visited_at timestamp.
     */
    protected function getWebsiteHistory(Device $device)
    {
        // Query BrowsingLog table for this device
        // orderBy('visited_at', 'desc') orders by most recent first
        // limit(15) only gets top 15 most recent browsing logs
        // get() returns a collection of BrowsingLog models
        // This allows the view to access full log data (URL, domain, visited_at, etc.)
        return $device->browsingLogs()
            ->orderBy('visited_at', 'desc')
            ->limit(15)
            ->get();
    }

    private function isPrivateIp(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
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
     * @param  Request  $request  HTTP request containing the new role
     * @param  Device  $device  The device to update
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

        AuditRequestSummary::set($request, 'Role set to '.$request->input('role').' for '.$device->name);

        // Redirect back with success message
        return redirect()->route('accounts.index')
            ->with('success', 'Device role updated successfully.');
    }
}
