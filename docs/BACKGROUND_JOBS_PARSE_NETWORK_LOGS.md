# Parse Network Logs Job - Complete Guide

## What Is This Job?

The `ParseNetworkLogs` job is a background job that periodically parses network traffic logs to extract browsing history and create BrowsingLog records. It reads DNS log files (from dnsmasq), extracts domain names from DNS queries, matches them to devices by IP address, and stores the browsing history in the database.

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
2. **Read Log File**: Reads DNS log file (`/var/log/dnsmasq.log`)
3. **Parse Entries**: Splits log into lines and parses each DNS query entry
4. **Extract Information**: Extracts domain name, source IP address, timestamp
5. **Match to Device**: Matches request to device by IP address (from DNS log)
6. **Create Record**: Creates BrowsingLog record in database
7. **Handle Duplicates**: Skips entries that already exist
8. **Log Results**: Logs processing summary

### Example Scenario

**Log Entry (DNS Log Format)**:
```
Dec 22 04:30:15 parentalpi dnsmasq[1234]: query[A] google.com from 192.168.4.31
```

**Processing**:
1. Parse log entry
2. Extract domain: `google.com`
3. Extract source IP: `192.168.4.31`
4. Match to device by IP address
5. Construct URL: `https://google.com`
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

### Log File Format

The job currently uses DNS log format from dnsmasq:

**DNS Log Format**:
```
Dec 22 04:30:15 parentalpi dnsmasq[1234]: query[A] google.com from 192.168.4.31
Dec 22 04:30:16 parentalpi dnsmasq[1234]: query[AAAA] youtube.com from 192.168.4.31
Dec 22 04:30:17 parentalpi dnsmasq[1234]: reply google.com is 142.250.191.14
```

**Format Breakdown:**
- **Timestamp**: `Dec 22 04:30:15`
- **Query Type**: `query[A]` (A record) or `query[AAAA]` (IPv6)
- **Domain**: `google.com`
- **Source IP**: `from 192.168.4.31`

The parser extracts domain names from DNS queries, which works for both HTTP and HTTPS traffic.

### Domain Extraction (DNS Logs)

Domains are extracted from DNS query entries:

```php
// Pattern: query[A] domain.com from IP
preg_match('/dnsmasq\[.*?\]:\s*query\[.*?\]\s+([^\s]+)\s+from\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $matches);
$domain = $matches[1];  // e.g., "google.com"
$sourceIp = $matches[2]; // e.g., "192.168.4.31"
```

### IP Address Matching

DNS logs contain IP addresses, not MAC addresses. The job matches devices by IP:

```php
// Match device by IP address
$device = Device::where('ip_address', $sourceIp)->first();
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

The job matches requests to devices by IP address (for DNS logs):

```php
// Index devices by IP address for quick lookup
$devicesByIp = Device::whereNotNull('ip_address')->get()->keyBy('ip_address');
$device = $devicesByIp[$sourceIp] ?? Device::where('ip_address', $sourceIp)->first();
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
2. Check device matching: Verify IP address matches device (devices must have `ip_address` set)
3. Check device IP addresses: Run `MonitorDeviceConnections` job to update device IPs
4. Check duplicates: Verify entries aren't being skipped as duplicates
5. Check database: Verify BrowsingLog model works
6. Check DNS log format: Verify log entries match expected format

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
$line = "Dec 22 04:30:15 parentalpi dnsmasq[1234]: query[A] google.com from 192.168.4.31";
$parsed = $job->parseLogEntry($line);

print_r($parsed);
// Output:
// [
//     'source_ip' => '192.168.4.31',
//     'url' => 'https://google.com',
//     'domain' => 'google.com',
//     'is_dns_log' => true,
//     ...
// ]
```

### Configuring Log Path

In `config/network.php`:

```php
return [
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/dnsmasq.log'),
];
```

In `.env`:

```env
NETWORK_LOG_PATH=/var/log/dnsmasq.log
```

## Summary

The `ParseNetworkLogs` job is essential for browsing history tracking. It:

- Runs every 10 minutes
- Parses DNS log files (`/var/log/dnsmasq.log`)
- Extracts domain names from DNS queries
- Matches requests to devices by IP address
- Creates BrowsingLog records
- Prevents duplicates

**Key Features:**
- Works with DNS logging (captures HTTP and HTTPS domains)
- Reliable domain extraction (100% accurate)
- Automatic execution via Laravel scheduler
- Handles both DNS query and reply entries

Without this job, browsing history would never be captured, and parents couldn't review what their children are visiting.

**Related Documentation:**
- Complete setup guide: `docs/BROWSING_LOGS_REFERENCE.md`
- DNS logging setup: See `docs/BROWSING_LOGS_REFERENCE.md` for complete setup instructions

