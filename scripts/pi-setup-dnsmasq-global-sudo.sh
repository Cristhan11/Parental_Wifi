#!/bin/bash
################################################################################
# One-time Raspberry Pi setup: allow the web user to run the household dnsmasq
# blocklist script without a password (NOPASSWD sudoers line).
#
# Run ON THE PI as a user with sudo (e.g. pi), after the app is deployed:
#   cd /var/www/parental_wifi   # your install path
#   sudo bash scripts/pi-setup-dnsmasq-global-sudo.sh
#
# Optional arguments:
#   sudo bash scripts/pi-setup-dnsmasq-global-sudo.sh [APP_ROOT] [WEB_USER]
#
# Optional environment:
#   WEB_USER=www-data   (default if second arg omitted)
################################################################################
set -euo pipefail

SUDOERS_FILE="/etc/sudoers.d/parental-wifi-scripts"

SCRIPT_PATH="$(readlink -f "${BASH_SOURCE[0]}")"
SCRIPT_DIR="$(dirname "$SCRIPT_PATH")"
DEFAULT_APP_ROOT="$(readlink -f "$SCRIPT_DIR/..")"

APP_ROOT="${1:-$DEFAULT_APP_ROOT}"
WEB_USER="${2:-${WEB_USER:-www-data}}"

GLOBAL_SCRIPT="$(readlink -f "$APP_ROOT/scripts/update_dnsmasq_global_blocklist.sh")"
DHCP_BYPASS_SCRIPT="$(readlink -f "$APP_ROOT/scripts/update_dnsmasq_dhcp_dns_bypass.sh")"
REMOVE_WHITELIST_SCRIPT="$(readlink -f "$APP_ROOT/scripts/remove_whitelist_accept_rules.sh")"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Error: run as root, e.g. sudo bash $SCRIPT_PATH" >&2
  exit 1
fi

if [[ ! -f "$GLOBAL_SCRIPT" ]]; then
  echo "Error: global blocklist script not found: $GLOBAL_SCRIPT" >&2
  exit 1
fi

if [[ ! -f "$DHCP_BYPASS_SCRIPT" ]]; then
  echo "Error: DHCP DNS bypass script not found: $DHCP_BYPASS_SCRIPT" >&2
  exit 1
fi

if [[ ! -f "$REMOVE_WHITELIST_SCRIPT" ]]; then
  echo "Error: remove_whitelist_accept_rules.sh not found: $REMOVE_WHITELIST_SCRIPT" >&2
  exit 1
fi

chmod 0755 "$APP_ROOT/scripts" 2>/dev/null || true
chmod +x "$GLOBAL_SCRIPT"
chmod +x "$DHCP_BYPASS_SCRIPT"
chmod +x "$REMOVE_WHITELIST_SCRIPT"

append_nopasswd_line() {
  local script_path="$1"
  local comment="$2"
  local line="${WEB_USER} ALL=(ALL) NOPASSWD: ${script_path}"

  if [[ -f "$SUDOERS_FILE" ]] && grep -Fxq "$line" "$SUDOERS_FILE" 2>/dev/null; then
    echo "Already configured: NOPASSWD line present for ${script_path}"
    return 0
  fi
  if [[ -f "$SUDOERS_FILE" ]] && grep -Fq "NOPASSWD: ${script_path}" "$SUDOERS_FILE" 2>/dev/null; then
    echo "Warning: $SUDOERS_FILE already references ${script_path} with a different user or line format." >&2
    echo "Edit manually or remove the old line, then re-run this script." >&2
    echo "Expected line: $line" >&2
    return 1
  fi

  local BACKUP="${SUDOERS_FILE}.backup.$(date +%Y%m%d%H%M%S)"
  if [[ -f "$SUDOERS_FILE" ]]; then
    cp -a "$SUDOERS_FILE" "$BACKUP"
    echo "Backed up to $BACKUP"
  fi

  if [[ ! -f "$SUDOERS_FILE" ]]; then
    umask 077
    {
      echo "# Parental WiFi — script permissions (managed / extended by pi-setup-dnsmasq-global-sudo.sh)"
      echo "$line"
    } >"$SUDOERS_FILE"
  else
    printf '\n# %s\n%s\n' "$comment" "$line" >>"$SUDOERS_FILE"
  fi

  chmod 0440 "$SUDOERS_FILE"
  chown root:root "$SUDOERS_FILE"
  echo "Appended NOPASSWD rule for ${script_path}"
  return 0
}

if ! append_nopasswd_line "$GLOBAL_SCRIPT" "Household dnsmasq blocklist (Laravel DomainBlockingService)"; then
  exit 1
fi

if ! append_nopasswd_line "$DHCP_BYPASS_SCRIPT" "DHCP DNS bypass for parent/guest/whitelisted MACs (Laravel)"; then
  exit 1
fi

if ! append_nopasswd_line "$REMOVE_WHITELIST_SCRIPT" "Remove stale whitelist iptables ACCEPT (Laravel NetworkService)"; then
  exit 1
fi

if ! visudo -c -f "$SUDOERS_FILE" >/dev/null; then
  echo "Error: sudoers syntax check failed for $SUDOERS_FILE" >&2
  exit 1
fi
echo "sudoers OK: $SUDOERS_FILE"

echo ""
echo "Optional test (should NOT ask for ${WEB_USER}'s password):"
echo "  echo 'verify-setup.invalid:1' | sudo -u ${WEB_USER} sudo ${GLOBAL_SCRIPT}"
echo "  printf '%s\n' 'aa:bb:cc:dd:ee:ff' | sudo -u ${WEB_USER} sudo ${DHCP_BYPASS_SCRIPT} 8.8.8.8 1.1.1.1"
echo ""
echo "Then remove the test domain from the app or run: php artisan dnsmasq:sync-blocklist <parent_user_id>"
