#!/bin/bash
################################################################################
# Dump Pi gateway state for Parental WiFi / OpenNDS / dnsmasq troubleshooting.
# Run on the Raspberry Pi:
#   sudo bash scripts/dump_gateway_state.sh
#   sudo bash scripts/dump_gateway_state.sh e6:6a:8f:19:be:b1   # optional MAC: extra greps
#
# Writes one text file under /tmp (path printed at end).
################################################################################
set -euo pipefail

MAC_ARG="${1:-}"
# normalize optional MAC for grep (colon form, case-insensitive)
MAC_GREP=""
if [ -n "$MAC_ARG" ]; then
    MAC_GREP=$(echo "$MAC_ARG" | tr '[:upper:]' '[:lower:]' | tr '-' ':')
fi

OUT=$(mktemp /tmp/parental-wifi-gateway-dump-XXXXXX.txt)
exec > >(tee -a "$OUT") 2>&1

echo "========== $(date -Is) parental_wifi gateway dump =========="
if [ -n "$MAC_GREP" ]; then
    echo "Optional MAC filter: $MAC_GREP"
fi
echo

echo "========== hostname / uname =========="
hostnamectl 2>/dev/null || true
uname -a

echo
echo "========== services (nds / splash / ap / dns) =========="
systemctl list-units --type=service --all 2>/dev/null | grep -iE 'opennds|nodogsplash|nds|hostapd|dnsmasq|dhcpcd|NetworkManager|systemd-networkd' || true

for u in opennds nodogsplash hostapd dnsmasq; do
    if systemctl list-unit-files --type=service 2>/dev/null | grep -q "^${u}.service"; then
        echo
        echo "----- systemctl status $u -----"
        systemctl status "$u" --no-pager -l 2>/dev/null || true
    fi
done

echo
echo "========== ndsctl =========="
command -v ndsctl >/dev/null 2>&1 && sudo ndsctl status 2>/dev/null || echo "ndsctl not found"
command -v ndsctl >/dev/null 2>&1 && sudo ndsctl clients 2>/dev/null | head -120 || true

echo
echo "========== ip_forward =========="
cat /proc/sys/net/ipv4/ip_forward 2>/dev/null || true

echo
echo "========== ip route / rule =========="
ip route show 2>/dev/null || true
ip rule show 2>/dev/null || true

echo
echo "========== interfaces (brief) =========="
ip -br addr 2>/dev/null || true

echo
echo "========== mangle PREROUTING (connmark / wlan0) =========="
sudo iptables -t mangle -L PREROUTING -n -v --line-numbers 2>/dev/null | head -25 || true

echo
echo "========== filter ndsNET =========="
sudo iptables -L ndsNET -n -v --line-numbers 2>/dev/null || true

echo
echo "========== filter FORWARD (first 25 rules, packet counts) =========="
sudo iptables -L FORWARD -n -v --line-numbers 2>/dev/null | head -40 || true

echo
echo "========== filter FORWARD (raw -S, first 30) =========="
sudo iptables -S FORWARD 2>/dev/null | head -30 || true

echo
echo "========== filter INPUT (raw -S, first 20) =========="
sudo iptables -S INPUT 2>/dev/null | head -20 || true

echo
echo "========== ip neigh (wlan0) =========="
ip neigh show dev wlan0 2>/dev/null || true

if [ -n "$MAC_GREP" ]; then
    echo
    echo "========== iptables-save lines matching MAC ($MAC_GREP) =========="
    sudo iptables-save 2>/dev/null | grep -i "$MAC_GREP" || echo "(no matches)"
fi

echo
echo "========== iptables-save (full) =========="
sudo iptables-save 2>/dev/null || true

echo
echo "========== dnsmasq main + snippets =========="
for f in /etc/dnsmasq.conf /etc/dnsmasq.d/parental-dhcp-dns-bypass.conf /etc/dnsmasq.d/parental-global-blocklist.conf; do
    if [ -f "$f" ]; then
        echo "----- $f -----"
        sed -n '1,200p' "$f"
        echo
    fi
done
ls -la /etc/dnsmasq.d/ 2>/dev/null || true

echo
echo "========== hostapd conf (first 80 lines) =========="
for f in /etc/hostapd/hostapd.conf /etc/hostapd.conf; do
    if [ -f "$f" ]; then
        echo "----- $f -----"
        sed -n '1,80p' "$f"
        break
    fi
done

echo
echo "========== opennds / nodogsplash conf (paths if exist) =========="
for f in /etc/opennds/opennds.conf /etc/config/opennds /etc/nodogsplash/nodogsplash.conf; do
    if [ -f "$f" ]; then
        echo "----- $f (first 120 lines) -----"
        sed -n '1,120p' "$f"
        echo
    fi
done

echo
echo "========== DHCP leases (dnsmasq) =========="
for lf in /var/lib/misc/dnsmasq.leases /var/lib/dhcp/dnsmasq.leases; do
    if [ -f "$lf" ]; then
        echo "----- $lf (last 30) -----"
        tail -30 "$lf"
        break
    fi
done

echo
echo "========== dump complete =========="
echo "Saved to: $OUT"
