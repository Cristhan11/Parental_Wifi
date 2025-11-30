#!/bin/bash

################################################################################
# Unblock Device Script
# 
# Purpose: Remove iptables blocking rules for a device's MAC address.
#          This allows the device to access the internet again.
#
# Usage:   ./unblock_device.sh <MAC_ADDRESS>
# Example: ./unblock_device.sh AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Removes DROP rules from INPUT and FORWARD chains
# 4. Handles case where rules don't exist (idempotent)
# 5. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device unblocked, or already unblocked)
#   1 = Validation error (invalid MAC address format)
#   2 = iptables error (failed to remove rules)
#
# Important Notes:
# - This script requires sudo privileges for iptables commands
# - The script is idempotent (safe to run multiple times)
# - Removes rules from both INPUT and FORWARD chains
# - Returns success even if rules don't exist (already unblocked)
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
# Input:   MAC address string
# Output:  Returns 0 if valid, 1 if invalid
#
# See block_device.sh for detailed explanation of this function
################################################################################
validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        echo "Error: MAC address is required" >&2
        return 1
    fi
    
    # Validate MAC address format using regex
    # Pattern: 6 groups of 2 hex characters, separated by : or -
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0
    else
        echo "Error: Invalid MAC address format: $mac" >&2
        echo "Expected format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX" >&2
        echo "Example: AA:BB:CC:DD:EE:FF" >&2
        return 1
    fi
}

################################################################################
# Function: normalize_mac_address
# 
# Purpose: Convert MAC address to standard format (colons, uppercase)
#
# Input:   MAC address in any valid format
# Output:  Normalized MAC address (XX:XX:XX:XX:XX:XX)
#
# See block_device.sh for detailed explanation of this function
################################################################################
normalize_mac_address() {
    local mac="$1"
    
    # Convert hyphens to colons
    mac=$(echo "$mac" | sed 's/-/:/g')
    
    # Convert to uppercase
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    
    echo "$mac"
}

################################################################################
# Function: remove_block_rule
# 
# Purpose: Remove an iptables blocking rule for a device
#
# Input:   Chain name (INPUT or FORWARD) and MAC address
# Output:  Returns 0 on success (even if rule didn't exist)
#
# What This Function Does:
# - Attempts to remove DROP rules matching the MAC address
# - Removes rules one at a time until no more exist
# - Handles case where rule doesn't exist gracefully (idempotent)
#
# iptables Command Breakdown:
#   sudo iptables = Run iptables with administrator privileges
#   -D = Delete (remove rule from chain)
#   INPUT/FORWARD = Chain name
#   -i wlan0 = Input interface (WiFi interface)
#   -m mac = Match by MAC address
#   --mac-source = Source MAC address to match
#   -j DROP = The rule target (DROP)
#
# Why Loop?
# - iptables -D removes only ONE rule at a time
# - If multiple rules exist (shouldn't happen, but possible), we need to remove all
# - Loop continues until no more rules are found
################################################################################
remove_block_rule() {
    # $1 = Chain name (INPUT or FORWARD)
    # $2 = MAC address
    local chain="$1"
    local mac="$2"
    local removed_count=0
    
    # Loop to remove all matching rules (in case duplicates exist)
    # while true = Infinite loop (we'll break out when done)
    while true; do
        # Try to remove one rule
        # 2>&1 = Redirect stderr to stdout (capture both)
        # > /dev/null = Discard output (we only care about exit code)
        # || true = If command fails, continue anyway (don't exit script)
        if sudo iptables -D "$chain" -i wlan0 -m mac --mac-source "$mac" -j DROP 2>&1 > /dev/null; then
            # Rule was removed successfully
            removed_count=$((removed_count + 1))
            # Continue loop to check for more rules
        else
            # No more rules to remove (or rule didn't exist)
            # Break out of loop
            break
        fi
    done
    
    # Check if we removed any rules
    if [ $removed_count -gt 0 ]; then
        echo "Success: Removed $removed_count blocking rule(s) from $chain chain for MAC $mac" >&2
    else
        # No rules found - this is okay (device already unblocked)
        echo "Info: No blocking rules found in $chain chain for MAC $mac (already unblocked)" >&2
    fi
    
    return 0  # Always return success (idempotent)
}

################################################################################
# Main Script Execution
################################################################################

# Check if exactly one argument was provided
if [ $# -ne 1 ]; then
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF" >&2
    echo "Example: $0 AA-BB-CC-DD-EE-FF" >&2
    exit 1
fi

# Store the MAC address argument
MAC_ADDRESS="$1"

# Validate the MAC address format
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Normalize MAC address to standard format
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Display what we're doing
echo "Unblocking device with MAC address: $NORMALIZED_MAC" >&2

# Remove blocking rules from INPUT chain
# INPUT chain = Removes blocks on traffic to the Raspberry Pi
if ! remove_block_rule "INPUT" "$NORMALIZED_MAC"; then
    echo "Warning: Error removing rules from INPUT chain (may already be unblocked)" >&2
    # Don't exit - continue to try FORWARD chain
fi

# Remove blocking rules from FORWARD chain
# FORWARD chain = Removes blocks on traffic through the Pi (internet access)
if ! remove_block_rule "FORWARD" "$NORMALIZED_MAC"; then
    echo "Warning: Error removing rules from FORWARD chain (may already be unblocked)" >&2
    # Don't exit - device may still be unblocked
fi

# Success! Device is now unblocked (or was already unblocked)
echo "Device unblocked successfully" >&2
exit 0

