# Monitor Device Connections Job - Complete Guide

## What Is This Job?

The `MonitorDeviceConnections` job is a background job that periodically monitors the network to detect new devices connecting to the WiFi access point and devices that have disconnected. It updates device IP addresses, ends sessions for disconnected devices, and logs new device connections for parent review.

**File Location**: `app/Jobs/MonitorDeviceConnections.php`

## Why Do We Need This Job?

Devices connect and disconnect from the WiFi network constantly. We need to:

1. **Detect New Devices**: Know when new devices connect (not in database)
2. **Update IP Addresses**: Update device IP addresses when they reconnect
3. **End Sessions**: End active sessions for devices that disconnected
4. **Track Connectivity**: Know which devices are currently online

Without this job, we wouldn't know which devices are connected, IP addresses would be stale, and sessions would never end for disconnected devices.

## How Does It Work?

### Step-by-Step Workflow

1. **Job Runs**: Every 2 minutes, Laravel's scheduler runs this job
2. **Get Connected Devices**: Calls `NetworkService::getConnectedDevices()` to get current device list
3. **Get Database Devices**: Gets all devices from database, indexed by MAC address
4. **Process Connected Devices**: For each connected device:
   - If device exists in database: Update IP address and timestamp
   - If device doesn't exist: Log as new device for parent review
5. **Process Disconnected Devices**: For each database device not in connected list:
   - End active sessions (deduct time)
   - Clear IP address
6. **Log Results**: Logs all operations for monitoring

### Example Scenario

**Scenario 1: Device Connects**

1. **10:00 AM**: Device connects to WiFi (MAC: AA:BB:CC:DD:EE:FF, IP: 192.168.4.5)
2. **10:02 AM**: Job runs, detects device in network
3. **10:02 AM**: Job finds device in database, updates IP address and timestamp
4. **10:02 AM**: Device can now start browsing

**Scenario 2: Device Disconnects**

1. **10:00 AM**: Device is browsing (active session)
2. **10:15 AM**: Device disconnects from WiFi
3. **10:16 AM**: Job runs, detects device not in network
4. **10:16 AM**: Job ends active session (deducts time)
5. **10:16 AM**: Job clears IP address

**Scenario 3: New Device Connects**

1. **10:00 AM**: Unknown device connects (MAC: 11:22:33:44:55:66)
2. **10:02 AM**: Job runs, detects device not in database
3. **10:02 AM**: Job logs new device for parent review
4. **10:02 AM**: Parent can add device to system later

## Code Structure

### Class Definition

```php
class MonitorDeviceConnections implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

### Main Method: `handle()`

The `handle()` method is the main entry point:

```php
public function handle(
    NetworkService $networkService,
    TimeTrackingService $timeTrackingService
): void {
    // 1. Get connected devices from network
    $connectedDevices = $networkService->getConnectedDevices();
    
    // 2. Get database devices
    $databaseDevices = Device::all()->keyBy('mac_address');
    
    // 3. Process connected devices
    foreach ($connectedDevices as $device) {
        // Update IP address, timestamp
    }
    
    // 4. Process disconnected devices
    foreach ($databaseDevices as $device) {
        // End sessions, clear IP
    }
}
```

**Parameters**:
- `NetworkService $networkService`: Gets connected devices from network
- `TimeTrackingService $timeTrackingService`: Ends sessions for disconnected devices

## Key Concepts

### Connected Devices

Connected devices are devices currently connected to the WiFi access point. The job gets this list from `NetworkService::getConnectedDevices()`, which:

1. Executes `get_connected_devices.sh` script
2. Queries ARP table for wlan0 interface
3. Maps IP addresses to MAC addresses
4. Returns JSON array of connected devices

**Format**:
```json
[
  {
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "ip_address": "192.168.4.5",
    "hostname": "device-hostname"
  }
]
```

### MAC Address Normalization

MAC addresses can be in different formats:
- `AA:BB:CC:DD:EE:FF` (colons)
- `AA-BB-CC-DD-EE-FF` (dashes)
- `AABBCCDDEEFF` (no separators)

The job normalizes MAC addresses to uppercase with colons for consistent comparison:

```php
$macAddress = strtoupper(str_replace(['-', '_'], ':', $macAddress));
// "aa-bb-cc-dd-ee-ff" → "AA:BB:CC:DD:EE:FF"
```

### Device Matching

The job matches network devices to database devices by MAC address:

```php
// Index database devices by MAC address
$databaseDevices = Device::all()->keyBy('mac_address');

