#!/bin/bash

################################################################################
# Allow Device Through Script
# 
# Purpose: Remove a device from NoDogSplash blocklist/redirect list, allowing
#          the device to access the internet normally after completing quiz/video.
#
# Usage:   ./allow_device_through.sh <MAC_ADDRESS>
# Example: ./allow_device_through.sh AA:BB:CC:DD:EE:FF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Removes device MAC address from NoDogSplash blocklist/redirect list
# 4. Restarts NoDogSplash service to apply changes
# 5. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device redirect removed)
#   1 = Validation error (invalid MAC address format)
#   2 = NoDogSplash config file error (failed to read/write config)
#   3 = Service restart error (failed to restart nodogsplash)
#
# Important Notes:
# - This script requires sudo privileges for config file modifications and service restart
# - The script is idempotent (safe to run multiple times - won't error if device not in list)
# - Device can access internet normally after service restart
# - Config file location: /etc/nodogsplash/nodogsplash.conf (standard location)
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

# NoDogSplash service name (systemd service)
# Used to restart the service after config changes
NODOGSPLASH_SERVICE="nodogsplash"

# Backup directory for config files (in case we need to restore)
BACKUP_DIR="/tmp/nodogsplash_backups"

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
# Function: backup_config_file
# 
# Purpose: Create a backup of the NoDogSplash config file before modifying it
#
# Input:   None (uses global NODOGSPLASH_CONFIG variable)
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Creates backup directory if it doesn't exist
# - Copies config file to backup location with timestamp
# - Provides safety net in case we need to restore original config
################################################################################
backup_config_file() {
    # Check if config file exists
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        # Config file doesn't exist - nothing to backup
        echo "Info: NoDogSplash config file does not exist: $NODOGSPLASH_CONFIG" >&2
        return 0  # Not an error - file doesn't exist
    fi
    
    # Create backup directory if it doesn't exist
    # -p = Create parent directories if needed, don't error if directory exists
    if [ ! -d "$BACKUP_DIR" ]; then
        sudo mkdir -p "$BACKUP_DIR"
    fi
    
    # Create backup filename with timestamp
    # date +%Y%m%d_%H%M%S = Format: YYYYMMDD_HHMMSS
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="$BACKUP_DIR/nodogsplash.conf.backup_$timestamp"
    
    # Copy config file to backup location
    # sudo cp = Copy file with administrator privileges
    if sudo cp "$NODOGSPLASH_CONFIG" "$backup_file"; then
        echo "Info: Config file backed up to: $backup_file" >&2
        return 0  # Success
    else
        echo "Warning: Failed to backup config file (continuing anyway)" >&2
        return 1  # Warning but not fatal
    fi
}

################################################################################
# Function: remove_device_from_blocklist
# 
# Purpose: Remove device MAC address from NoDogSplash blocklist/redirect list
#
# Input:   MAC address (normalized)
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Checks if config file exists (may not exist)
# - Removes all lines containing the device MAC address from blocklist
# - Uses sed to remove matching lines from config file
# - Handles case where device is not in blocklist (idempotent)
#
# NoDogSplash Configuration Format:
# - BlockList: MAC addresses that should be blocked/redirected
# - Format: BlockList MAC_ADDRESS
# - We remove lines matching: BlockList MAC_ADDRESS (case insensitive)
################################################################################
remove_device_from_blocklist() {
    # $1 = MAC address (normalized)
    local mac="$1"
    
    # Check if config file exists
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        # Config file doesn't exist - device is not in blocklist (idempotent)
        echo "Info: NoDogSplash config file does not exist - device not in blocklist" >&2
        return 0  # Success (device not in blocklist)
    fi
    
    # Check if device is in blocklist
    # grep -q = Quiet mode (return exit code only)
    # grep -i = Case insensitive matching
    if ! sudo grep -qi "BlockList.*$mac" "$NODOGSPLASH_CONFIG"; then
        # Device is not in blocklist - this is okay (idempotent)
        echo "Info: Device $mac is not in blocklist (no change needed)" >&2
        return 0  # Success (already not in blocklist)
    fi
    
    # Device is in blocklist - remove it
    # Create temporary file for sed output (safer than in-place editing)
    local temp_file=$(mktemp)
    
    # Remove lines containing the MAC address (case insensitive)
    # sed -i = In-place editing (modify file directly)
    # sed '/pattern/d' = Delete lines matching pattern
    # -i = In-place editing (modify file directly)
    # -i.bak = Create backup with .bak extension (safety measure)
    # We use case-insensitive matching to catch all variations
    if sudo sed -i.bak "/BlockList.*$mac/d" "$NODOGSPLASH_CONFIG"; then
        echo "Info: Removed device $mac from blocklist" >&2
        
        # Remove backup file created by sed (we already have our own backup)
        sudo rm -f "$NODOGSPLASH_CONFIG.bak"
        
        return 0  # Success
    else
        echo "Error: Failed to remove device $mac from blocklist" >&2
        rm -f "$temp_file"  # Clean up temp file
        return 1  # Error
    fi
}

