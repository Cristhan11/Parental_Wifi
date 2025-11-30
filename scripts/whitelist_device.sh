#!/bin/bash

################################################################################
# Whitelist Device Script
# 
# Purpose: Whitelist a device by removing blocking rules and adding explicit
#          ACCEPT rules at high priority. This ensures the device always has
#          unrestricted internet access, bypassing all restrictions.
#
# Usage:   ./whitelist_device.sh <MAC_ADDRESS>
# Example: ./whitelist_device.sh AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format
# 3. Removes any existing blocking rules (calls unblock logic)
# 4. Adds explicit ACCEPT rules at position 1 (highest priority) in INPUT and FORWARD chains
# 5. Ensures whitelisted devices bypass all restrictions
#
# Exit Codes:
#   0 = Success (device whitelisted)
#   1 = Validation error (invalid MAC address format)
#   2 = iptables error (failed to add/remove rules)
#
# Important Notes:
# - This script requires sudo privileges for iptables commands
# - The script is idempotent (safe to run multiple times)
# - ACCEPT rules are added at position 1 (checked first, highest priority)
# - Whitelisted devices bypass time limits, blocks, and all restrictions
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
        echo "Error: MAC address is required" >&2
        return 1
    fi
    
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
# See block_device.sh for detailed explanation
################################################################################
normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

################################################################################
# Function: remove_block_rule
# 
# Purpose: Remove blocking rules (same as unblock_device.sh)
#
# This function is identical to the one in unblock_device.sh
# We include it here so this script is self-contained
################################################################################
remove_block_rule() {
    local chain="$1"
    local mac="$2"
    local removed_count=0
    
    # Remove all matching DROP rules
    while true; do
        if sudo iptables -D "$chain" -i wlan0 -m mac --mac-source "$mac" -j DROP 2>&1 > /dev/null; then
            removed_count=$((removed_count + 1))
        else
            break
        fi
    done
    
    if [ $removed_count -gt 0 ]; then
        echo "Info: Removed $removed_count blocking rule(s) from $chain chain" >&2
    fi
    
    return 0
}

################################################################################
# Function: remove_accept_rule
# 
# Purpose: Remove existing ACCEPT rules (if any) before adding new ones
#
# Input:   Chain name and MAC address
# Output:  Returns 0 on success
#
# What This Function Does:
# - Removes any existing ACCEPT rules for this MAC address
# - Prevents duplicate ACCEPT rules
# - Idempotent (safe to run multiple times)
################################################################################
remove_accept_rule() {
    local chain="$1"
    local mac="$2"
    local removed_count=0
    
    # Remove all existing ACCEPT rules for this MAC
    while true; do
        if sudo iptables -D "$chain" -i wlan0 -m mac --mac-source "$mac" -j ACCEPT 2>&1 > /dev/null; then
            removed_count=$((removed_count + 1))
        else
            break
        fi
    done
    
    if [ $removed_count -gt 0 ]; then
        echo "Info: Removed $removed_count existing ACCEPT rule(s) from $chain chain" >&2
    fi
    
    return 0
}

################################################################################
# Function: add_whitelist_rule
# 
# Purpose: Add explicit ACCEPT rule at position 1 (highest priority)
#
# Input:   Chain name (INPUT or FORWARD) and MAC address
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Adds ACCEPT rule at position 1 (checked FIRST, before all other rules)
# - This ensures whitelisted devices always pass through
# - Even if other rules try to block, this rule is checked first
#
# iptables Command Breakdown:
#   sudo iptables = Run iptables with administrator privileges
#   -I = Insert (add rule at specific position)
#   INPUT/FORWARD 1 = Insert at position 1 (first position, highest priority)
#   -i wlan0 = Input interface (WiFi interface)
#   -m mac = Match by MAC address
#   --mac-source = Source MAC address to match
#   -j ACCEPT = Action: Accept (allow) the packet
#
# Why Position 1?
# - iptables checks rules in order (top to bottom)
# - First matching rule wins
# - Position 1 = checked FIRST, before any blocking rules
# - This guarantees whitelisted devices always pass through
################################################################################
add_whitelist_rule() {
    # $1 = Chain name (INPUT or FORWARD)
    # $2 = MAC address
    local chain="$1"
    local mac="$2"
    
    # First, remove any existing ACCEPT rules (prevent duplicates)
    remove_accept_rule "$chain" "$mac"
    
    # Add ACCEPT rule at position 1 (highest priority)
    # -I = Insert (add rule at specific position)
    # $chain 1 = Insert at position 1 in the chain (first position)
    # This ensures this rule is checked BEFORE any blocking rules
    if sudo iptables -I "$chain" 1 -i wlan0 -m mac --mac-source "$mac" -j ACCEPT; then
        echo "Success: Added whitelist rule at position 1 in $chain chain for MAC $mac" >&2
        return 0
    else
        echo "Error: Failed to add whitelist rule to $chain chain for MAC $mac" >&2
        return 1
    fi
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
echo "Whitelisting device with MAC address: $NORMALIZED_MAC" >&2

# Step 1: Remove any existing blocking rules
# This ensures the device is not blocked before we add ACCEPT rules
echo "Step 1: Removing any existing blocking rules..." >&2
remove_block_rule "INPUT" "$NORMALIZED_MAC"
remove_block_rule "FORWARD" "$NORMALIZED_MAC"

# Step 2: Add ACCEPT rules at position 1 (highest priority)
# This ensures the device always passes through, even if other rules try to block
echo "Step 2: Adding whitelist rules at highest priority..." >&2

# Add whitelist rule to INPUT chain
if ! add_whitelist_rule "INPUT" "$NORMALIZED_MAC"; then
    echo "Error: Failed to whitelist device on INPUT chain" >&2
    exit 2
fi

# Add whitelist rule to FORWARD chain
if ! add_whitelist_rule "FORWARD" "$NORMALIZED_MAC"; then
    echo "Error: Failed to whitelist device on FORWARD chain" >&2
    exit 2
fi

# Success! Device is now whitelisted
echo "Device whitelisted successfully" >&2
echo "Device will now bypass all restrictions and have unrestricted internet access" >&2
exit 0

