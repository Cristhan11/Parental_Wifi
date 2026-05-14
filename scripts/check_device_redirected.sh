#!/bin/bash

################################################################################
# Check Device Redirected Script
# 
# Purpose: Check if a device is currently being redirected to the portal.
#          This checks the device's authentication state in NoDogSplash.
#
# Usage:   ./check_device_redirected.sh <MAC_ADDRESS>
# Example: ./check_device_redirected.sh AA:BB:CC:DD:EE:FF
#
# Output:  Returns exit code 0 if device is redirected (Preauthenticated),
#          exit code 1 if device is not redirected (Authenticated)
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Finds the device in NoDogSplash client list using MAC address
# 4. Checks the device's state (Preauthenticated = redirected, Authenticated = not redirected)
# 5. Returns exit code indicating redirect status
#
# Exit Codes:
#   0 = Device is redirected (Preauthenticated state)
#   1 = Device is not redirected (Authenticated state or not found)
#   2 = Validation error (invalid MAC address format)
#   3 = ndsctl error (failed to query NoDogSplash)
#
# Important Notes:
# - This script requires sudo privileges to run ndsctl
# - The script only reads client list (doesn't modify anything)
# - Returns safe default (not redirected) if device not found
################################################################################

# Set script to exit immediately if any command fails
set -e

# Set script to exit if any variable is used before being set
set -u

################################################################################
# Configuration Constants
################################################################################

# NoDogSplash control command
NDSCTL="/usr/bin/ndsctl"

################################################################################
# Function: validate_mac_address
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
################################################################################
normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

################################################################################
# Function: check_device_state
# 
# Purpose: Check device's authentication state in NoDogSplash
#
# Input:   MAC address (normalized)
# Output:  Returns 0 if Preauthenticated (redirected), 1 if Authenticated (not redirected)
################################################################################
check_device_state() {
    local mac="$1"
    
    # Check if ndsctl is available
    if [ ! -f "$NDSCTL" ]; then
        echo "Error: ndsctl not found at $NDSCTL" >&2
        return 3
    fi
    
    # Get client list from NoDogSplash
    set +e  # Temporarily disable exit on error
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    set -e  # Re-enable exit on error
    
    if [ -z "$client_info" ]; then
        echo "Warning: No clients found in NoDogSplash or ndsctl failed" >&2
        echo "not_redirected" >&2
        return 1  # Not redirected (safe default)
    fi
    
    # Parse client list to find state for this MAC address
    local state=""
    local current_mac=""
    local in_client_block=false
    
    # Convert MAC to lowercase for comparison (ndsctl may output lowercase)
    local mac_lower=$(echo "$mac" | tr '[:upper:]' '[:lower:]')
    
    while IFS= read -r line; do
        # Check if this is a client_id line (start of new client block)
        if [[ "$line" =~ ^client_id= ]]; then
            if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ] && [ -n "$state" ]; then
                if [ "$state" = "Preauthenticated" ]; then
                    echo "redirected" >&2
                    return 0
                else
                    echo "not_redirected" >&2
                    return 1
                fi
            fi
            in_client_block=true
            current_mac=""
            state=""
        elif [[ "$line" =~ ^mac= ]]; then
            # Extract MAC address (remove "mac=" prefix)
            current_mac=$(echo "$line" | sed 's/^mac=//' | tr '[:upper:]' '[:lower:]')
        elif [[ "$line" =~ ^state= ]]; then
            if [ "$current_mac" = "$mac_lower" ]; then
                state=$(echo "$line" | sed 's/^state=//')
            fi
        elif [ -z "$line" ]; then
            # Empty line - end of client block, check if we found our device
            if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
                # Check state
                if [ "$state" = "Preauthenticated" ]; then
                    echo "redirected" >&2
                    return 0  # Redirected
                else
                    echo "not_redirected" >&2
                    return 1  # Not redirected
                fi
            fi
            in_client_block=false
            current_mac=""
            state=""
        fi
    done <<< "$client_info"
    
    # Check last client block if file doesn't end with newline
    if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
        if [ "$state" = "Preauthenticated" ]; then
            echo "redirected" >&2
            return 0  # Redirected
        else
            echo "not_redirected" >&2
            return 1  # Not redirected
        fi
    fi
    
    # Device not found - safe default (not redirected)
    echo "not_redirected" >&2
    return 1
}

################################################################################
# Main Script Execution
################################################################################

# Check if exactly one argument was provided
if [ $# -ne 1 ]; then
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF" >&2
    exit 2
fi

# Store the MAC address argument
MAC_ADDRESS="$1"

# Validate the MAC address format
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 2
fi

# Normalize MAC address to standard format (colons, uppercase)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Check device state
# This function returns:
# - Exit code 0 if device is redirected (Preauthenticated)
# - Exit code 1 if device is not redirected (Authenticated or not found)
set +e  # Temporarily disable exit on error
check_device_state "$NORMALIZED_MAC"
STATE_STATUS=$?
set -e  # Re-enable exit on error

# Return the state status
exit $STATE_STATUS

# Note: Exit codes are:
# 0 = Device is redirected (Preauthenticated state)
# 1 = Device is not redirected (Authenticated state or not found)
# This follows Unix convention where 0 = success/true, non-zero = failure/false
# In this context:
# - Exit code 0 means "yes, device is redirected" (Preauthenticated)
# - Exit code 1 means "no, device is not redirected" (Authenticated or not found)
