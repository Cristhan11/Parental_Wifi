#!/bin/bash

################################################################################
# Check Device Redirected Script
# 
# Purpose: Check if a device is currently in NoDogSplash blocklist/redirect list.
#          This tells us if the device will be redirected to portal on next HTTP request.
#
# Usage:   ./check_device_redirected.sh <MAC_ADDRESS>
# Example: ./check_device_redirected.sh AA:BB:CC:DD:EE:FF
#
# Output:  Returns exit code 0 if device is redirected, 1 if not redirected
#          Also outputs "redirected" or "not_redirected" to stdout for easy parsing
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Checks NoDogSplash config file for device MAC address in blocklist
# 4. Returns exit code indicating redirect status
#
# Exit Codes:
#   0 = Device is redirected (found in blocklist)
#   1 = Device is not redirected (not found in blocklist or config doesn't exist)
#   2 = Validation error (invalid MAC address format)
#   3 = Config file read error (failed to read config file)
#
# Important Notes:
# - This script requires sudo privileges to read NoDogSplash config file
# - The script only reads config file (doesn't modify anything)
# - Config file location: /etc/nodogsplash/nodogsplash.conf (standard location)
# - Returns safe default (not redirected) if config file doesn't exist
################################################################################

# Set script to exit immediately if any command fails
# This prevents the script from continuing after an error
set -e

# Set script to exit if any variable is used before being set
# This prevents bugs from uninitialized variables
set -u

################################################################################
# Configuration Constants
################################################################################

# NoDogSplash configuration file location (standard location)
# This is where NoDogSplash reads its configuration
NODOGSPLASH_CONFIG="/etc/nodogsplash/nodogsplash.conf"

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
    # $1 = First argument passed to function (the MAC address)
    local mac="$1"
    
    # Check if MAC address is empty (no argument provided)
    # -z checks if string is empty (zero length)
    if [ -z "$mac" ]; then
        # Write error message to stderr (standard error output)
        # >&2 redirects output to stderr instead of stdout
        echo "Error: MAC address is required" >&2
        return 1  # Return error code
    fi
    
    # Validate MAC address format using regex pattern
    # Pattern explanation:
    #   ^ = Start of string
    #   ([0-9A-Fa-f]{2}) = Exactly 2 hexadecimal characters (0-9, A-F, case insensitive)
    #   [:-] = Separator (colon OR hyphen)
    #   This pattern repeats 5 times (for 6 total groups)
    #   $ = End of string
    # grep -q = Quiet mode (don't output, just return exit code)
    # grep -E = Extended regex mode
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0  # Valid format
    else
        # Invalid format - show error message
        echo "Error: Invalid MAC address format: $mac" >&2
        echo "Expected format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX" >&2
        echo "Example: AA:BB:CC:DD:EE:FF" >&2
        return 1  # Return error code
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
# What This Function Does:
# - Converts hyphens to colons (standard format)
# - Converts to uppercase (for consistency)
# - Uses sed (stream editor) to perform replacements
################################################################################
normalize_mac_address() {
    # $1 = First argument (the MAC address)
    local mac="$1"
    
    # Convert hyphens to colons using sed
    # s/-/:/g = Substitute all hyphens with colons (g = global, all occurrences)
    mac=$(echo "$mac" | sed 's/-/:/g')
    
    # Convert to uppercase using tr (translate characters)
    # tr '[:lower:]' '[:upper:]' = Translate all lowercase to uppercase
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    
    # Output the normalized MAC address
    # This is captured by the caller using $(normalize_mac_address ...)
    echo "$mac"
}

################################################################################
# Function: check_device_in_blocklist
# 
# Purpose: Check if device MAC address is in NoDogSplash blocklist
#
# Input:   MAC address (normalized)
# Output:  Returns 0 if device is in blocklist, 1 if not
#
# What This Function Does:
# - Checks if config file exists (returns "not redirected" if it doesn't)
# - Searches config file for device MAC address in blocklist entries
# - Uses case-insensitive matching to find device
# - Returns safe default (not redirected) if config file doesn't exist
#
# NoDogSplash Configuration Format:
# - BlockList: MAC addresses that should be blocked/redirected
# - Format: BlockList MAC_ADDRESS
# - We search for lines matching: BlockList MAC_ADDRESS (case insensitive)
################################################################################
check_device_in_blocklist() {
    # $1 = MAC address (normalized)
    local mac="$1"
    
    # Check if config file exists
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        # Config file doesn't exist - device is not redirected (safe default)
        echo "not_redirected" >&2
        return 1  # Not redirected
    fi
    
    # Check if device is in blocklist
    # grep -q = Quiet mode (return exit code only, no output)
    # grep -i = Case insensitive matching
    # Pattern: BlockList followed by MAC address (with optional whitespace)
    # We search for the MAC address in blocklist entries
    if sudo grep -qiE "BlockList[[:space:]]+$mac" "$NODOGSPLASH_CONFIG"; then
        # Device is in blocklist - it is redirected
        echo "redirected"
        return 0  # Redirected
    else
        # Device is not in blocklist - it is not redirected
        echo "not_redirected"
        return 1  # Not redirected
    fi
}

################################################################################
# Main Script Execution
# 
# This is where the script actually runs when executed
################################################################################

# Check if exactly one argument was provided
# $# = Number of arguments passed to script
if [ $# -ne 1 ]; then
    # Wrong number of arguments - show usage and exit
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF" >&2
    exit 2  # Exit with validation error
fi

# Store the MAC address argument in a variable
# $1 = First argument passed to script
MAC_ADDRESS="$1"

# Validate the MAC address format
# If validation fails, the function returns non-zero, and script exits due to set -e
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 2  # Exit with validation error
fi

# Normalize MAC address to standard format (colons, uppercase)
# $(...) = Command substitution (captures output of command)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Check if device is in blocklist (redirected)
# This function returns:
# - Exit code 0 if device is redirected (found in blocklist)
# - Exit code 1 if device is not redirected (not found in blocklist)
# - Outputs "redirected" or "not_redirected" to stderr for debugging
# We capture the exit code to determine redirect status
if check_device_in_blocklist "$NORMALIZED_MAC"; then
    # Device is redirected (exit code 0)
    exit 0  # Device is redirected
else
    # Device is not redirected (exit code 1)
    exit 1  # Device is not redirected
fi

# Note: Exit codes are:
# 0 = Device is redirected (success - found in blocklist)
# 1 = Device is not redirected (normal - not found in blocklist)
# This follows Unix convention where 0 = success/true, non-zero = failure/false
# However, in this context:
# - Exit code 0 means "yes, device is redirected" (successful check, positive result)
# - Exit code 1 means "no, device is not redirected" (successful check, negative result)

