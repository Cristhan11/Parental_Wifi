#!/bin/bash

################################################################################
# Domain Blocking Test Verification Script
# 
# Purpose: Quick verification script to test domain blocking functionality
#          Run this on the Raspberry Pi to verify domain blocking is working
#
# Usage:   ./scripts/test_domain_blocking.sh <MAC_ADDRESS> <DOMAIN>
# Example: ./scripts/test_domain_blocking.sh e6:6a:8f:19:be:b1 example.com
################################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check arguments
if [ $# -lt 2 ]; then
    echo "Usage: $0 <MAC_ADDRESS> <DOMAIN>"
    echo "Example: $0 e6:6a:8f:19:be:b1 example.com"
    exit 1
fi

MAC="$1"
DOMAIN="$2"

# Normalize MAC address (uppercase, colons)
NORMALIZED_MAC=$(echo "$MAC" | tr '[:lower:]' '[:upper:]' | tr '-' ':')

echo "=========================================="
echo "Domain Blocking Test Verification"
echo "=========================================="
echo "Device MAC: $NORMALIZED_MAC"
echo "Test Domain: $DOMAIN"
echo ""

# 1. Check dnsmasq service
echo "1. Checking dnsmasq service..."
if systemctl is-active --quiet dnsmasq; then
    echo -e "${GREEN}   ✅ dnsmasq is running${NC}"
else
    echo -e "${RED}   ❌ dnsmasq is not running${NC}"
    echo "   Run: sudo systemctl start dnsmasq"
    exit 1
fi

# 2. Check config file exists
CONFIG_FILE="/etc/dnsmasq.d/blocked-domains-${NORMALIZED_MAC}.conf"
echo ""
echo "2. Checking config file..."
if [ -f "$CONFIG_FILE" ]; then
    echo -e "${GREEN}   ✅ Config file exists: $CONFIG_FILE${NC}"
    echo "   File contents:"
    sudo cat "$CONFIG_FILE" | sed 's/^/      /'
else
    echo -e "${YELLOW}   ⚠️  Config file does not exist: $CONFIG_FILE${NC}"
    echo "   This is normal if domain hasn't been blocked yet"
fi

# 3. Check if domain is in config
echo ""
echo "3. Checking if domain is blocked in config..."
if [ -f "$CONFIG_FILE" ]; then
    if grep -q "address=/${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null || \
       grep -q "address=/.${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null; then
        echo -e "${GREEN}   ✅ Domain $DOMAIN is in blocklist${NC}"
        if grep -q "address=/.${DOMAIN}/127.0.0.1" "$CONFIG_FILE" 2>/dev/null; then
            echo "   Blocking type: Subdomains included (wildcard)"
        else
            echo "   Blocking type: Main domain only"
        fi
    else
        echo -e "${YELLOW}   ⚠️  Domain $DOMAIN is NOT in blocklist${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠️  Cannot check - config file doesn't exist${NC}"
fi

# 4. Test DNS resolution
echo ""
echo "4. Testing DNS resolution..."
DNS_RESULT=$(dig @127.0.0.1 "$DOMAIN" +short 2>/dev/null | head -1)
if [ -z "$DNS_RESULT" ]; then
    echo -e "${YELLOW}   ⚠️  DNS query returned no result${NC}"
elif [ "$DNS_RESULT" = "127.0.0.1" ]; then
    echo -e "${GREEN}   ✅ DNS resolves to 127.0.0.1 (BLOCKED)${NC}"
else
    echo -e "${RED}   ❌ DNS resolves to $DNS_RESULT (NOT BLOCKED)${NC}"
    echo "   Expected: 127.0.0.1"
fi

# 5. Check dnsmasq logs
echo ""
echo "5. Checking recent dnsmasq logs..."
RECENT_LOGS=$(sudo journalctl -u dnsmasq -n 10 --no-pager 2>/dev/null | tail -5)
if [ -n "$RECENT_LOGS" ]; then
    echo "   Recent log entries:"
    echo "$RECENT_LOGS" | sed 's/^/      /'
else
    echo "   No recent log entries"
fi

# 6. Check for errors in logs
echo ""
echo "6. Checking for errors in dnsmasq logs..."
ERRORS=$(sudo journalctl -u dnsmasq -n 50 --no-pager 2>/dev/null | grep -i "error\|fail" | tail -3)
if [ -n "$ERRORS" ]; then
    echo -e "${RED}   ⚠️  Found potential errors:${NC}"
    echo "$ERRORS" | sed 's/^/      /'
else
    echo -e "${GREEN}   ✅ No errors found in recent logs${NC}"
fi

echo ""
echo "=========================================="
echo "Test Complete"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. If domain is not blocked, create blocked website via web interface"
echo "2. Verify device is using Pi as DNS server"
echo "3. Test from device: nslookup $DOMAIN (should return 127.0.0.1)"
echo "4. Try accessing http://$DOMAIN from device (should fail)"

