#!/usr/bin/env bash
#
# Pi 4B (or any Linux) — verify reporting + unified logs stack after deploy.
#
# Usage (from project root on the Pi):
#   chmod +x scripts/pi_verify_reporting_and_logs.sh
#   ./scripts/pi_verify_reporting_and_logs.sh
#
# Optional environment variables:
#   PI_SKIP_PHPUNIT=1     — only run environment / artisan checks, skip PHPUnit (faster).
#   PI_LIVE_DIGEST_TEST=1 — after PHPUnit, run `php artisan reporting:send-test $PI_PARENT_USER_ID`
#                           (needs real DB user id, MAIL_*, and queue worker if using database queue).
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "=============================================="
echo " Parental WiFi — Pi verification (logs + reporting)"
echo " Project: $ROOT"
echo "=============================================="
echo ""

echo "== PHP CLI =="
php -v
echo ""

if [[ ! -f .env ]]; then
  echo "ERROR: .env not found. Copy .env.example and configure APP_KEY, DB_*, MAIL_*."
  exit 1
fi

echo "== Laravel bootstrap =="
php artisan --version
echo ""

echo "== Reporting Artisan commands registered =="
if ! php artisan list reporting >/dev/null 2>&1; then
  echo "ERROR: 'php artisan list reporting' failed — reporting commands missing."
  exit 1
fi
php artisan list reporting
echo ""

echo "== Scheduled digest tasks (expect reporting:send-digest) =="
if ! php artisan schedule:list 2>/dev/null | grep -q 'reporting:send-digest'; then
  echo "WARN: schedule:list did not show reporting:send-digest — check routes/console.php and php version."
else
  php artisan schedule:list 2>/dev/null | grep 'reporting:send-digest' || true
fi
echo ""

echo "== Database migrations (reporting tables) =="
# After migrate, these should appear as "Ran"
php artisan migrate:status 2>/dev/null | grep -E 'reporting_|report_dispatch' || {
  echo "WARN: Could not grep reporting migrations — run: php artisan migrate --force"
}
echo ""

echo "== PHPUnit — critical tests (in-memory SQLite; no real email) =="
if [[ "${PI_SKIP_PHPUNIT:-0}" == "1" ]]; then
  echo "Skipping PHPUnit (PI_SKIP_PHPUNIT=1)."
else
  php artisan test \
    tests/Feature/ReportingEmailConfigTest.php \
    tests/Feature/PiCriticalLogsReportingTest.php
fi
echo ""

echo "== Optional: live digest queue test =="
if [[ "${PI_LIVE_DIGEST_TEST:-0}" == "1" ]]; then
  if [[ -z "${PI_PARENT_USER_ID:-}" ]]; then
    echo "Set PI_PARENT_USER_ID to a parent user id from the Pi database."
    exit 1
  fi
  echo "Running: php artisan reporting:send-test ${PI_PARENT_USER_ID}"
  echo "(Ensure this parent has at least one enabled reporting_recipients row and queue:work is running if QUEUE_CONNECTION=database.)"
  php artisan reporting:send-test "${PI_PARENT_USER_ID}"
else
  echo "Skipped. To exercise real SMTP + queue on Pi:"
  echo "  export PI_LIVE_DIGEST_TEST=1 PI_PARENT_USER_ID=<parent_id>"
  echo "  ./scripts/pi_verify_reporting_and_logs.sh"
fi

echo ""
echo "=============================================="
echo " Verification script finished OK."
echo "=============================================="
