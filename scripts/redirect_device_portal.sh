#!/bin/bash

################################################################################
# Redirect Device Portal Script
# 
# Purpose: Configure NoDogSplash to redirect a device to the portal page.
#          This deauthenticates the device, putting it back in Preauthenticated
#          state, which causes NoDogSplash to redirect all HTTP requests to
#          the portal.
#
# Usage:   ./redirect_device_portal.sh <MAC_ADDRESS> <PORTAL_URL>
# Example: ./redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Validates the portal URL format
# 4. Finds the device's token using ndsctl clients
# 5. Deauthenticates the device using ndsctl deauth
# 6. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device deauthenticated and will be redirected)
#   1 = Validation error (invalid MAC address or URL format)
#   2 = Device not found in NoDogSplash client list
#   3 = Failed to deauthenticate device
#
# Important Notes:
# - This script requires sudo privileges for ndsctl commands
# - The script is idempotent (safe to run multiple times)
# - NoDogSplash will redirect device on next HTTP request
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
# Function: validate_portal_url
################################################################################
validate_portal_url() {
    local url="$1"
    
    if [ -z "$url" ]; then
        echo "Error: Portal URL is required" >&2
        return 1
    fi
    
    if echo "$url" | grep -qE '^https?://'; then
        return 0
    else
        echo "Error: Invalid portal URL format: $url" >&2
        echo "Expected format: http://HOST/PATH or https://HOST/PATH" >&2
        echo "Example: http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF" >&2
        return 1
    fi
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
    # ndsctl clients outputs client information in a structured format
    # We need to parse it to find the token for the given MAC address
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    
    if [ -z "$client_info" ]; then
        echo "Warning: No clients found in NoDogSplash or ndsctl failed" >&2
        return 1
    fi
    
    # Parse client list to find token for this MAC address
    # Client list format:
    # client_id=0
    # ip=192.168.4.31
    # mac=42:b8:77:ae:74:12
    # token=d864b6e8
    # state=Authenticated
    #
    # We need to find the token for the matching MAC address
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
# Function: get_device_state
# 
# Purpose: Get the current authentication state of a device
#
# Input:   MAC address (normalized)
# Output:  State string (Preauthenticated, Authenticated, or empty if not found)
################################################################################
get_device_state() {
    local mac="$1"
    local mac_lower=$(echo "$mac" | tr '[:upper:]' '[:lower:]')
    
    # Get client list and parse for this MAC
    set +e
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    set -e
    
    if [ -z "$client_info" ]; then
        return 1
    fi
    
    # Parse client list to find state for this MAC address
    local state=""
    local current_mac=""
    local in_client_block=false
    
    while IFS= read -r line; do
        if [[ "$line" =~ ^client_id= ]]; then
            in_client_block=true
            current_mac=""
            state=""
        elif [[ "$line" =~ ^mac= ]]; then
            current_mac=$(echo "$line" | sed 's/^mac=//' | tr '[:upper:]' '[:lower:]')
        elif [[ "$line" =~ ^state= ]]; then
            if [ "$current_mac" = "$mac_lower" ]; then
                state=$(echo "$line" | sed 's/^state=//')
            fi
        elif [ -z "$line" ]; then
            if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ] && [ -n "$state" ]; then
                echo "$state"
                return 0
            fi
            in_client_block=false
            current_mac=""
            state=""
        fi
    done <<< "$client_info"
    
    # Check last client block
    if [ "$in_client_block" = true ] && [ "$current_mac" = "$mac_lower" ] && [ -n "$state" ]; then
        echo "$state"
        return 0
    fi
    
    return 1
}

################################################################################
# Function: deauthenticate_device
# 
# Purpose: Deauthenticate a device using ndsctl, putting it back in
#          Preauthenticated state so it gets redirected to portal
#
# Input:   Token (from find_device_token)
# Output:  Returns 0 on success, 1 on error
################################################################################
deauthenticate_device() {
    local token="$1"
    
    if [ -z "$token" ]; then
        echo "Error: Token is required for deauthentication" >&2
        return 1
    fi
    
    # Deauthenticate device using ndsctl deauth (not deauthenticate)
    # This puts the device back in Preauthenticated state
    # NoDogSplash will then redirect all HTTP requests to RedirectURL
    if sudo "$NDSCTL" deauth "$token" >/dev/null 2>&1; then
        echo "Info: Device deauthenticated successfully (token: $token)" >&2
        return 0
    else
        echo "Error: Failed to deauthenticate device (token: $token)" >&2
        return 1
    fi
}

################################################################################
# Main Script Execution
################################################################################

# Check if exactly two arguments were provided
if [ $# -ne 2 ]; then
    echo "Usage: $0 <MAC_ADDRESS> <PORTAL_URL>" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF \"http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF\"" >&2
    exit 1
fi

# Store the arguments
MAC_ADDRESS="$1"
PORTAL_URL="$2"

# Validate the MAC address format
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Validate the portal URL format
if ! validate_portal_url "$PORTAL_URL"; then
    exit 1
fi

# Normalize MAC address to standard format (colons, uppercase)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Display what we're doing
echo "Configuring NoDogSplash to redirect device to portal" >&2
echo "  MAC Address: $NORMALIZED_MAC" >&2
echo "  Portal URL: $PORTAL_URL" >&2

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

# Step 2: Check device state first (optimization - skip if already Preauthenticated)
set +e
DEVICE_STATE=$(get_device_state "$NORMALIZED_MAC")
STATE_STATUS=$?
set -e

if [ $STATE_STATUS -eq 0 ] && [ "$DEVICE_STATE" = "Preauthenticated" ]; then
    echo "Info: Device is already in Preauthenticated state - redirect is active" >&2
    echo "Device redirect configured successfully" >&2
    echo "Device $NORMALIZED_MAC will be redirected to portal on next HTTP request" >&2
    exit 0
fi

# Step 3: Deauthenticate device (put it back in Preauthenticated state)
if ! deauthenticate_device "$DEVICE_TOKEN"; then
    echo "Error: Failed to deauthenticate device" >&2
    exit 3
fi

# Success! Device is now in Preauthenticated state and will be redirected
echo "Device redirect configured successfully" >&2
echo "Device $NORMALIZED_MAC will be redirected to portal on next HTTP request" >&2
echo "Info: Device is now in Preauthenticated state - NoDogSplash will redirect HTTP requests to portal" >&2
exit 0
