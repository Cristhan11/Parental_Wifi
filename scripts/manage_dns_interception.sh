#!/bin/bash

################################################################################
# Manage DNS Interception Script
# 
# Purpose: Enable or disable DNS interception for a device by managing dnsmasq
#          configuration. This allows HTTPS requests to be intercepted by
#          redirecting all DNS queries to the gateway IP (192.168.4.1).
#
# Usage:   ./manage_dns_interception.sh <MAC_ADDRESS> <ACTION>
# Example: ./manage_dns_interception.sh AA:BB:CC:DD:EE:FF add
#          ./manage_dns_interception.sh AA:BB:CC:DD:EE:FF remove
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Finds the device's IP address using ndsctl clients
# 4. Adds or removes the IP from dnsmasq captive portal config
# 5. Reloads dnsmasq service to apply changes
# 6. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (DNS interception enabled/disabled)
#   1 = Validation error (invalid MAC address or action)
#   2 = Device not found in NoDogSplash client list
#   3 = Failed to update dnsmasq configuration
#   4 = Failed to reload dnsmasq service
#
# Important Notes:
# - This script requires sudo privileges for ndsctl and dnsmasq commands
# - The script is idempotent (safe to run multiple times)
# - DNS interception redirects all DNS queries to 192.168.4.1
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

# dnsmasq configuration directory
DNSMASQ_CONF_DIR="/etc/dnsmasq.d"

# Captive portal config file
CAPTIVE_PORTAL_CONF="$DNSMASQ_CONF_DIR/captive-portal.conf"

# Gateway IP (where DNS queries are redirected)
GATEWAY_IP="192.168.4.1"

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
# Function: find_device_ip
# 
# Purpose: Find the device's IP address in NoDogSplash client list using MAC address
#
# Input:   MAC address (normalized)
# Output:  IP address if found, empty string if not found
################################################################################
find_device_ip() {
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
    
    # Parse output - ndsctl clients outputs each field on a separate line
    # Format:
    # 1
    # client_id=0
    # ip=192.168.4.32
    # mac=e6:6a:8f:19:be:b1
    # ...
    # Each client block starts with client_id, then ip, then mac, etc.
    local current_ip=""
    while IFS= read -r line; do
        # Skip empty lines and the count line (just a number)
        [ -z "$line" ] && continue
        echo "$line" | grep -qE "^[0-9]+$" && continue
        
        # Check if this line contains an IP address
        if echo "$line" | grep -qE "^ip=[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$"; then
            current_ip=$(echo "$line" | cut -d= -f2)
        fi
        # Check if this line contains the MAC address we're looking for
        if echo "$line" | grep -qiE "^mac=$mac$"; then
            if [ -n "$current_ip" ]; then
                echo "$current_ip"
                return 0
            fi
        fi
        # Reset IP when we hit a new client (client_id line)
        # This ensures we only match IP with MAC from the same client
        if echo "$line" | grep -qE "^client_id="; then
            current_ip=""
        fi
    done <<< "$client_info"
    
    return 1
}

################################################################################
# Function: ensure_config_directory
################################################################################
ensure_config_directory() {
    if [ ! -d "$DNSMASQ_CONF_DIR" ]; then
        echo "Info: Creating dnsmasq config directory: $DNSMASQ_CONF_DIR" >&2
        sudo mkdir -p "$DNSMASQ_CONF_DIR"
    fi
}

################################################################################
# Function: ensure_config_file
################################################################################
ensure_config_file() {
    if [ ! -f "$CAPTIVE_PORTAL_CONF" ]; then
        echo "Info: Creating captive portal config file: $CAPTIVE_PORTAL_CONF" >&2
        sudo tee "$CAPTIVE_PORTAL_CONF" > /dev/null <<EOF
# Captive Portal DNS Interception
# This file is automatically managed by Laravel scripts
# DO NOT EDIT MANUALLY
#
# Format: address=/#/192.168.4.1
# This redirects all DNS queries to gateway IP
# Only IPs listed here will have DNS interception

EOF
    fi
}

