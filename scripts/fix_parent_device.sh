#!/bin/bash

################################################################################
# Fix Parent Device Script
# 
# Purpose: Authenticate a parent device in NoDogSplash to allow internet access
#          This is a quick fix for parent devices that are being redirected.
#
# Usage:   ./fix_parent_device.sh <MAC_ADDRESS>
# Example: ./fix_parent_device.sh 30:03:C8:0A:45:AF
#
# What This Script Does:
# 1. Validates the MAC address format
# 2. Normalizes MAC address to standard format
# 3. Authenticates the device using allow_device_through.sh
#
################################################################################

# Check if exactly one argument was provided
if [ $# -ne 1 ]; then
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    echo "Example: $0 30:03:C8:0A:45:AF" >&2
    exit 1
fi

MAC_ADDRESS="$1"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Use the existing allow_device_through.sh script
"$SCRIPT_DIR/allow_device_through.sh" "$MAC_ADDRESS"

exit $?

