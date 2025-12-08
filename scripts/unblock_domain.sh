#!/bin/bash

################################################################################
# Unblock Domain Script
# 
# Purpose: Unblock a domain for a specific device by removing it from
#          the dnsmasq blocklist. This allows the device to access the domain again.
#
# Usage:   ./unblock_domain.sh <DOMAIN> <MAC_ADDRESS>
# Example: ./unblock_domain.sh facebook.com AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the domain format
# 2. Validates the MAC address format
# 3. Normalizes MAC address to standard format (with colons)
# 4. Removes domain from dnsmasq blocklist for the device
# 5. Restarts dnsmasq service to apply changes
# 6. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (domain unblocked, or already unblocked)
#   1 = Validation error (invalid domain or MAC address format)
#   2 = dnsmasq error (failed to remove domain or restart service)
#
# Important Notes:
# - This script requires sudo privileges for dnsmasq operations
# - The script is idempotent (safe to run multiple times - won't error if already unblocked)
# - Removes both main domain and subdomain entries if present
# - Handles case where domain is not in blocklist (returns success)
################################################################################

# Set script to exit immediately if any command fails
# This prevents the script from continuing after an error
set -e

# Set script to exit if any variable is used before being set
# This prevents bugs from uninitialized variables
set -u

################################################################################
# Function: validate_domain
# 
# Purpose: Check if the domain is in a valid format
#
# Input:   Domain string (e.g., "facebook.com" or "api.facebook.com")
# Output:  Returns 0 if valid, 1 if invalid
#
# What This Function Does:
# - Checks if domain matches standard format (letters, numbers, dots, hyphens)
# - Ensures domain is not empty
# - Validates basic domain structure (at least one dot, valid TLD)
################################################################################
validate_domain() {
    local domain="$1"
    
    # Check if domain is empty
    if [ -z "$domain" ]; then
        echo "Error: Domain cannot be empty" >&2
        return 1
    fi
    
    # Check domain format: letters, numbers, dots, hyphens
    if ! echo "$domain" | grep -qE '^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$'; then
        echo "Error: Invalid domain format: $domain" >&2
        return 1
    fi
    
    return 0
}

################################################################################
# Function: validate_mac_address
# 
# Purpose: Check if the MAC address is in a valid format
#
# Input:   MAC address string (e.g., "AA:BB:CC:DD:EE:FF" or "AA-BB-CC-DD-EE-FF")
# Output:  Returns 0 if valid, 1 if invalid
################################################################################
validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        echo "Error: MAC address cannot be empty" >&2
        return 1
    fi
    
    if ! echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        echo "Error: Invalid MAC address format: $mac" >&2
        return 1
    fi
    
    return 0
}

################################################################################
# Function: normalize_mac_address
# 
# Purpose: Normalize MAC address to standard format (with colons)
#
# Input:   MAC address string (any format)
# Output:  Normalized MAC address (XX:XX:XX:XX:XX:XX)
################################################################################
normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | tr '-' ':')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    echo "$mac"
}

################################################################################
# Main Script Logic
################################################################################

# Check if correct number of arguments provided
if [ $# -ne 2 ]; then
    echo "Usage: $0 <DOMAIN> <MAC_ADDRESS>" >&2
    echo "Example: $0 facebook.com AA:BB:CC:DD:EE:FF" >&2
    exit 1
fi

# Get arguments
DOMAIN="$1"
MAC_ADDRESS="$2"

# Validate inputs
if ! validate_domain "$DOMAIN"; then
    exit 1
fi

if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Normalize MAC address
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# dnsmasq config directory
DNSMASQ_DIR="/etc/dnsmasq.d"
CONFIG_FILE="${DNSMASQ_DIR}/blocked-domains-${NORMALIZED_MAC}.conf"

# Check if config file exists
if [ ! -f "$CONFIG_FILE" ]; then
    echo "No blocklist found for device ${NORMALIZED_MAC} (config file does not exist)"
    echo "Domain $DOMAIN is not blocked (or already unblocked)"
    exit 0
fi

# Check if domain is in blocklist
if ! grep -q "address=/${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null && \
   ! grep -q "address=/.${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null; then
    echo "Domain $DOMAIN is not in blocklist for device ${NORMALIZED_MAC}"
    echo "Domain is already unblocked (or was never blocked)"
    exit 0
fi

# Remove domain from blocklist (both main domain and subdomain entries)
# Use sed to remove lines containing the domain
sudo sed -i "/address=\/${DOMAIN}\/127\.0\.0\.1/d" "$CONFIG_FILE"
sudo sed -i "/address=\/\.${DOMAIN}\/127\.0\.0\.1/d" "$CONFIG_FILE"

# If config file is now empty (except comments), remove it
if [ -f "$CONFIG_FILE" ] && [ $(grep -v '^#' "$CONFIG_FILE" | grep -v '^$' | wc -l) -eq 0 ]; then
    sudo rm "$CONFIG_FILE"
    echo "Removed empty blocklist file for device ${NORMALIZED_MAC}"
fi

# Restart dnsmasq service to apply changes
if ! sudo systemctl restart dnsmasq; then
    echo "Error: Failed to restart dnsmasq service" >&2
    exit 2
fi

# Verify dnsmasq is running
if ! systemctl is-active --quiet dnsmasq; then
    echo "Error: dnsmasq service is not running after restart" >&2
    exit 2
fi

echo "Successfully unblocked domain $DOMAIN for device ${NORMALIZED_MAC}"
exit 0

