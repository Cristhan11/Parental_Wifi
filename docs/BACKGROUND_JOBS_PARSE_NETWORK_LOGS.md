# Parse Network Logs Job - Complete Guide

## What Is This Job?

The `ParseNetworkLogs` job is a background job that periodically parses network traffic logs to extract browsing history and create BrowsingLog records. It reads tcpdump or iptables log files, extracts HTTP requests, matches them to devices by MAC address, and stores the browsing history in the database.

**File Location**: `app/Jobs/ParseNetworkLogs.php`

## Why Do We Need This Job?

Network traffic logs contain information about websites visited, but they're just text files. We need to:

1. **Extract Browsing History**: Parse logs to find which websites were visited
2. **Match to Devices**: Match requests to devices by MAC address
3. **Store in Database**: Create BrowsingLog records for parent review
4. **Track Usage**: Monitor bandwidth and browsing patterns

Without this job, browsing history would never be captured, and parents couldn't review what their children are visiting.

## How Does It Work?

### Step-by-Step Workflow

1. **Job Runs**: Every 10 minutes, Laravel's scheduler runs this job
2. **Read Log File**: Reads network log file (tcpdump or iptables)
3. **Parse Entries**: Splits log into lines and parses each entry
4. **Extract Information**: Extracts URL, domain, IP address, timestamp, etc.
5. **Match to Device**: Matches request to device by MAC address
6. **Create Record**: Creates BrowsingLog record in database
7. **Handle Duplicates**: Skips entries that already exist
8. **Log Results**: Logs processing summary

### Example Scenario

**Log Entry**:
```
2024-01-15 15:30:00 IP 192.168.4.5.54321 > 93.184.216.34.80: GET /page HTTP/1.1 Host: example.com MAC=AA:BB:CC:DD:EE:FF
```

**Processing**:
1. Parse log entry
2. Extract MAC address: `AA:BB:CC:DD:EE:FF`
3. Extract URL: `http://example.com/page`
4. Extract domain: `example.com`
5. Match to device by MAC address
6. Create BrowsingLog record

## Code Structure

### Class Definition

```php
class ParseNetworkLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

### Main Method: `handle()`

The `handle()` method is the main entry point:

```php
public function handle(): void
{
    // 1. Get log file path
    $logPath = config('network.log_path', '/var/log/tcpdump/network.log');
    
    // 2. Read log file
    $logContent = file_get_contents($logPath);
    
    // 3. Parse entries
    $logLines = explode("\n", $logContent);
    
    // 4. Process each entry
    foreach ($logLines as $line) {
        $parsedEntry = $this->parseLogEntry($line);
        // Create BrowsingLog record
    }
}
```

### Helper Method: `parseLogEntry()`

The `parseLogEntry()` method parses a single log line:

```php
private function parseLogEntry(string $line): ?array
{
    // Extract MAC address
    // Extract URL/domain
    // Extract IP address
    // Extract timestamp
    // Extract user agent
    // Extract bandwidth
    // Return parsed data
}
```

## Key Concepts

### Log File Formats

Network logs can be in different formats:

**tcpdump Format**:
```
2024-01-15 15:30:00 IP 192.168.4.5.54321 > 93.184.216.34.80: GET /page HTTP/1.1 Host: example.com
```

**iptables Format**:
```
Jan 15 15:30:00 kernel: IN=wlan0 OUT=eth0 MAC=AA:BB:CC:DD:EE:FF SRC=192.168.4.5 DST=93.184.216.34
```

The parser needs to handle both formats (or be configured for one).

### MAC Address Extraction

MAC addresses are extracted using regex patterns:

```php
// Pattern: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $matches);
$macAddress = $matches[0];
```

### URL Extraction

URLs are extracted from HTTP requests:

```php
// Pattern: http:// or https:// followed by domain and path
preg_match('/https?:\/\/([^\s]+)/', $line, $matches);
$url = $matches[0];
```

### Timestamp Parsing

Timestamps are parsed from various formats:

```php
// ISO format: "2024-01-15 15:30:00"
preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $matches);
$visitedAt = \Carbon\Carbon::parse($matches[1]);
```

### Duplicate Prevention

The job checks for existing BrowsingLog records to prevent duplicates:

```php
$existingLog = BrowsingLog::where('device_id', $device->id)
    ->where('url', $parsedEntry['url'])
    ->where('visited_at', $parsedEntry['visited_at'])
    ->first();

