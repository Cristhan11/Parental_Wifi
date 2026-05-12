#!/bin/bash
################################################################################
# Fix NoDogSplash child traffic hitting ndsNET REJECT while ndsctl says
# Authenticated.
#
# Cause (common on Pi + Tailscale): mangle PREROUTING has a rule such as
#   -m conntrack --ctstate RELATED,ESTABLISHED -j CONNMARK --restore-mark \
#       --nfmask 0xff0000 --ctmask 0xff0000
# on *all* interfaces. That can overwrite NDS packet marks (0x30000) on
# forwarded traffic from wlan0, so filter FORWARD ndsNET misses ndsAUT and
# falls through to REJECT — while the portal still shows Authenticated.
#
# Fix: replace that rule with the same CONNMARK restore but only when the
# packet is NOT entering on the AP interface (! -i wlan0).
#
# Run on the Pi (after deploy):
#   sudo bash /var/www/parental_wifi/scripts/fix_mangle_connmark_skip_wlan0.sh
#
# Re-run after Tailscale upgrades if child Wi-Fi breaks again (tailscaled may
# re-insert the global rule). Optional: systemd drop-in on tailscaled.service
#   ExecStartPost=/bin/bash /var/www/parental_wifi/scripts/fix_mangle_connmark_skip_wlan0.sh
################################################################################
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Error: run as root, e.g. sudo bash $0" >&2
  exit 1
fi

IPT="iptables"
if ! command -v "$IPT" >/dev/null 2>&1; then
  echo "Error: $IPT not found" >&2
  exit 1
fi

# Exact match for the broad restore (no incoming-interface match).
DEL_ARGS=(
  -t mangle -D PREROUTING
  -m conntrack --ctstate RELATED,ESTABLISHED
  -j CONNMARK --restore-mark --nfmask 0xff0000 --ctmask 0xff0000
)

# Append so we do not assume CONNMARK was always line 4 (other distros may differ).
ADD_ARGS=(
  -t mangle -A PREROUTING
  -m conntrack --ctstate RELATED,ESTABLISHED
  ! -i wlan0
  -j CONNMARK --restore-mark --nfmask 0xff0000 --ctmask 0xff0000
)

FIXED_CHECK=(
  -t mangle -C PREROUTING
  -m conntrack --ctstate RELATED,ESTABLISHED
  ! -i wlan0
  -j CONNMARK --restore-mark --nfmask 0xff0000 --ctmask 0xff0000
)
# -C checks an existing rule anywhere in the chain.

removed=0
while "$IPT" "${DEL_ARGS[@]}" 2>/dev/null; do
  removed=$((removed + 1))
  echo "Removed global CONNMARK restore from mangle PREROUTING (#${removed})."
done

if "$IPT" "${FIXED_CHECK[@]}" 2>/dev/null; then
  if [[ "$removed" -gt 0 ]]; then
    echo "Scoped CONNMARK (! -i wlan0) already present; removed duplicate global rule(s)."
  else
    echo "Already OK: scoped CONNMARK present and no global restore matched."
  fi
  exit 0
fi

if [[ "$removed" -eq 0 ]]; then
  echo "No global CONNMARK restore matched (mask 0xff0000 / 0xff0000) and scoped rule not present."
  echo "Inspect manually: sudo iptables -t mangle -L PREROUTING -n -v --line-numbers"
  exit 0
fi

if ! "$IPT" "${ADD_ARGS[@]}"; then
  echo "Error: failed to append scoped CONNMARK rule." >&2
  exit 1
fi

echo "Appended CONNMARK restore with '! -i wlan0' to mangle PREROUTING (runs after nds* rules)."
echo "Test child Wi-Fi (no iptables whitelist). If good, save rules (e.g. netfilter-persistent) or hook after tailscaled."
