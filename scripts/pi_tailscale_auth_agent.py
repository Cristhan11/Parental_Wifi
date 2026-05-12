#!/usr/bin/env python3
"""
Local helper service for Parental WiFi to fetch Tailscale auth URLs safely.

Security model:
- Binds to 127.0.0.1 only
- Requires X-Pi-Agent-Token header
- Executes only allowlisted tailscale path
- Uses process timeout and sanitized JSON responses
"""

from __future__ import annotations

import json
import os
import re
import subprocess
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, HTTPServer
from socketserver import ThreadingMixIn
from typing import Any


HOST = os.getenv("PI_AGENT_HOST", "127.0.0.1")
PORT = int(os.getenv("PI_AGENT_PORT", "9098"))
TOKEN = os.getenv("PI_AGENT_TOKEN", "")
TAILSCALE_BIN = os.getenv("PI_AGENT_TAILSCALE_BIN", "/usr/bin/tailscale")
CMD_TIMEOUT = int(os.getenv("PI_AGENT_COMMAND_TIMEOUT_SECONDS", "8"))

STATUS_ALLOWED = {"already_authenticated", "action_required", "unavailable", "error"}
AUTH_URL_PATTERN = re.compile(r"https://login\.tailscale\.com/[^\s\"'<>]+")


def _json_response(handler: BaseHTTPRequestHandler, code: int, payload: dict[str, Any]) -> None:
    body = json.dumps(payload).encode("utf-8")
    handler.send_response(code)
    handler.send_header("Content-Type", "application/json")
    handler.send_header("Content-Length", str(len(body)))
    handler.end_headers()
    try:
        handler.wfile.write(body)
    except BrokenPipeError:
        # Client closed the socket early (e.g. browser navigated away); not a server fault.
        return


def _run_tailscale(args: list[str]) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [TAILSCALE_BIN] + args,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        timeout=CMD_TIMEOUT,
        check=False,
    )


def _extract_auth_url(output: str) -> str | None:
    m = AUTH_URL_PATTERN.search(output)
    if not m:
        return None
    return m.group(0)


def _iso_now() -> str:
    return datetime.now(timezone.utc).isoformat()


def _login_from_status_json(data: dict[str, Any]) -> str | None:
    self_block = data.get("Self")
    if not isinstance(self_block, dict):
        return None
    uid = self_block.get("UserID")
    users = data.get("User")
    if not isinstance(users, dict) or uid is None:
        return None
    key = str(uid)
    prof = users.get(key)
    if not isinstance(prof, dict):
        return None
    for field in ("LoginName", "DisplayName"):
        val = prof.get(field)
        if isinstance(val, str) and val.strip():
            return val.strip()
    return None


def _login_from_status_text(stdout: str) -> str | None:
    for line in stdout.splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        parts = line.split()
        if len(parts) >= 3:
            candidate = parts[2]
            if "@" in candidate or candidate.endswith("@"):
                return candidate
    return None


def _tailscale_logged_in_identity() -> str | None:
    """
    Return Tailscale login identity string if connected, None if not logged in.
    Return empty string if connected but identity could not be parsed.
    """
    r = _run_tailscale(["status", "--json"])
    if r.returncode == 0 and (r.stdout or "").strip():
        try:
            data = json.loads(r.stdout)
            if isinstance(data, dict):
                hint = _login_from_status_json(data)
                if hint:
                    return hint
        except json.JSONDecodeError:
            pass

    r2 = _run_tailscale(["status"])
    if r2.returncode != 0:
        return None
    hint = _login_from_status_text(r2.stdout or "")
    return hint if hint else ""


def _identity_matches_dashboard(tailscale_user: str, dashboard: str) -> bool:
    a = tailscale_user.strip().lower()
    b = dashboard.strip().lower()
    if not a or not b:
        return False
    if a == b:
        return True
    if "@" not in a or "@" not in b:
        return False
    la, da = a.split("@", 1)
    lb, db = b.split("@", 1)
    if la != lb:
        return False
    if da == db:
        return True
    google_domains = frozenset({"gmail.com", "google.com", "googlemail.com"})
    return da in google_domains and db in google_domains


def _request_login_url() -> dict[str, Any]:
    """Assume Pi is not authenticated; obtain a login.tailscale.com URL."""
    try:
        login_res = _run_tailscale(["login"])
    except (subprocess.TimeoutExpired, FileNotFoundError):
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": "Unable to request Tailscale login link.",
        }

    combined = (login_res.stdout or "") + "\n" + (login_res.stderr or "")
    auth_url = _extract_auth_url(combined)
    if auth_url:
        return {
            "status": "action_required",
            "auth_url": auth_url,
            "expires_at": _iso_now(),
            "message": "Open this link to finish Tailscale sign-in on the Raspberry Pi.",
        }

    return {
        "status": "error",
        "auth_url": None,
        "expires_at": None,
        "message": "Could not extract a Tailscale auth URL from command output.",
    }


