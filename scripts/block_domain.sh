#!/bin/bash

################################################################################
# Block Domain Script
# 
# Purpose: Block a domain for a specific device using dnsmasq DNS blocking.
#          This prevents the device from accessing the domain by redirecting
#          DNS queries to 127.0.0.1 (localhost).
#
# Usage:   ./block_domain.sh <DOMAIN> <MAC_ADDRESS> [BLOCK_SUBDOMAINS]
# Example: ./block_domain.sh facebook.com AA:BB:CC:DD:EE:FF 1
#
# What This Script Does:
# 1. Validates the domain format
# 2. Validates the MAC address format
# 3. Normalizes MAC address to standard format (with colons)
# 4. Adds domain to dnsmasq blocklist for the device
# 5. Handles subdomain blocking if BLOCK_SUBDOMAINS is 1
# 6. Restarts dnsmasq service to apply changes
# 7. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (domain blocked)
#   1 = Validation error (invalid domain or MAC address format)
#   2 = dnsmasq error (failed to add domain or restart service)
#
# Important Notes:
# - This script requires sudo privileges for dnsmasq operations
# - The script is idempotent (safe to run multiple times - won't create duplicates)
# - Creates per-device config files in /etc/dnsmasq.d/
# - Format: blocked-domains-{MAC_ADDRESS}.conf
# - dnsmasq config format: address=/domain.com/127.0.0.1
# - For subdomains: address=/.domain.com/127.0.0.1 (note the leading dot)
#
# DNS Blocking How It Works:
# - dnsmasq intercepts DNS queries from devices
# - When device asks "What's the IP for facebook.com?"
# - dnsmasq checks if domain is in blocklist
# - If blocked, returns 127.0.0.1 instead of real IP
# - Device can't connect because 127.0.0.1 is not the real server
# - This works for both web browsers AND mobile apps
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
# - Prevents injection attacks by validating format
################################################################################
validate_domain() {
    local domain="$1"
    
    # Check if domain is empty
    if [ -z "$domain" ]; then
        echo "Error: Domain cannot be empty" >&2
        return 1
    fi
    
    # Check domain format: letters, numbers, dots, hyphens
    # Must contain at least one dot (e.g., example.com)
    # Must not start or end with dot or hyphen
    if ! echo "$domain" | grep -qE '^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$'; then
        echo "Error: Invalid domain format: $domain" >&2
        echo "Domain must be in format: example.com" >&2
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
#
# What This Function Does:
# - Checks if MAC address matches standard format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX)
# - Uses regex pattern matching to validate format
# - Ensures exactly 6 groups of 2 hexadecimal characters
################################################################################
validate_mac_address() {
    local mac="$1"
    
    # Check if MAC address is empty
    if [ -z "$mac" ]; then
        echo "Error: MAC address cannot be empty" >&2
        return 1
    fi
    
    # Check MAC address format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
    # Must be exactly 6 groups of 2 hexadecimal characters
    if ! echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        echo "Error: Invalid MAC address format: $mac" >&2
        echo "MAC address must be in format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX" >&2
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
#
# What This Function Does:
# - Converts hyphens to colons (AA-BB-CC -> AA:BB:CC)
# - Converts to uppercase for consistency
# - Ensures standard format for dnsmasq config file names
################################################################################
normalize_mac_address() {
    local mac="$1"
    
    # Replace hyphens with colons
    mac=$(echo "$mac" | tr '-' ':')
    
    # Convert to uppercase
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    
    echo "$mac"
}

################################################################################
# Main Script Logic
################################################################################

# Check if correct number of arguments provided
if [ $# -lt 2 ] || [ $# -gt 3 ]; then
    echo "Usage: $0 <DOMAIN> <MAC_ADDRESS> [BLOCK_SUBDOMAINS]" >&2
    echo "Example: $0 facebook.com AA:BB:CC:DD:EE:FF 1" >&2
    echo "" >&2
    echo "Arguments:" >&2
    echo "  DOMAIN           Domain to block (e.g., facebook.com)" >&2
    echo "  MAC_ADDRESS      Device MAC address (e.g., AA:BB:CC:DD:EE:FF)" >&2
    echo "  BLOCK_SUBDOMAINS Optional: 1 to block subdomains, 0 or omit to block only main domain" >&2
    exit 1
fi

# Get arguments
DOMAIN="$1"
MAC_ADDRESS="$2"
BLOCK_SUBDOMAINS="${3:-0}"  # Default to 0 if not provided

# Validate inputs
if ! validate_domain "$DOMAIN"; then
    exit 1
fi

if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# Normalize MAC address to standard format
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# dnsmasq config directory
DNSMASQ_DIR="/etc/dnsmasq.d"
CONFIG_FILE="${DNSMASQ_DIR}/blocked-domains-${NORMALIZED_MAC}.conf"

# Ensure dnsmasq.d directory exists
if [ ! -d "$DNSMASQ_DIR" ]; then
    echo "Error: dnsmasq.d directory does not exist: $DNSMASQ_DIR" >&2
    exit 2
fi

# Create config file if it doesn't exist
if [ ! -f "$CONFIG_FILE" ]; then
    sudo touch "$CONFIG_FILE"
    sudo chmod 644 "$CONFIG_FILE"
    echo "# Blocked domains for device ${NORMALIZED_MAC}" | sudo tee "$CONFIG_FILE" > /dev/null
    echo "# Generated by block_domain.sh" | sudo tee -a "$CONFIG_FILE" > /dev/null
    echo "" | sudo tee -a "$CONFIG_FILE" > /dev/null
fi

# Check if domain is already blocked (idempotent check)
if grep -q "address=/${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null || \
   grep -q "address=/.${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null; then
    echo "Domain $DOMAIN is already blocked for device ${NORMALIZED_MAC}"
    exit 0
fi

# Add domain to blocklist
# Format: address=/domain.com/127.0.0.1
# For subdomains: address=/.domain.com/127.0.0.1 (note the leading dot)
if [ "$BLOCK_SUBDOMAINS" = "1" ]; then
    # Block subdomains (wildcard pattern)
    echo "address=/.${DOMAIN}/127.0.0.1" | sudo tee -a "$CONFIG_FILE" > /dev/null
    echo "Blocked domain $DOMAIN and all subdomains (*.$DOMAIN) for device ${NORMALIZED_MAC}"
else
    # Block only main domain
    echo "address=/${DOMAIN}/127.0.0.1" | sudo tee -a "$CONFIG_FILE" > /dev/null
    echo "Blocked domain $DOMAIN for device ${NORMALIZED_MAC}"
fi

# Note: We do NOT reload dnsmasq here because:
# 1. block_domain.sh is called multiple times for app-level blocking (one per domain)
# 2. Reloading after each domain causes rapid restarts that hit systemd start-limit
# 3. update_dnsmasq_blocklist.sh will reload dnsmasq once at the end after all domains are added
# This prevents "start-limit-hit" errors when blocking apps with many domains

echo "Successfully blocked domain $DOMAIN for device ${NORMALIZED_MAC}"
echo "Note: dnsmasq will be reloaded after all domains are processed"
exit 0