################################################################################
# Function: restart_nodogsplash_service
# 
# Purpose: Restart NoDogSplash service to apply configuration changes
#
# Input:   None (uses global NODOGSPLASH_SERVICE variable)
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Checks if NoDogSplash service is installed and available
# - Restarts the service using systemctl
# - Verifies service restarted successfully
# - Handles case where service is not installed (returns error but continues)
################################################################################
restart_nodogsplash_service() {
    # Check if systemctl command is available
    if ! command -v systemctl > /dev/null 2>&1; then
        echo "Warning: systemctl command not found (systemd not available)" >&2
        return 1  # Can't restart service without systemctl
    fi
    
    # Check if NoDogSplash service exists
    # systemctl list-unit-files = List all service files
    # grep -q = Quiet mode (return exit code only)
    if ! sudo systemctl list-unit-files | grep -q "$NODOGSPLASH_SERVICE.service"; then
        echo "Warning: NoDogSplash service '$NODOGSPLASH_SERVICE' not found" >&2
        echo "Info: Service may not be installed or may have different name" >&2
        return 1  # Service doesn't exist
    fi
    
    # Restart the NoDogSplash service
    # sudo systemctl restart = Restart service with administrator privileges
    # This applies the new configuration we just wrote to the config file
    if sudo systemctl restart "$NODOGSPLASH_SERVICE"; then
        echo "Info: NoDogSplash service restarted successfully" >&2
        
        # Wait a moment for service to fully restart
        sleep 1
        
        # Verify service is running
        # systemctl is-active = Check if service is currently active/running
        if sudo systemctl is-active --quiet "$NODOGSPLASH_SERVICE"; then
            echo "Info: NoDogSplash service is running" >&2
            return 0  # Success
        else
            echo "Warning: NoDogSplash service restarted but may not be running" >&2
            return 1  # Service not running
        fi
    else
        echo "Error: Failed to restart NoDogSplash service" >&2
        return 1  # Error restarting service
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
echo "Removing device redirect from NoDogSplash" >&2
echo "  MAC Address: $NORMALIZED_MAC" >&2

# Step 1: Backup config file (safety measure)
# Disable exit on error temporarily (backup failure is not fatal)
set +e
backup_config_file
set -e  # Re-enable exit on error

# Step 2: Remove device from blocklist in config file
# If this fails, script exits due to set -e
if ! remove_device_from_blocklist "$NORMALIZED_MAC"; then
    echo "Error: Failed to remove device from blocklist" >&2
    exit 2  # Exit with config file error
fi

# Step 3: Restart NoDogSplash service to apply changes
# Disable exit on error temporarily (service restart failure is warning, not fatal)
# We still want to report success if config was updated, even if service restart fails
set +e
restart_nodogsplash_service
SERVICE_RESTART_STATUS=$?
set -e  # Re-enable exit on error

# Check service restart status
if [ $SERVICE_RESTART_STATUS -ne 0 ]; then
    echo "Warning: Service restart failed, but config file was updated" >&2
    echo "Info: You may need to manually restart NoDogSplash service" >&2
    echo "Info: Run: sudo systemctl restart $NODOGSPLASH_SERVICE" >&2
    # Don't exit with error - config was updated successfully
    # Service restart failure is a warning, not a fatal error
fi

# Success! Device redirect is removed
echo "Device redirect removed successfully" >&2
echo "Device $NORMALIZED_MAC can now access internet normally" >&2
exit 0  # Exit with success code