def _logout_then_login() -> dict[str, Any]:
    try:
        _run_tailscale(["logout"])
    except (subprocess.TimeoutExpired, FileNotFoundError):
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": "Tailscale command is unavailable.",
        }
    try:
        status_after = _run_tailscale(["status"])
    except (subprocess.TimeoutExpired, FileNotFoundError):
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": "Tailscale command is unavailable.",
        }
    if status_after.returncode == 0:
        return {
            "status": "error",
            "auth_url": None,
            "expires_at": None,
            "message": (
                "Could not sign the Pi out of Tailscale. Update the systemd unit from the repo "
                "(pi_tailscale_auth_agent.service runs as root so tailscale logout works), then "
                "`sudo systemctl daemon-reload && sudo systemctl restart pi_tailscale_auth_agent`."
            ),
        }
    return _request_login_url()


def _resolve_auth_link(force_reauth: bool, dashboard_email: str | None) -> dict[str, Any]:
    if not TOKEN:
        return {
            "status": "error",
            "auth_url": None,
            "expires_at": None,
            "message": "Pi helper token is not configured.",
        }

    if force_reauth:
        return _logout_then_login()

    dash = (dashboard_email or "").strip()
    if dash:
        ident = _tailscale_logged_in_identity()
        if ident is None:
            return _request_login_url()
        if ident and _identity_matches_dashboard(ident, dash):
            return {
                "status": "already_authenticated",
                "auth_url": None,
                "expires_at": None,
                "message": "Pi is already signed in to Tailscale with this dashboard account.",
            }
        return _logout_then_login()

    try:
        status_res = _run_tailscale(["status"])
    except (subprocess.TimeoutExpired, FileNotFoundError):
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": "Tailscale command is unavailable.",
        }

    if status_res.returncode == 0:
        return {
            "status": "already_authenticated",
            "auth_url": None,
            "expires_at": None,
            "message": "Pi is already signed in to Tailscale.",
        }

    return _request_login_url()


class Handler(BaseHTTPRequestHandler):
    server_version = "PiTailscaleAuthAgent/1.0"

    def _authorized(self) -> bool:
        token = self.headers.get("X-Pi-Agent-Token", "")
        return bool(token) and token == TOKEN

    def _reject_unauthorized(self) -> None:
        _json_response(self, 401, {"status": "error", "message": "Unauthorized"})

    def do_GET(self) -> None:  # noqa: N802
        if self.path == "/v1/tailscale/health":
            return _json_response(
                self,
                200,
                {
                    "status": "ok",
                    "service": "pi_tailscale_auth_agent",
                    "timestamp": _iso_now(),
                },
            )

        _json_response(self, 404, {"status": "error", "message": "Not found"})

    def do_POST(self) -> None:  # noqa: N802
        if self.path != "/v1/tailscale/auth-link":
            return _json_response(self, 404, {"status": "error", "message": "Not found"})

        if not self._authorized():
            return self._reject_unauthorized()

        length_header = self.headers.get("Content-Length", "0")
        try:
            length = min(int(length_header), 4096)
        except ValueError:
            length = 0
        raw = self.rfile.read(length) if length > 0 else b""
        force_reauth = False
        dashboard_email: str | None = None
        if raw.strip():
            try:
                parsed = json.loads(raw.decode("utf-8"))
                if isinstance(parsed, dict):
                    force_reauth = bool(parsed.get("force_reauth"))
                    de = parsed.get("dashboard_email")
                    if isinstance(de, str) and de.strip():
                        dashboard_email = de.strip()
            except json.JSONDecodeError:
                pass

        result = _resolve_auth_link(force_reauth, dashboard_email)
        if result.get("status") not in STATUS_ALLOWED:
            result = {
                "status": "error",
                "auth_url": None,
                "expires_at": None,
                "message": "Internal status validation failed.",
            }
        _json_response(self, 200, result)

    def log_message(self, fmt: str, *args: Any) -> None:
        # Prevent leaking command data to stdout logs by default.
        return


class ThreadingHTTPServer(ThreadingMixIn, HTTPServer):
    daemon_threads = True


if __name__ == "__main__":
    server = ThreadingHTTPServer((HOST, PORT), Handler)
    server.serve_forever()
