#!/bin/bash

################################################################################
# Get Connected Devices Script
# 
# Purpose: Get a list of all devices currently connected to the wlan0 WiFi
#          access point, including their MAC addresses, IP addresses, and hostnames.
#
# Usage:   ./get_connected_devices.sh
# Output:  JSON array of devices to stdout
#
# What This Script Does:
# 1. Queries the ARP (Address Resolution Protocol) table for wlan0 interface
# 2. Extracts MAC addresses, IP addresses, and hostnames
# 3. Normalizes MAC addresses to standard format
# 4. Outputs results as JSON array
# 5. Handles empty results gracefully
#
# Output Format (JSON):
# [
#   {
#     "mac": "AA:BB:CC:DD:EE:FF",
#     "ip": "192.168.4.2",
#     "hostname": "device-name" or "unknown"
#   },
#   ...
# ]
#
# Exit Codes:
#   0 = Success (even if no devices found - returns empty array)
#   1 = Error (system command failed)
#
# Important Notes:
# - Outputs JSON to stdout (for easy parsing in PHP)
# - Error messages go to stderr (don't interfere with JSON output)
# - Uses wlan0 interface specifically (Raspberry Pi WiFi access point)
# - Returns empty array [] if no devices are connected
################################################################################

# Set script to exit immediately if any command fails
set -e

# Set script to exit if any variable is used before being set
set -u

################################################################################
# Function: normalize_mac_address
# 
# Purpose: Convert MAC address to standard format (colons, uppercase)
#
# See block_device.sh for detailed explanation
################################################################################
normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

################################################################################
# Function: get_hostname
# 
# Purpose: Get hostname for an IP address (if available)
#
# Input:   IP address
# Output:  Hostname or "unknown"
#
# What This Function Does:
# - Attempts reverse DNS lookup to get hostname
# - Uses getent (get entries from databases) to query hosts database
# - Falls back to "unknown" if hostname not available
# - Times out after 1 second to avoid hanging
#
# Why getent instead of host/nslookup?
# - getent is faster and more reliable
# - Works with local /etc/hosts file
# - Better error handling
################################################################################
get_hostname() {
    local ip="$1"
    
    # Try to get hostname using getent
    # getent hosts = Query hosts database
    # awk '{print $2}' = Extract second field (hostname)
    # timeout 1 = Kill command after 1 second (prevent hanging)
    # 2>/dev/null = Discard error messages
    local hostname=$(timeout 1 getent hosts "$ip" 2>/dev/null | awk '{print $2}' | head -1)
    
    # Check if hostname was found
    if [ -n "$hostname" ]; then
        # Remove domain suffix if present (e.g., "device.local" -> "device")
        # cut -d. -f1 = Split by dot, take first field
        echo "$hostname" | cut -d. -f1
    else
        # No hostname found - return "unknown"
        echo "unknown"
    fi
}

################################################################################
# Main Script Execution
################################################################################

# Check if wlan0 interface exists
# ip link show = Show network interfaces
# grep -q = Quiet mode (return exit code only)
if ! ip link show wlan0 > /dev/null 2>&1; then
    echo "Error: wlan0 interface not found" >&2
    echo "[]"  # Return empty JSON array
    exit 1
fi

# Initialize JSON array (we'll build it as we find devices)
# Start with opening bracket
json_output="["

# Counter for devices found (to handle commas in JSON)
device_count=0

# Get connected devices from ARP table
# ip neigh show dev wlan0 = Show neighbor (ARP) entries for wlan0 interface
# This shows all devices that have communicated with the Pi
# Format: IP_ADDRESS dev wlan0 lladdr MAC_ADDRESS state STATE
#
# Example output:
# 192.168.4.2 dev wlan0 lladdr AA:BB:CC:DD:EE:FF REACHABLE
#
# We'll parse this to extract IP and MAC addresses
while IFS= read -r line; do
    # Skip empty lines
    if [ -z "$line" ]; then
        continue
    fi
    
    # Extract IP address (first field)
    # awk '{print $1}' = Print first field (space-separated)
    ip_address=$(echo "$line" | awk '{print $1}')
    
    # Extract MAC address (5th field, after "lladdr")
    # awk '{print $5}' = Print 5th field
    mac_address=$(echo "$line" | awk '{print $3}')
    
    # Skip if MAC address is empty or invalid
    # -z checks if string is empty
    if [ -z "$mac_address" ]; then
        continue
    fi
    
    # Normalize MAC address to standard format
    normalized_mac=$(normalize_mac_address "$mac_address")
    
    # Get hostname for this IP address
    hostname=$(get_hostname "$ip_address")
    
    # Add comma before this device (except first one)
    # This ensures valid JSON format
    if [ $device_count -gt 0 ]; then
        json_output="$json_output,"
    fi
    
    # Add device object to JSON array
    # Format: {"mac":"...","ip":"...","hostname":"..."}
    json_output="$json_output{\"mac\":\"$normalized_mac\",\"ip\":\"$ip_address\",\"hostname\":\"$hostname\"}"
    
    # Increment device counter
    device_count=$((device_count + 1))
    
# Read from ip neigh show command output
# Process substitution: <(command) = Run command and use output as file
done < <(ip neigh show dev wlan0 2>/dev/null || true)

# Close JSON array
json_output="$json_output]"

# Output JSON to stdout
# This is what PHP will read when calling the script
echo "$json_output"

# Exit with success (even if no devices found - empty array is valid)
exit 0

