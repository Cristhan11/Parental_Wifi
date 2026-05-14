#!/bin/bash
################################################################################
# Remove iptables ACCEPT rules added by whitelist_device.sh for a MAC.
#
# When a device is whitelisted, whitelist_device.sh inserts:
#   -I INPUT 1  -i wlan0 -m mac --mac-source MAC -j ACCEPT
#   -I FORWARD 1 -i wlan0 -m mac --mac-source MAC -j ACCEPT
#
# Changing the device back to Active/Child in the dashboard does NOT remove
# those rules, so traffic bypasses OpenNDS for that MAC forever. This script
# removes only those ACCEPT rules (idempotent).
#
# Usage: ./remove_whitelist_accept_rules.sh <MAC_ADDRESS>
################################################################################
set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <MAC_ADDRESS>" >&2
    exit 1
fi

validate_mac_address() {
    local mac="$1"
    [ -n "$mac" ] || return 1
    echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'
}

normalize_mac_address() {
    local mac="$1"
    mac=$(echo "$mac" | sed 's/-/:/g')
    echo "$mac" | tr '[:lower:]' '[:upper:]'
}

strip_accept() {
    local chain="$1"
    local mac="$2"
    local n=0
    while sudo iptables -D "$chain" -i wlan0 -m mac --mac-source "$mac" -j ACCEPT >/dev/null 2>&1; do
        n=$((n + 1))
    done
    if [ "$n" -gt 0 ]; then
        echo "Removed $n ACCEPT rule(s) from $chain for $mac" >&2
    else
        echo "No ACCEPT rule in $chain for $mac" >&2
    fi
}

MAC="$1"
if ! validate_mac_address "$MAC"; then
    echo "Error: invalid MAC: $MAC" >&2
    exit 1
fi
NORM=$(normalize_mac_address "$MAC")

echo "Removing whitelist-style ACCEPT rules for: $NORM" >&2
strip_accept "INPUT" "$NORM"
strip_accept "FORWARD" "$NORM"
echo "Done." >&2
exit 0
