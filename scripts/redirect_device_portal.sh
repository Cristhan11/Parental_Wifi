#!/bin/bash

################################################################################
# Redirect Device Portal Script
# 
# Purpose: Configure NoDogSplash to redirect a device to the portal page.
#          This intercepts all HTTP requests from the device and redirects
#          them to the portal so the child can complete quizzes/videos.
#
# Usage:   ./redirect_device_portal.sh <MAC_ADDRESS> <PORTAL_URL>
# Example: ./redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format (with colons)
# 3. Validates the portal URL format
# 4. Adds device MAC address to NoDogSplash blocklist/redirect list
# 5. Restarts NoDogSplash service to apply changes
# 6. Returns exit code 0 on success, non-zero on error
#
# Exit Codes:
#   0 = Success (device redirect configured)
#   1 = Validation error (invalid MAC address or URL format)
#   2 = NoDogSplash config file error (failed to read/write config)
#   3 = Service restart error (failed to restart nodogsplash)
#
# Important Notes:
# - This script requires sudo privileges for config file modifications and service restart
# - The script is idempotent (safe to run multiple times - won't create duplicates)
# - NoDogSplash will redirect device on next HTTP request after service restart
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
# Function: validate_portal_url
# 
# Purpose: Check if the portal URL is in a valid format
#
# Input:   Portal URL string (e.g., "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF")
# Output:  Returns 0 if valid, 1 if invalid
#
# What This Function Does:
# - Checks if URL starts with http:// or https://
# - Validates basic URL structure
# - Ensures URL is not empty
################################################################################
validate_portal_url() {
    # $1 = First argument (the portal URL)
    local url="$1"
    
    # Check if URL is empty (no argument provided)
    if [ -z "$url" ]; then
        echo "Error: Portal URL is required" >&2
        return 1  # Return error code
    fi
    
    # Validate URL format - must start with http:// or https://
    # grep -q = Quiet mode (return exit code only)
    # grep -E = Extended regex mode
    if echo "$url" | grep -qE '^https?://'; then
        return 0  # Valid format
    else
        # Invalid format - show error message
        echo "Error: Invalid portal URL format: $url" >&2
        echo "Expected format: http://HOST/PATH or https://HOST/PATH" >&2
        echo "Example: http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF" >&2
        return 1  # Return error code
    fi
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
    # Check if config file exists (might not exist on first run)
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        # Config file doesn't exist yet - that's okay, we'll create it
        echo "Info: NoDogSplash config file does not exist yet, will be created: $NODOGSPLASH_CONFIG" >&2
        return 0  # Not an error - file will be created
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
# Function: add_device_to_blocklist
# 
# Purpose: Add device MAC address to NoDogSplash blocklist/redirect list
#
# Input:   MAC address (normalized) and Portal URL
# Output:  Returns 0 on success, 1 on error
#
# What This Function Does:
# - Checks if device is already in blocklist (idempotent)
# - Adds device MAC address to NoDogSplash blocklist/redirect configuration
# - Uses standard NoDogSplash config format
# - Handles case where config file doesn't exist yet
#
# NoDogSplash Configuration Format:
# - BlockList: MAC addresses that should be blocked/redirected
# - Format: BlockList MAC_ADDRESS or RedirectList MAC_ADDRESS URL
# - We use BlockList approach for simplicity (NoDogSplash will redirect blocked devices)
################################################################################
add_device_to_blocklist() {
    # $1 = MAC address (normalized)
    # $2 = Portal URL
    local mac="$1"
    local portal_url="$2"
    
    # Check if config file exists
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        # Config file doesn't exist - create it with initial content
        echo "Info: Creating new NoDogSplash config file: $NODOGSPLASH_CONFIG" >&2
        
        # Create config file with header comment and blocklist entry
        # sudo tee = Write to file with administrator privileges
        # Using tee allows us to write to system-protected files
        sudo tee "$NODOGSPLASH_CONFIG" > /dev/null <<EOF
# NoDogSplash Configuration
# Auto-generated by Laravel Parental WiFi System
# This file is managed automatically - manual edits may be overwritten

# BlockList: Devices that should be redirected to portal
# Format: BlockList MAC_ADDRESS
BlockList $mac

EOF
        echo "Info: Created config file and added device $mac to blocklist" >&2
        return 0  # Success
    fi
    
    # Config file exists - check if device is already in blocklist (idempotent)
    # grep -q = Quiet mode (return exit code only)
    # grep -i = Case insensitive matching
    if sudo grep -qi "BlockList.*$mac" "$NODOGSPLASH_CONFIG"; then
        # Device is already in blocklist - this is okay (idempotent)
        echo "Info: Device $mac is already in blocklist (no change needed)" >&2
        return 0  # Success (already configured)
    fi
    
    # Device is not in blocklist - add it
    # Append blocklist entry to config file
    # sudo tee -a = Append to file with administrator privileges
    # -a = Append mode (don't overwrite existing content)
    if echo "BlockList $mac" | sudo tee -a "$NODOGSPLASH_CONFIG" > /dev/null; then
        echo "Info: Added device $mac to blocklist" >&2
        return 0  # Success
    else
        echo "Error: Failed to add device $mac to blocklist" >&2
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

# Check if exactly two arguments were provided
# $# = Number of arguments passed to script
if [ $# -ne 2 ]; then
    # Wrong number of arguments - show usage and exit
    echo "Usage: $0 <MAC_ADDRESS> <PORTAL_URL>" >&2
    echo "Example: $0 AA:BB:CC:DD:EE:FF \"http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF\"" >&2
    exit 1  # Exit with error code
fi

# Store the arguments in variables
# $1 = First argument (MAC address)
# $2 = Second argument (Portal URL)
MAC_ADDRESS="$1"
PORTAL_URL="$2"

# Validate the MAC address format
# If validation fails, the function returns non-zero, and script exits due to set -e
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1  # Exit with validation error
fi

# Validate the portal URL format
if ! validate_portal_url "$PORTAL_URL"; then
    exit 1  # Exit with validation error
fi

# Normalize MAC address to standard format (colons, uppercase)
# $(...) = Command substitution (captures output of command)
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# Display what we're doing
echo "Configuring NoDogSplash to redirect device to portal" >&2
echo "  MAC Address: $NORMALIZED_MAC" >&2
echo "  Portal URL: $PORTAL_URL" >&2

# Step 1: Backup config file (safety measure)
# Disable exit on error temporarily (backup failure is not fatal)
set +e
backup_config_file
set -e  # Re-enable exit on error

# Step 2: Add device to blocklist in config file
# If this fails, script exits due to set -e
if ! add_device_to_blocklist "$NORMALIZED_MAC" "$PORTAL_URL"; then
    echo "Error: Failed to add device to blocklist" >&2
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

# Success! Device redirect is configured
echo "Device redirect configured successfully" >&2
echo "Device $NORMALIZED_MAC will be redirected to portal on next HTTP request" >&2
exit 0  # Exit with success code

