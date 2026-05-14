#!/bin/bash

################################################################################
# Monitor Traffic Script
# 
# Purpose: Get current network traffic statistics (bytes sent/received) for
#          devices connected to the wlan0 WiFi access point.
#
# Usage:   ./monitor_traffic.sh [MAC_ADDRESS]
#          ./monitor_traffic.sh                    (all devices)
#          ./monitor_traffic.sh AA:BB:CC:DD:EE:FF (specific device)
#
# Output:  JSON array of traffic statistics to stdout
#
# What This Script Does:
# 1. Optionally filters by MAC address (if provided)
# 2. Correlates traffic with MAC addresses via ARP on wlan0 (all-devices mode)
# 3. Sums iptables byte counters on rules referencing each MAC (filter FORWARD/INPUT,
#    or if zero: mangle ndsOUT/ndsIN + filter ndsNET/ndsAUT/ndsRTR for NoDogSplash)
# 4. Outputs results as JSON array
# 5. Handles devices with no traffic (returns 0 bytes)
#
# Output Format (JSON):
# [
#   {
#     "mac_address": "AA:BB:CC:DD:EE:FF",
#     "bytes_sent": 1048576,
#     "bytes_received": 2097152
#   },
#   ...
# ]
#
# Exit Codes:
#   0 = Success (even if no traffic found - returns empty array or zeros)
#   1 = Validation error (invalid MAC address format, if provided)
#   2 = System error (iptables or network commands failed)
#
# Important Notes:
# - Outputs JSON to stdout (for easy parsing in PHP)
# - Error messages go to stderr (don't interfere with JSON output)
# - Uses wlan0 for neighbour discovery (all-devices mode)
# - Per-MAC totals prefer filter FORWARD/INPUT; if zero, uses NoDogSplash-related chains
# - If MAC address provided, returns only that device's stats
# - If no MAC provided, returns stats for all devices
################################################################################

# Set script to exit immediately if any command fails
set -e

# Set script to exit if any variable is used before being set
set -u

################################################################################
# Function: validate_mac_address
# 
# Purpose: Check if the MAC address is in a valid format
#
# See block_device.sh for detailed explanation
################################################################################
validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        return 1
    fi
    
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0
    else
        echo "Error: Invalid MAC address format: $mac" >&2
        echo "Expected format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX" >&2
        return 1
    fi
}

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
# Function: sum_bytes_for_mac_in_chain
#
# Input:   iptables table, chain name, MAC address (any case)
# Output:  integer byte sum printed to stdout (rules whose line contains the MAC)
################################################################################
sum_bytes_for_mac_in_chain() {
    local table="$1"
    local chain="$2"
    local mac="$3"
    local sum=0

    while IFS= read -r line; do
        [ -z "$line" ] && continue
        if ! echo "$line" | grep -qi "$mac"; then
            continue
        fi
        local b
        b=$(echo "$line" | awk '{print $2}')
        if [[ "$b" =~ ^[0-9]+$ ]]; then
            sum=$((sum + b))
        fi
    done < <(sudo iptables -t "$table" -L "$chain" -v -n -x 2>/dev/null || true)

    echo "$sum"
}

