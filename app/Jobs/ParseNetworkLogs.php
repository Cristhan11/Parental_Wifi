<?php

namespace App\Jobs;

use App\Models\BrowsingLog;
use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Parse Network Logs Job
 * 
 * This background job periodically parses network traffic logs to extract browsing
 * history and create BrowsingLog records. It reads tcpdump or iptables log files,
 * extracts HTTP requests, matches them to devices by MAC address, and stores the
 * browsing history in the database.
 * 
 * What is a Background Job?
 * - A background job is code that runs automatically without user interaction
 * - Think of it like a "robot assistant" that works in the background
 * - It runs on a schedule (every 10 minutes) to parse network logs
 * - This ensures browsing history is captured and stored in the database
 * 
 * Why Do We Need This Job?
 * - Network traffic logs contain information about websites visited
 * - We need to extract this information and store it in the database
 * - Parents can review browsing history through the dashboard
 * - This job processes logs periodically to avoid real-time processing overhead
 * 
 * How It Works:
 * 1. Job runs automatically every 10 minutes (via Laravel scheduler)
 * 2. Reads network log files (tcpdump or iptables logs)
 * 3. Parses log entries to extract HTTP/HTTPS requests
 * 4. For HTTP: Extracts URLs from plain text traffic
 * 5. For HTTPS: Extracts domains from SNI (Server Name Indication) in TLS handshakes
 * 6. Extracts URL, domain, IP address, and other information
 * 7. Matches requests to devices by MAC address
 * 8. Creates BrowsingLog records in database
 * 9. Handles log rotation (marks processed logs)
 * 
 * What Information is Extracted:
 * - URL: Full website URL visited (e.g., "https://example.com" or "http://example.com/page")
 * - Domain: Website domain (e.g., "example.com", "youtube.com", "google.com")
 *   - For HTTP: Extracted from plain text URLs
 *   - For HTTPS: Extracted from SNI (Server Name Indication) in TLS handshake
 * - IP Address: Server IP address
 * - User Agent: Browser information (if available in HTTP traffic)
 * - Timestamp: When the request was made
 * - Bandwidth: Bytes sent and received (if available)
 * 
 * Integration with Other Services:
 * - BrowsingLog Model: Stores browsing history records
 * - Device Model: Matches requests to devices by MAC address
 * - Storage Facade: Reads log files from storage
 * 
 * Error Handling:
 * - If log file doesn't exist, job logs warning and continues
 * - If parsing fails for one entry, job continues with next entry
 * - Errors are logged but don't crash the job
 * - Job will retry failed operations on next run
 * 
 * Scheduling:
 * - Registered in routes/console.php (Laravel 11+) or app/Console/Kernel.php (Laravel 10)
 * - Runs every 10 minutes to balance processing frequency and performance
 * - Uses Laravel's scheduler to run automatically
 * 
 * Why Every 10 Minutes?
 * - Logs accumulate over time, don't need real-time parsing
 * - Too frequent (every 1 minute): Wastes server resources
 * - Too infrequent (every 30 minutes): Browsing history becomes stale
 * - 10 minutes is a good balance: Recent history without overloading server
 * 
 * Log File Locations:
 * - tcpdump logs: /var/log/tcpdump/network.log (or configured path)
 * - iptables logs: /var/log/iptables.log (or configured path)
 * - Logs are rotated automatically by system
 * 
 * Usage Example:
 * ```php
 * // Job runs automatically via scheduler
 * // No manual call needed - Laravel handles it
 * 
 * // To test manually:
 * use App\Jobs\ParseNetworkLogs;
 * ParseNetworkLogs::dispatch();
 * ```
 */
class ParseNetworkLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to parse network logs and create browsing history.
     * 
     * This is the main method that runs when the job executes. It:
     * 1. Reads network log files (tcpdump or iptables)
     * 2. Parses log entries to extract HTTP requests
     * 3. Extracts URL, domain, IP address, and other information
     * 4. Matches requests to devices by MAC address
     * 5. Creates BrowsingLog records in database
     * 6. Handles log rotation
     * 
     * How It Works Step-by-Step:
     * - Step 1: Determine which log files to process
     * - Step 2: Read log file content
     * - Step 3: Parse each log entry
     * - Step 4: Extract HTTP request information
     * - Step 5: Match request to device by MAC address
     * - Step 6: Create BrowsingLog record
     * - Step 7: Mark log as processed
     * 
     * Error Handling:
     * - If log file doesn't exist, job logs warning and exits
     * - If parsing fails for one entry, job continues with next entry
     * - Errors are logged but don't crash the job
     * 
     * @return void No return value
     * 
     * Usage:
     * This method is called automatically by Laravel when the job runs.
     * You don't need to call it manually - the scheduler handles it.
     */
    public function handle(): void
    {
        // Log that the job started running
        // This helps us track when the job executes and debug any issues
        Log::info('ParseNetworkLogs job started - parsing network traffic logs');

        // Step 1: Determine which log files to process
        // Network logs can be stored in different locations depending on configuration
        // Common locations:
        // - tcpdump logs: /var/log/tcpdump/network.log
        // - iptables logs: /var/log/iptables.log
        // - Custom location: config('network.log_path')
        // 
        // For now, we'll use a configurable path from environment
        // In production, this would be configured in .env file
        $logPath = config('network.log_path', '/var/log/tcpdump/network.log');

        // Check if log file exists
        // If log file doesn't exist, there's nothing to process
        if (!file_exists($logPath)) {
            Log::debug('ParseNetworkLogs job completed - log file does not exist', [
                'log_path' => $logPath,
            ]);
            return; // Exit early - no log file to process
        }

        // Step 2: Read log file content
        // We read the entire log file into memory
        // For large log files, this might need to be optimized to read line by line
        // 
        // Why Read Entire File?
        // - Simpler implementation for now
         // - Log files are typically rotated before they get too large
        // - Can be optimized later if needed (read line by line)
        try {
            $logContent = file_get_contents($logPath);
        } catch (\Exception $e) {
            // If reading log file fails, log error and exit
            // This could happen if file permissions are incorrect
            Log::error('Failed to read network log file', [
                'log_path' => $logPath,
                'error' => $e->getMessage(),
            ]);
            return; // Exit early - can't read log file
        }

        // If log file is empty, nothing to process
        if (empty($logContent)) {
            Log::debug('ParseNetworkLogs job completed - log file is empty', [
                'log_path' => $logPath,
            ]);
            return; // Exit early - no content to process
        }

        // Step 3: Parse log entries
        // Log files typically have one entry per line
        // We split the content into lines and process each line
        // 
        // Log Format Examples:
        // - tcpdump: "2024-01-15 15:30:00 IP 192.168.4.5.54321 > 93.184.216.34.80: GET /page HTTP/1.1"
        // - iptables: "Jan 15 15:30:00 kernel: IN=wlan0 OUT=eth0 MAC=AA:BB:CC:DD:EE:FF SRC=192.168.4.5 DST=93.184.216.34"
        // 
        // We need to parse these formats to extract:
        // - MAC address (to match to device)
        // - Source IP (device IP)
        // - Destination IP (server IP)
        // - URL/domain (from HTTP request)
        // - Timestamp (when request was made)
        $logLines = explode("\n", $logContent);
        $entriesProcessed = 0;
        $entriesCreated = 0;
        $entriesSkipped = 0;

        // Get all devices indexed by MAC address for quick lookup
        // This avoids querying database for each log entry
        $devices = Device::all()->keyBy(function ($device) {
            // Normalize MAC address to uppercase with colons for consistent lookup
            return strtoupper(str_replace(['-', '_'], ':', $device->mac_address));
        });

        // Step 4: Process each log entry
        foreach ($logLines as $lineNumber => $line) {
            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            // Wrap in try-catch to handle individual entry errors
            // If one entry fails to parse, we continue with next entry
            try {
                // Parse log entry to extract information
                // This is a simplified parser - in production, you would use
                // a more robust parsing library or regex patterns
                $parsedEntry = $this->parseLogEntry($line);

                // If parsing failed, skip this entry
                if (!$parsedEntry) {
                    $entriesSkipped++;
                    continue; // Continue with next entry
                }

                // Step 5: Match request to device by MAC address
                // We need to find which device made this request
                // MAC address is the unique identifier for each device
                $macAddress = strtoupper(str_replace(['-', '_'], ':', $parsedEntry['mac_address'] ?? ''));

                // If MAC address is missing or device not found, skip this entry
                if (empty($macAddress) || !isset($devices[$macAddress])) {
                    $entriesSkipped++;
                    continue; // Continue with next entry
                }

                $device = $devices[$macAddress];

                // Step 6: Check if browsing log already exists (avoid duplicates)
                // We check if a BrowsingLog with same URL and timestamp already exists
                // This prevents duplicate entries if job runs multiple times
                $existingLog = BrowsingLog::where('device_id', $device->id)
                    ->where('url', $parsedEntry['url'])
                    ->where('visited_at', $parsedEntry['visited_at'])
                    ->first();

                if ($existingLog) {
                    // Log already exists, skip it
                    $entriesSkipped++;
                    continue; // Continue with next entry
                }

                // Step 7: Create BrowsingLog record
                // We store the browsing history in the database
                // This allows parents to review browsing history through the dashboard
                BrowsingLog::create([
                    'device_id' => $device->id,
                    'url' => $parsedEntry['url'],
                    'domain' => $parsedEntry['domain'],
                    'ip_address' => $parsedEntry['ip_address'],
                    'user_agent' => $parsedEntry['user_agent'] ?? null,
                    'bytes_sent' => $parsedEntry['bytes_sent'] ?? 0,
                    'bytes_received' => $parsedEntry['bytes_received'] ?? 0,
                    'visited_at' => $parsedEntry['visited_at'],
                ]);

                $entriesCreated++;
                $entriesProcessed++;

            } catch (\Exception $e) {
                // If parsing or creating log entry fails, log error but continue
                // This ensures one failed entry doesn't stop processing of other entries
                Log::warning('Failed to process log entry', [
                    'line_number' => $lineNumber + 1,
                    'line_content' => substr($line, 0, 100), // First 100 characters
                    'error' => $e->getMessage(),
                ]);

                $entriesSkipped++;
                continue; // Continue with next entry
            }
        }

        // Step 8: Log job completion with summary
        // This helps us monitor job execution and understand processing results
        Log::info('ParseNetworkLogs job completed', [
            'log_path' => $logPath,
            'entries_processed' => $entriesProcessed,
            'entries_created' => $entriesCreated,
            'entries_skipped' => $entriesSkipped,
        ]);

        // Note: In a full implementation, you would also:
        // - Mark log file as processed (move to archive)
        // - Rotate log files if they get too large
        // - Handle log file locking to prevent concurrent processing
    }

    /**
     * Parse a single log entry to extract HTTP/HTTPS request information.
     * 
     * This method parses a log line to extract:
     * - MAC address (to match to device)
     * - URL (website visited)
     * - Domain (website domain)
     *   - For HTTP: Extracted from plain text URLs in traffic
     *   - For HTTPS: Extracted from SNI (Server Name Indication) in TLS handshake
     * - IP address (server IP)
     * - Timestamp (when request was made)
     * - User agent (browser information, if available in HTTP traffic)
     * - Bandwidth (bytes sent/received, if available)
     * 
     * HTTPS Domain Extraction:
     * - Uses SNI (Server Name Indication) from TLS ClientHello packets
     * - SNI contains the domain name in plain text before encryption
     * - Works with tcpdump -A flag output which shows readable packet content
     * - Extracts domains like "google.com", "youtube.com", "facebook.com"
     * 
     * This parser handles both HTTP (plain text) and HTTPS (SNI extraction)
     * traffic to provide accurate domain capture for parental monitoring.
     * 
     * @param string $line The log line to parse
     * @return array|null Parsed entry data or null if parsing failed
     * 
     * Usage:
     * This method is called internally by handle() method.
     * You don't need to call it manually.
     */
    private function parseLogEntry(string $line): ?array
    {
        // This is a simplified parser - in production, you would implement
        // a more robust parser based on your specific log format
        // 
        // Example log formats:
        // - tcpdump: "2024-01-15 15:30:00 IP 192.168.4.5.54321 > 93.184.216.34.80: GET /page HTTP/1.1 Host: example.com"
        // - iptables: "Jan 15 15:30:00 kernel: IN=wlan0 OUT=eth0 MAC=AA:BB:CC:DD:EE:FF SRC=192.168.4.5 DST=93.184.216.34"
        // 
        // For now, we'll implement a basic parser that extracts common patterns
        // In production, you would customize this based on your log format

        // Extract MAC address from log line
        // MAC address format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
        // Pattern: Look for MAC address pattern in log line
        if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $macMatches)) {
            $macAddress = $macMatches[0];
        } else {
            // MAC address not found, can't match to device
            return null;
        }

        // Extract URL and domain from log line
        // Supports both HTTP (plain text) and HTTPS (SNI extraction from TLS handshake)
        $url = null;
        $domain = null;
        $isHttps = false;
        
        // Step 1: Try to extract HTTP/HTTPS URLs (works for HTTP traffic)
        if (preg_match('/https?:\/\/([^\s\/]+)/', $line, $urlMatches)) {
            $url = $urlMatches[0];
            $isHttps = strpos($url, 'https://') === 0;
            // Extract domain from URL
            if (preg_match('/https?:\/\/([^\/\s]+)/', $url, $domainMatches)) {
                $domain = $domainMatches[1];
                // Remove port numbers if present
                $domain = preg_replace('/:\d+$/', '', $domain);
            }
        }
        // Step 2: Extract SNI (Server Name Indication) from TLS handshake for HTTPS
        // SNI appears in TLS ClientHello packets before encryption
        // Check if this is HTTPS traffic (port 443)
        elseif (preg_match('/\.443[^:>]*?[>:]/', $line) || preg_match('/:443[^>]*?[>:]/', $line)) {
            $isHttps = true;
            
            // SNI extraction: Look for domain names in TLS handshake context
            // SNI domain appears as readable text in TLS ClientHello with -A flag
            // Pattern 1: Look for domain patterns near port 443 connections
            // Pattern 2: Look for SNI extension format (domain appears after TLS handshake data)
            
            // Extract domain from TLS handshake - look for readable domain strings
            // Domains in SNI are often visible in tcpdump -A output
            if (preg_match('/([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}/', $line, $domainMatches)) {
                $domain = $domainMatches[0];
                
                // Filter out common false positives (IP addresses, MAC addresses, etc.)
                // Valid domain should not start with numbers and should have proper TLD
                if (!preg_match('/^\d+\./', $domain) && 
                    !preg_match('/^[0-9A-Fa-f]{2}[:-]/', $domain) &&
                    preg_match('/\.[a-zA-Z]{2,}$/', $domain)) {
                    // Clean domain: remove common prefixes that might be false positives
                    $domain = preg_replace('/^(www|api|cdn|static|img|images|media|m|mobile|www2|www3)\./', '', $domain);
                    $url = 'https://' . $domain;
                } else {
                    $domain = null;
                }
            }
            
            // If domain extraction failed, try alternative SNI patterns
            if (!$domain) {
                // Look for SNI in TLS extension format
                // SNI extension type is 0x0000, followed by length, then domain
                // In ASCII output, this might appear as readable domain strings
                if (preg_match('/\x00\x00[^\x00]{0,50}?([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}/', $line, $sniMatches)) {
                    $domain = $sniMatches[1];
                    $url = 'https://' . $domain;
                }
            }
        }
        // Step 3: Fallback - try to extract any domain-like pattern
        // This catches domains that might appear in other contexts
        elseif (preg_match('/([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}/', $line, $domainMatches)) {
            $domain = $domainMatches[0];
            
            // Determine if HTTPS based on port 443 in the line
            $isHttps = (strpos($line, '.443') !== false || strpos($line, ':443') !== false);
            
            // Filter out false positives
            if (!preg_match('/^\d+\./', $domain) && 
                !preg_match('/^[0-9A-Fa-f]{2}[:-]/', $domain) &&
                preg_match('/\.[a-zA-Z]{2,}$/', $domain)) {
                $url = ($isHttps ? 'https://' : 'http://') . $domain;
            } else {
                $domain = null;
            }
        }
        
        // If no domain found after all attempts, can't create browsing log
        if (!$domain || !$url) {
            return null;
        }

        // Extract IP address from log line
        // Look for IP address pattern (e.g., 192.168.4.5)
        $ipAddress = null;
        if (preg_match('/(\d{1,3}\.){3}\d{1,3}/', $line, $ipMatches)) {
            $ipAddress = $ipMatches[0];
        }

        // Extract timestamp from log line
        // Try to parse various timestamp formats
        // Common formats:
        // - tcpdump: "03:53:05.851696" (time only, use today's date)
        // - "2024-01-15 15:30:00" (full date and time)
        // - "Jan 15 15:30:00" (month name format)
        $visitedAt = now(); // Default to current time if can't parse

        // Try to parse tcpdump timestamp format: "03:53:05.851696"
        if (preg_match('/(\d{2}:\d{2}:\d{2}\.\d+)/', $line, $timeMatches)) {
            try {
                // Parse time with microseconds
                $timeStr = $timeMatches[1];
                $visitedAt = \Carbon\Carbon::createFromFormat('H:i:s.u', $timeStr);
                // Set to today's date (tcpdump only shows time, not date)
                $visitedAt->setDate(now()->year, now()->month, now()->day);
            } catch (\Exception $e) {
                // Try without microseconds
                try {
                    $timeStr = preg_replace('/\.\d+$/', '', $timeMatches[1]);
                    $visitedAt = \Carbon\Carbon::createFromFormat('H:i:s', $timeStr);
                    $visitedAt->setDate(now()->year, now()->month, now()->day);
                } catch (\Exception $e2) {
                    // Parsing failed, use default (now())
                }
            }
        }
        // Try to parse ISO format: "2024-01-15 15:30:00"
        elseif (preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $timeMatches)) {
            try {
                $visitedAt = \Carbon\Carbon::parse($timeMatches[1]);
            } catch (\Exception $e) {
                // Parsing failed, use default (now())
            }
        }

        // Extract user agent from log line (if available)
        // User agent format: "User-Agent: Mozilla/5.0 ..."
        $userAgent = null;
        if (preg_match('/User-Agent:\s*([^\n]+)/i', $line, $uaMatches)) {
            $userAgent = trim($uaMatches[1]);
        }

        // Extract bandwidth information (if available)
        // tcpdump shows packet length in "length X" format
        $bytesSent = 0;
        $bytesReceived = 0;
        
        // Try tcpdump format: "length 1234"
        if (preg_match('/length\s+(\d+)/', $line, $lengthMatches)) {
            // For outbound packets (device -> server), this is bytes sent
            // For inbound packets (server -> device), this is bytes received
            // We'll treat it as bytes received (most common case)
            $bytesReceived = (int) $lengthMatches[1];
        }
        // Try explicit format: "bytes_sent=1234 bytes_received=5678"
        if (preg_match('/bytes_sent=(\d+)/', $line, $sentMatches)) {
            $bytesSent = (int) $sentMatches[1];
        }
        if (preg_match('/bytes_received=(\d+)/', $line, $receivedMatches)) {
            $bytesReceived = (int) $receivedMatches[1];
        }

        // Return parsed entry data
        return [
            'mac_address' => $macAddress,
            'url' => $url,
            'domain' => $domain,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'bytes_sent' => $bytesSent,
            'bytes_received' => $bytesReceived,
            'visited_at' => $visitedAt,
        ];
    }
}