if ($existingLog) {
    continue; // Skip duplicate
}
```

## Integration Points

### BrowsingLog Model

The job creates `BrowsingLog` records:

```php
BrowsingLog::create([
    'device_id' => $device->id,
    'url' => $parsedEntry['url'],
    'domain' => $parsedEntry['domain'],
    'ip_address' => $parsedEntry['ip_address'],
    'user_agent' => $parsedEntry['user_agent'],
    'bytes_sent' => $parsedEntry['bytes_sent'],
    'bytes_received' => $parsedEntry['bytes_received'],
    'visited_at' => $parsedEntry['visited_at'],
]);
```

### Device Model

The job matches requests to devices by MAC address:

```php
$devices = Device::all()->keyBy('mac_address');
$device = $devices[$macAddress];
```

### Storage Facade

The job reads log files (though current implementation uses `file_get_contents()`):

```php
$logContent = file_get_contents($logPath);
```

## Scheduling

The job is scheduled in `routes/console.php`:

```php
Schedule::job(new ParseNetworkLogs)
    ->everyTenMinutes()
    ->name('parse-network-logs')
    ->withoutOverlapping();
```

**Schedule Details**:
- **Frequency**: Every 10 minutes
- **Name**: `parse-network-logs`
- **Without Overlapping**: Prevents multiple instances

**Why Every 10 Minutes?**
- Logs accumulate over time, don't need real-time parsing
- Too frequent (every 1 minute): Wastes resources
- Too infrequent (every 30 minutes): History becomes stale
- 10 minutes is a good balance

## Error Handling

The job uses try-catch blocks at multiple levels:

### File Reading Errors

```php
try {
    $logContent = file_get_contents($logPath);
} catch (\Exception $e) {
    Log::error('Failed to read network log file', [...]);
    return; // Exit early
}
```

### Entry Parsing Errors

```php
foreach ($logLines as $line) {
    try {
        $parsedEntry = $this->parseLogEntry($line);
        // Process entry
    } catch (\Exception $e) {
        Log::warning('Failed to process log entry', [...]);
        continue; // Continue with next entry
    }
}
```

**Error Handling Strategy**:
1. Log error for debugging
2. Continue processing other entries
3. Don't crash the job

## Logging

The job logs important events:

### Info Level
- Job start: `ParseNetworkLogs job started`
- Job completion: `ParseNetworkLogs job completed`

### Debug Level
- No log file: `ParseNetworkLogs job completed - log file does not exist`
- Empty log: `ParseNetworkLogs job completed - log file is empty`

### Warning Level
- Entry processing error: `Failed to process log entry`

### Error Level
- File reading error: `Failed to read network log file`

## Testing

### Manual Testing

```php
use App\Jobs\ParseNetworkLogs;

// Dispatch job immediately
ParseNetworkLogs::dispatch();
```

### Testing Scenarios

1. **Valid Log Entry**: Create log file with valid entry, run job, verify BrowsingLog created
2. **Invalid Log Entry**: Create log file with invalid entry, run job, verify skipped
3. **Duplicate Entry**: Create duplicate entry, run job, verify skipped
4. **No Log File**: Run job with no log file, verify no errors
5. **Empty Log File**: Create empty log file, run job, verify no errors

## Troubleshooting

### Log File Not Found

**Problem**: Job can't find log file.

**Solutions**:
1. Check log path: `config('network.log_path')`
2. Verify file exists: `file_exists($logPath)`
3. Check file permissions: `chmod 644 $logPath`
4. Check file location: Verify path is correct

### Parsing Fails

**Problem**: Log entries not being parsed correctly.

**Solutions**:
1. Check log format: Verify format matches parser expectations
2. Check regex patterns: Verify patterns match log format
3. Check logs: Review warning messages
4. Test parser: Test `parseLogEntry()` method directly

### No BrowsingLogs Created

**Problem**: Job runs but no records created.

**Solutions**:
1. Check if entries parsed: Verify `$parsedEntry` is not null
2. Check device matching: Verify MAC address matches device
3. Check duplicates: Verify entries aren't being skipped as duplicates
4. Check database: Verify BrowsingLog model works

## Code Examples

### Basic Usage

```php
// Job runs automatically via scheduler
// No manual call needed
```

### Manual Dispatch

```php
use App\Jobs\ParseNetworkLogs;

// Dispatch immediately
ParseNetworkLogs::dispatch();
```

### Testing Parser

```php
$job = new ParseNetworkLogs();
$line = "2024-01-15 15:30:00 IP 192.168.4.5 > 93.184.216.34: GET /page HTTP/1.1 MAC=AA:BB:CC:DD:EE:FF";
$parsed = $job->parseLogEntry($line);

print_r($parsed);
// Output:
// [
//     'mac_address' => 'AA:BB:CC:DD:EE:FF',
//     'url' => 'http://93.184.216.34/page',
//     'domain' => '93.184.216.34',
//     ...
// ]
```

### Configuring Log Path

In `config/network.php`:

```php
return [
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/tcpdump/network.log'),
];
```

In `.env`:

```env
NETWORK_LOG_PATH=/var/log/tcpdump/network.log
```

## Summary

The `ParseNetworkLogs` job is essential for browsing history tracking. It:

- Runs every 10 minutes
- Parses network log files
- Extracts browsing information
- Creates BrowsingLog records
- Prevents duplicates

Without this job, browsing history would never be captured, and parents couldn't review what their children are visiting.