################################################################################
# Function: get_traffic_for_mac
# 
# Purpose: Get traffic statistics for a specific MAC address
#
# Input:   MAC address
# Output:  JSON object with bytes_sent and bytes_received
#
# What This Function Does:
# - Sums iptables byte counters on rules that reference the MAC (case-insensitive)
# - Checks filter: FORWARD, INPUT (whitelist / block style rules)
# - If those chains show 0 bytes for this MAC, also checks NoDogSplash-related chains:
#   mangle: ndsOUT, ndsIN; filter: ndsNET, ndsAUT, ndsRTR (child traffic often only hits ndsOUT)
#
# Double-counting: if FORWARD/INPUT already account for this MAC, mangle/nds* counters are skipped.
################################################################################
get_traffic_for_mac() {
    local mac="$1"
    local total_bytes=0
    local filter_main=0
    local nds_bytes=0
    local chain
    local part

    for chain in FORWARD INPUT; do
        part=$(sum_bytes_for_mac_in_chain "filter" "$chain" "$mac")
        part=${part:-0}
        filter_main=$((filter_main + part))
    done

    if [ "$filter_main" -gt 0 ]; then
        total_bytes=$filter_main
    else
        for chain in ndsOUT ndsIN; do
            part=$(sum_bytes_for_mac_in_chain "mangle" "$chain" "$mac")
            part=${part:-0}
            nds_bytes=$((nds_bytes + part))
        done
        for chain in ndsNET ndsAUT ndsRTR; do
            part=$(sum_bytes_for_mac_in_chain "filter" "$chain" "$mac")
            part=${part:-0}
            nds_bytes=$((nds_bytes + part))
        done
        total_bytes=$nds_bytes
    fi

    # NetworkService adds bytes_sent + bytes_received; combined total in bytes_received is enough.
    echo "{\"mac_address\":\"$mac\",\"bytes_sent\":0,\"bytes_received\":$total_bytes}"
}

################################################################################
# Function: get_all_devices_traffic
# 
# Purpose: Get traffic statistics for all connected devices
#
# Output:  JSON array with traffic for all devices
#
# What This Function Does:
# - Gets list of connected devices (MAC addresses) from ARP table
# - For each device, gets traffic statistics
# - Combines into JSON array
# - Handles devices with no traffic (returns 0 bytes)
################################################################################
get_all_devices_traffic() {
    local json_output="["
    local device_count=0
    
    # Get all connected devices from ARP table
    # ip neigh show dev wlan0 = Show all devices on wlan0
    while IFS= read -r line; do
        # Skip empty lines
        if [ -z "$line" ]; then
            continue
        fi
        
        # MAC follows "lladdr" (field position varies: with "dev wlan0" MAC is $5, without it MAC is $3)
        mac_address=$(echo "$line" | awk '{
            for (i = 1; i <= NF; i++) {
                if ($i == "lladdr" && i + 1 <= NF) { print $(i + 1); exit }
            }
        }')

        # Skip if no lladdr (e.g. "192.168.x.x FAILED" only)
        if [ -z "$mac_address" ]; then
            continue
        fi
        
        # Normalize MAC address
        normalized_mac=$(normalize_mac_address "$mac_address")
        
        # Get traffic statistics for this device
        device_traffic=$(get_traffic_for_mac "$normalized_mac")
        
        # Add comma before this device (except first one)
        if [ $device_count -gt 0 ]; then
            json_output="$json_output,"
        fi
        
        # Add device traffic to JSON array
        json_output="$json_output$device_traffic"
        
        # Increment counter
        device_count=$((device_count + 1))
        
    done < <(ip neigh show dev wlan0 2>/dev/null || true)
    
    # Close JSON array
    json_output="$json_output]"
    
    # Output JSON
    echo "$json_output"
}

################################################################################
# Main Script Execution
################################################################################

# Check if wlan0 interface exists
if ! ip link show wlan0 > /dev/null 2>&1; then
    echo "Error: wlan0 interface not found" >&2
    echo "[]"  # Return empty JSON array
    exit 2
fi

# Check if MAC address argument was provided
if [ $# -eq 1 ]; then
    # MAC address provided - get stats for specific device
    MAC_ADDRESS="$1"
    
    # Validate MAC address format
    if ! validate_mac_address "$MAC_ADDRESS"; then
        echo "[]"  # Return empty JSON array on error
        exit 1
    fi
    
    # Normalize MAC address
    NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")
    
    # Get traffic statistics for this device
    # Output as JSON array with single device
    device_traffic=$(get_traffic_for_mac "$NORMALIZED_MAC")
    echo "[$device_traffic]"
    
elif [ $# -eq 0 ]; then
    # No MAC address provided - get stats for all devices
    get_all_devices_traffic
    
else
    # Wrong number of arguments
    echo "Usage: $0 [MAC_ADDRESS]" >&2
    echo "Example: $0" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF" >&2
    echo "[]"  # Return empty JSON array
    exit 1
fi

# Exit with success
exit 0

