#!/bin/bash

################################################################################
# Block Device Script
# 
# Purpose: Block a device's MAC address using iptables firewall rules.
#          This prevents the device from accessing the internet at the network level.
#
# Usage:   ./block_device.sh <MAC_ADDRESS>
# Example: ./block_device.sh AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Adds iptables rules to block the device on INPUT and FORWARD chains
# 4. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device blocked)
#   1 = Validation error (invalid MAC address format)
#   2 = iptables error (failed to add rules)
#
# Important Notes:
# - This script requires sudo privileges for iptables commands
# - The script is idempotent (safe to run multiple times - won't create duplicates)
# - Blocks on both INPUT (traffic to Pi) and FORWARD (traffic through Pi to internet)
################################################################################

# Set script to exit immediately if any command fails
# This prevents the script from continuing after an error
set -e

# Set script to exit if any variable is used before being set
# This prevents bugs from uninitialized variables
set -u

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
# - Converts hyphens to colons (standard iptables format)
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
# Function: check_rule_exists
# 
# Purpose: Check if an iptables rule already exists
#
# Input:   Chain name (INPUT or FORWARD) and MAC address
# Output:  Returns 0 if rule exists, 1 if not
#
# What This Function Does:
# - Lists iptables rules and searches for the MAC address
# - Prevents duplicate rules from being added
# - Uses grep to search for the MAC address in rule list
################################################################################
check_rule_exists() {
    # $1 = Chain name (INPUT or FORWARD)
    # $2 = MAC address
    local chain="$1"
    local mac="$2"
    
    # List iptables rules for the specified chain
    # -L = List rules
    # -n = Numeric output (don't resolve IPs to hostnames)
    # -v = Verbose (show more details)
    # Then search for the MAC address in the output
    # grep -q = Quiet mode (return exit code only, no output)
    if sudo iptables -L "$chain" -n -v | grep -q "$mac"; then
        return 0  # Rule exists
    else
        return 1  # Rule doesn't exist
    fi
}

################################################################################
# Function: add_block_rule
# 
# Purpose: Add an iptables rule to block a device
#
# Input:   Chain name (INPUT or FORWARD) and MAC address
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Adds a DROP rule to the specified iptables chain
# - Rule matches packets from the specified MAC address
# - Drops (blocks) all matching packets
#
# iptables Command Breakdown:
#   sudo iptables = Run iptables with administrator privileges
#   -A = Append (add rule to end of chain)
#   INPUT/FORWARD = Chain name (where to add rule)
#   -i wlan0 = Input interface (WiFi interface)
#   -m mac = Match by MAC address
#   --mac-source = Source MAC address to match
#   -j DROP = Jump to DROP target (discard packet)
################################################################################
add_block_rule() {
    # $1 = Chain name (INPUT or FORWARD)
    # $2 = MAC address
    local chain="$1"
    local mac="$2"
    
    # Check if rule already exists (idempotent - safe to run multiple times)
    if check_rule_exists "$chain" "$mac"; then
        # Rule already exists - this is okay, just inform user
        echo "Info: Blocking rule already exists in $chain chain for MAC $mac" >&2
        return 0  # Success (rule already there)
    fi
    
    # Add the blocking rule
    # sudo = Run as administrator (required for iptables)
    # iptables = Linux firewall tool
    # -A = Append rule to end of chain
    # $chain = Chain name (INPUT or FORWARD)
    # -i wlan0 = Match packets coming from wlan0 interface (WiFi)
    # -m mac = Use MAC address matching module
    # --mac-source = Match packets from this MAC address
    # $mac = The MAC address to block
    # -j DROP = Action: Drop (discard) the packet
    if sudo iptables -A "$chain" -i wlan0 -m mac --mac-source "$mac" -j DROP; then
        echo "Success: Added blocking rule to $chain chain for MAC $mac" >&2
        return 0  # Success
    else
        # Failed to add rule - show error
        echo "Error: Failed to add blocking rule to $chain chain for MAC $mac" >&2
        return 1  # Error
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
    echo "Example: $0 AA-BB-CC-DD-EE-FF" >&2
    exit 1  # Exit with error code
fi

# Store the MAC address argument in a variable
# $1 = First argument passed to script
MAC_ADDRESS="$1"

# Validate the MAC address format
# If validation fails, the function returns non-zero, and script exits due to set -e
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1  # Exit with validation error
fi

# Normalize MAC address to standard format (colons, uppercase)
# $(...) = Command substitution (captures output of command)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Display what we're doing
echo "Blocking device with MAC address: $NORMALIZED_MAC" >&2

# Add blocking rule to INPUT chain
# INPUT chain = Blocks traffic coming TO the Raspberry Pi
# This prevents device from accessing the Pi itself
if ! add_block_rule "INPUT" "$NORMALIZED_MAC"; then
    echo "Error: Failed to block device on INPUT chain" >&2
    exit 2  # Exit with iptables error
fi

# Add blocking rule to FORWARD chain
# FORWARD chain = Blocks traffic being FORWARDED through the Pi
# This is CRITICAL for access point - blocks device from accessing internet
if ! add_block_rule "FORWARD" "$NORMALIZED_MAC"; then
    echo "Error: Failed to block device on FORWARD chain" >&2
    exit 2  # Exit with iptables error
fi

# Success! Device is now blocked
echo "Device blocked successfully on both INPUT and FORWARD chains" >&2
exit 0  # Exit with success code

