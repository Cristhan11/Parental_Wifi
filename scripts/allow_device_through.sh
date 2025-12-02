#!/bin/bash

################################################################################
# Allow Device Through Script
# 
# Purpose: Authenticate a device in NoDogSplash, allowing it to access the
#          internet normally after completing quiz/video.
#
# Usage:   ./allow_device_through.sh <MAC_ADDRESS>
# Example: ./allow_device_through.sh AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Finds the device's token using ndsctl clients
# 4. Authenticates the device using ndsctl authenticate
# 5. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device authenticated and can access internet)
#   1 = Validation error (invalid MAC address format)
#   2 = Device not found in NoDogSplash client list
#   3 = Failed to authenticate device
#
# Important Notes:
# - This script requires sudo privileges for ndsctl commands
# - The script is idempotent (safe to run multiple times)
# - Device can access internet normally after authentication
# - Device must be connected to WiFi and in NoDogSplash client list
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
# Function: find_device_token
# 
# Purpose: Find the device's token in NoDogSplash client list using MAC address
#
# Input:   MAC address (normalized)
# Output:  Token string if found, empty string if not found
################################################################################
find_device_token() {
    local mac="$1"
    
    # Check if ndsctl is available
    if [ ! -f "$NDSCTL" ]; then
        echo "Error: ndsctl not found at $NDSCTL" >&2
        return 1
    fi
    
    # Get client list from NoDogSplash
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    
    if [ -z "$client_info" ]; then
        echo "Warning: No clients found in NoDogSplash or ndsctl failed" >&2
        return 1
    fi
    
    # Parse client list to find token for this MAC address
    local token=""
    local current_mac=""
    local in_client_block=false
    
    # Convert MAC to lowercase for comparison (ndsctl may output lowercase)
    local mac_lower=$(echo "$mac" | tr '[:upper:]' '[:lower:]')
    
    while IFS= read -r line; do
        # Check if this is a client_id line (start of new client block)
        if [[ "$line" =~ ^client_id= ]]; then
            in_client_block=true
            current_mac=""
            token=""
        elif [[ "$line" =~ ^mac= ]]; then
            # Extract MAC address (remove "mac=" prefix)
            current_mac=$(echo "$line" | sed 's/^mac=//' | tr '[:upper:]' '[:lower:]')
        elif [[ "$line" =~ ^token= ]]; then
            # Extract token (remove "token=" prefix)
            token=$(echo "$line" | sed 's/^token=//')
        elif [ -z "$line" ]; then
            # Empty line - end of client block, check if we found our device
            if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
                echo "$token"
                return 0
            fi
            in_client_block=false
            current_mac=""
            token=""
        fi
    done <<< "$client_info"
    
    # Check last client block if file doesn't end with newline
    if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ]; then
        echo "$token"
        return 0
    fi
    
    # Device not found
    return 1
}

################################################################################
# Function: authenticate_device
# 
# Purpose: Authenticate a device using ndsctl, allowing it to access internet
#
# Input:   Token (from find_device_token)
# Output:  Returns 0 on success, 1 on error
################################################################################
authenticate_device() {
    local token="$1"
    
    if [ -z "$token" ]; then
        echo "Error: Token is required for authentication" >&2
        return 1
    fi
    
    # Authenticate device using ndsctl
    # This puts the device in Authenticated state
    # Device can now access internet normally
    if sudo "$NDSCTL" authenticate "$token" >/dev/null 2>&1; then
        echo "Info: Device authenticated successfully (token: $token)" >&2
        return 0
    else
        echo "Error: Failed to authenticate device (token: $token)" >&2
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
    exit 1
fi

# Store the MAC address argument
MAC_ADDRESS="$1"

# Validate the MAC address format
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Normalize MAC address to standard format (colons, uppercase)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Display what we're doing
echo "Authenticating device in NoDogSplash" >&2
echo "  MAC Address: $NORMALIZED_MAC" >&2

# Step 1: Find device token in NoDogSplash client list
echo "Info: Looking for device in NoDogSplash client list..." >&2
set +e  # Temporarily disable exit on error for token lookup
DEVICE_TOKEN=$(find_device_token "$NORMALIZED_MAC")
TOKEN_STATUS=$?
set -e  # Re-enable exit on error

if [ $TOKEN_STATUS -ne 0 ] || [ -z "$DEVICE_TOKEN" ]; then
    echo "Error: Device not found in NoDogSplash client list" >&2
    echo "Info: Device may not be connected to WiFi or may not be in NoDogSplash client list" >&2
    echo "Info: Make sure device is connected to the WiFi network" >&2
    exit 2
fi

echo "Info: Found device token: $DEVICE_TOKEN" >&2

# Step 2: Authenticate device (allow it to access internet)
if ! authenticate_device "$DEVICE_TOKEN"; then
    echo "Error: Failed to authenticate device" >&2
    exit 3
fi

# Success! Device is now authenticated and can access internet
echo "Device authenticated successfully" >&2
echo "Device $NORMALIZED_MAC can now access internet normally" >&2
echo "Info: Device is now in Authenticated state - can access internet" >&2
exit 0