// Look up device
if (isset($databaseDevices[$macAddress])) {
    $device = $databaseDevices[$macAddress];
}
```

## Integration Points

### NetworkService

The job uses `NetworkService::getConnectedDevices()` to get the current list of connected devices. This service:

1. Executes `get_connected_devices.sh` script
2. Parses JSON output
3. Returns array of connected devices

### TimeTrackingService

The job uses `TimeTrackingService::endSession()` to end sessions for disconnected devices. This service:

1. Calculates session duration
2. Deducts time from device
3. Marks session as ended
4. Updates device timestamp

### Device Model

The job interacts with the `Device` model to:

- Update IP addresses
- Update `last_seen_at` timestamps
- Get device information
- Check device status

## Scheduling

The job is scheduled in `routes/console.php`:

```php
Schedule::job(new MonitorDeviceConnections)
    ->everyTwoMinutes()
    ->name('monitor-device-connections')
    ->withoutOverlapping();
```

**Schedule Details**:
- **Frequency**: Every 2 minutes
- **Name**: `monitor-device-connections`
- **Without Overlapping**: Prevents multiple instances

**Why Every 2 Minutes?**
- Too frequent (every 30 seconds): Wastes resources
- Too infrequent (every 5 minutes): Slow detection
- 2 minutes is a good balance

## Error Handling

The job uses try-catch blocks at multiple levels:

### Network Service Errors

```php
try {
    $connectedDevices = $networkService->getConnectedDevices();
} catch (\Exception $e) {
    Log::error('Failed to get connected devices', [...]);
    throw $e; // Re-throw for retry
}
```

### Individual Device Errors

```php
foreach ($connectedDevices as $device) {
    try {
        // Process device
    } catch (\Exception $e) {
        Log::error('Error processing device', [...]);
        continue; // Continue with next device
    }
}
```

**Error Handling Strategy**:
1. Log error for debugging
2. Continue processing other devices
3. Re-throw critical errors for queue retry

## Logging

The job logs important events:

### Info Level
- Job start: `MonitorDeviceConnections job started`
- New device: `New device detected on network`
- Session ended: `Ended active sessions for disconnected device`
- Job completion: `MonitorDeviceConnections job completed`

### Debug Level
- Device updated: `Device connection updated`
- Device disconnected: `Device disconnected`

### Error Level
- Job failure: `MonitorDeviceConnections job failed`
- Device processing error: `Error processing connected device`

## Testing

### Manual Testing

```php
use App\Jobs\MonitorDeviceConnections;

// Dispatch job immediately
MonitorDeviceConnections::dispatch();
```

### Testing Scenarios

1. **Device Connects**: Connect device, run job, verify IP updated
2. **Device Disconnects**: Disconnect device, run job, verify session ended
3. **New Device**: Connect unknown device, run job, verify logged
4. **No Devices**: Run job with no devices, verify no errors

## Troubleshooting

### Job Not Detecting Devices

**Problem**: Job runs but doesn't detect connected devices.

**Solutions**:
1. Check `NetworkService::getConnectedDevices()` is working
2. Verify `get_connected_devices.sh` script exists and is executable
3. Check ARP table: `arp -a`
4. Check logs for errors

### IP Addresses Not Updating

**Problem**: Devices connect but IP addresses don't update.

**Solutions**:
1. Check MAC address matching (normalization)
2. Verify device exists in database
3. Check database update permissions
4. Check logs for errors

### Sessions Not Ending

**Problem**: Devices disconnect but sessions don't end.

**Solutions**:
1. Check if device has active sessions
2. Verify `TimeTrackingService::endSession()` is called
3. Check logs for errors
4. Verify device is in database

## Code Examples

### Basic Usage

```php
// Job runs automatically via scheduler
// No manual call needed
```

### Manual Dispatch

```php
use App\Jobs\MonitorDeviceConnections;

// Dispatch immediately
MonitorDeviceConnections::dispatch();
```

### Checking Connected Devices

```php
use App\Services\NetworkService;

$networkService = app(NetworkService::class);
$devices = $networkService->getConnectedDevices();

foreach ($devices as $device) {
    echo "MAC: {$device['mac_address']}, IP: {$device['ip_address']}\n";
}
```

## Summary

The `MonitorDeviceConnections` job is essential for network monitoring. It:

- Runs every 2 minutes
- Detects new/disconnected devices
- Updates IP addresses
- Ends sessions for disconnected devices
- Logs new devices for parent review

Without this job, we wouldn't know which devices are connected, IP addresses would be stale, and sessions would never end.

