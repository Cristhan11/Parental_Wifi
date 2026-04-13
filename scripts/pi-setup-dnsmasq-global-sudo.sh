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

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Error: run as root, e.g. sudo bash $SCRIPT_PATH" >&2
  exit 1
fi

if [[ ! -f "$GLOBAL_SCRIPT" ]]; then
  echo "Error: global blocklist script not found: $GLOBAL_SCRIPT" >&2
  exit 1
fi

chmod 0755 "$APP_ROOT/scripts" 2>/dev/null || true
chmod +x "$GLOBAL_SCRIPT"

SUDOERS_LINE="${WEB_USER} ALL=(ALL) NOPASSWD: ${GLOBAL_SCRIPT}"

if [[ -f "$SUDOERS_FILE" ]] && grep -Fxq "$SUDOERS_LINE" "$SUDOERS_FILE" 2>/dev/null; then
  echo "Already configured: NOPASSWD line present in $SUDOERS_FILE"
elif [[ -f "$SUDOERS_FILE" ]] && grep -Fq "NOPASSWD: ${GLOBAL_SCRIPT}" "$SUDOERS_FILE" 2>/dev/null; then
  echo "Warning: $SUDOERS_FILE already references this script with a different user or line format." >&2
  echo "Edit manually or remove the old line, then re-run this script." >&2
  echo "Expected line: $SUDOERS_LINE" >&2
  exit 1
else
  BACKUP="${SUDOERS_FILE}.backup.$(date +%Y%m%d%H%M%S)"
  if [[ -f "$SUDOERS_FILE" ]]; then
    cp -a "$SUDOERS_FILE" "$BACKUP"
    echo "Backed up to $BACKUP"
  fi

  if [[ ! -f "$SUDOERS_FILE" ]]; then
    umask 077
    {
      echo "# Parental WiFi — script permissions (managed / extended by pi-setup-dnsmasq-global-sudo.sh)"
      echo "$SUDOERS_LINE"
    } >"$SUDOERS_FILE"
  else
    printf '\n# Household dnsmasq blocklist (Laravel DomainBlockingService)\n%s\n' "$SUDOERS_LINE" >>"$SUDOERS_FILE"
  fi

  chmod 0440 "$SUDOERS_FILE"
  chown root:root "$SUDOERS_FILE"
  echo "Appended NOPASSWD rule to $SUDOERS_FILE"
fi

if ! visudo -c -f "$SUDOERS_FILE" >/dev/null; then
  echo "Error: sudoers syntax check failed for $SUDOERS_FILE" >&2
  exit 1
fi
echo "sudoers OK: $SUDOERS_FILE"

echo ""
echo "Optional test (should NOT ask for www-data's password):"
echo "  echo 'verify-setup.invalid:1' | sudo -u ${WEB_USER} sudo ${GLOBAL_SCRIPT}"
echo ""
echo "Then remove the test domain from the app or run: php artisan dnsmasq:sync-blocklist <parent_user_id>"
