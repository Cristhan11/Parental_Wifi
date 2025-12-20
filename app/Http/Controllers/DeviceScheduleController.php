<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceScheduleRequest;
use App\Http\Requests\UpdateDeviceScheduleRequest;
use App\Models\DeviceSchedule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Device Schedule Controller
 * 
 * This controller handles all device schedule management operations for parents.
 * Schedules define time-based internet access rules for child devices.
 * 
 * Key Responsibilities:
 * 1. List schedules (filterable by device, day of week)
 * 2. Create new schedules
 * 3. Update existing schedules
 * 4. Delete schedules
 * 
 * Authorization:
 * - All methods require authentication (user must be logged in)
 * - DeviceSchedulePolicy ensures users can only manage schedules for their own devices
 * - Uses $this->authorize() to check permissions before operations
 * 
 * Schedule Enforcement:
 * - The EnforceSchedules background job automatically enforces these schedules
 * - Job runs every 1 minute to check and enforce time-based rules
 * - Devices are blocked/unblocked based on schedule rules
 */
class DeviceScheduleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of device schedules.
     * 
     * Route: GET /schedules
     * 
     * Shows all schedules for the authenticated user's devices.
     * Filterable by device and day of week.
     * 
     * @param Request $request HTTP request (may contain filter parameters)
     * @return View The schedules index view
     */
    public function index(Request $request): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        // Build query for schedules
        $query = DeviceSchedule::whereHas('device', function ($q) {
            $q->where('user_id', Auth::id());
        })->with('device');

        // Filter by device if provided
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by day of week if provided
        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filter by active/inactive if provided
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        // Order by device name, then day of week, then start time
        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(20);

        return view('schedules.index', compact('schedules', 'devices'));
    }

    /**
     * Show the form for creating a new schedule.
     * 
     * Route: GET /schedules/create
     * 
     * @return View The create schedule form
     */
    public function create(): View
    {
        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        return view('schedules.create', compact('devices'));
    }

    /**
     * Store a newly created schedule.
     * 
     * Route: POST /schedules
     * 
     * Creates a new schedule for a device.
     * 
     * @param StoreDeviceScheduleRequest $request Validated form request
     * @return RedirectResponse Redirect to index with success message
     */
    public function store(StoreDeviceScheduleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Create schedule
        DeviceSchedule::create($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule created successfully.');
    }

    /**
     * Show the form for editing the specified schedule.
     * 
     * Route: GET /schedules/{schedule}/edit
     * 
     * @param DeviceSchedule $schedule The schedule to edit (route model binding)
     * @return View The edit schedule form
     */
    public function edit(DeviceSchedule $schedule): View
    {
        // Check authorization
        $this->authorize('update', $schedule);

        // Get all devices for the authenticated user
        $devices = Auth::user()->devices()->orderBy('name')->get();

        return view('schedules.edit', compact('schedule', 'devices'));
    }

    /**
     * Update the specified schedule.
     * 
     * Route: PUT /schedules/{schedule}
     * 
     * Updates a schedule.
     * 
     * @param UpdateDeviceScheduleRequest $request Validated form request
     * @param DeviceSchedule $schedule The schedule to update (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function update(UpdateDeviceScheduleRequest $request, DeviceSchedule $schedule): RedirectResponse
    {
        // Check authorization
        $this->authorize('update', $schedule);

        $validated = $request->validated();

        // Update schedule
        $schedule->update($validated);

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified schedule.
     * 
     * Route: DELETE /schedules/{schedule}
     * 
     * Deletes a schedule.
     * 
     * @param DeviceSchedule $schedule The schedule to delete (route model binding)
     * @return RedirectResponse Redirect to index with success message
     */
    public function destroy(DeviceSchedule $schedule): RedirectResponse
    {
        // Check authorization
        $this->authorize('delete', $schedule);

        // Delete schedule
        $schedule->delete();

        return redirect()->route('schedules.index')
            ->with('success', 'Schedule removed successfully.');
    }
}