################################################################################
# Function: add_dns_interception
################################################################################
add_dns_interception() {
    local ip="$1"
    
    # Ensure config directory and file exist
    ensure_config_directory
    ensure_config_file
    
    # Check if DNS interception is already enabled globally
    # We use a global rule that affects all devices when any device needs interception
    # This is simpler than per-IP filtering (which dnsmasq doesn't support well)
    if sudo grep -q "^address=/#/$GATEWAY_IP" "$CAPTIVE_PORTAL_CONF" 2>/dev/null; then
        echo "Info: DNS interception already enabled globally" >&2
        return 0
    fi
    
    # Add DNS interception rule
    # This redirects all DNS queries (/#/) to gateway IP for ALL devices
    # Note: This is a global rule. Authenticated devices will still work because
    # NoDogSplash allows them through, but they'll connect to gateway IP.
    # For proper HTTPS support, we need this global interception.
    echo "Info: Enabling DNS interception globally (redirects all DNS to $GATEWAY_IP)" >&2
    echo "address=/#/$GATEWAY_IP" | sudo tee -a "$CAPTIVE_PORTAL_CONF" > /dev/null
    
    # Reload dnsmasq to apply changes
    if sudo systemctl reload dnsmasq >/dev/null 2>&1; then
        echo "Info: dnsmasq reloaded successfully" >&2
        return 0
    else
        echo "Error: Failed to reload dnsmasq service" >&2
        return 1
    fi
}

################################################################################
# Function: check_preauthenticated_devices
# 
# Purpose: Check if there are any Preauthenticated devices in NoDogSplash
#          Returns 0 if any Preauthenticated devices exist, 1 if none
################################################################################
check_preauthenticated_devices() {
    # Get client list from NoDogSplash
    local client_info=$(sudo "$NDSCTL" clients 2>/dev/null || echo "")
    
    if [ -z "$client_info" ]; then
        return 1  # No clients, so no Preauthenticated devices
    fi
    
    # Check if any device has state=Preauthenticated
    if echo "$client_info" | grep -qi "state=Preauthenticated"; then
        return 0  # Found at least one Preauthenticated device
    fi
    
    return 1  # No Preauthenticated devices found
}

################################################################################
# Function: remove_dns_interception
# 
# Note: This removes DNS interception globally. We check if there are any
# other Preauthenticated devices before removing. If other devices still
# need interception, we keep it enabled.
################################################################################
remove_dns_interception() {
    local ip="$1"
    
    # Check if config file exists
    if [ ! -f "$CAPTIVE_PORTAL_CONF" ]; then
        echo "Info: Config file does not exist, nothing to remove" >&2
        return 0
    fi
    
    # Check if DNS interception rule exists
    if ! sudo grep -q "^address=/#/$GATEWAY_IP" "$CAPTIVE_PORTAL_CONF" 2>/dev/null; then
        echo "Info: DNS interception not enabled, nothing to remove" >&2
        return 0
    fi
    
    # Check if there are any other Preauthenticated devices
    # If yes, keep DNS interception enabled (don't remove)
    if check_preauthenticated_devices; then
        echo "Info: Other Preauthenticated devices exist, keeping DNS interception enabled" >&2
        return 0
    fi
    
    # No other Preauthenticated devices, safe to remove DNS interception
    echo "Info: No other Preauthenticated devices, removing DNS interception rule" >&2
    sudo sed -i "/^address=\/#\/$GATEWAY_IP$/d" "$CAPTIVE_PORTAL_CONF"
    
    # Reload dnsmasq to apply changes
    if sudo systemctl reload dnsmasq >/dev/null 2>&1; then
        echo "Info: dnsmasq reloaded successfully" >&2
        return 0
    else
        echo "Error: Failed to reload dnsmasq service" >&2
        return 1
    fi
}

################################################################################
# Main Script Execution
################################################################################

# Check arguments
if [ $# -ne 2 ]; then
    echo "Usage: $0 <MAC_ADDRESS> <ACTION>" >&2
    echo "  ACTION: 'add' to enable DNS interception, 'remove' to disable" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF add" >&2
    exit 1
fi

MAC_ADDRESS="$1"
ACTION="$2"

# Validate action
if [ "$ACTION" != "add" ] && [ "$ACTION" != "remove" ]; then
    echo "Error: Invalid action: $ACTION" >&2
    echo "Action must be 'add' or 'remove'" >&2
    exit 1
fi

# Validate MAC address
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Normalize MAC address
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Find device IP address
DEVICE_IP=$(find_device_ip "$NORMALIZED_MAC")
if [ -z "$DEVICE_IP" ]; then
    echo "Error: Device not found in NoDogSplash client list" >&2
    echo "Info: Device may not be connected to WiFi or may not be in NoDogSplash client list" >&2
    echo "Info: Make sure device is connected to the WiFi network" >&2
    exit 2
fi

echo "Info: Found device IP: $DEVICE_IP" >&2

# Perform action
if [ "$ACTION" = "add" ]; then
    if ! add_dns_interception "$DEVICE_IP"; then
        exit 4
    fi
    echo "Info: DNS interception enabled for device (IP: $DEVICE_IP)" >&2
else
    if ! remove_dns_interception "$DEVICE_IP"; then
        exit 4
    fi
    echo "Info: DNS interception disabled for device (IP: $DEVICE_IP)" >&2
fi

exit 0

