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
    handler.wfile.write(body)


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


def _resolve_auth_link(force_reauth: bool) -> dict[str, Any]:
    if not TOKEN:
        return {
            "status": "error",
            "auth_url": None,
            "expires_at": None,
            "message": "Pi helper token is not configured.",
        }

    if force_reauth:
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
                    "Could not sign the Pi out of Tailscale. The agent user (often www-data) "
                    "needs permission to run: tailscale logout — for example add it to the "
                    "tailscale group, then restart pi_tailscale_auth_agent."
                ),
            }
        return _request_login_url()

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
        # Already authenticated.
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
        if raw.strip():
            try:
                parsed = json.loads(raw.decode("utf-8"))
                if isinstance(parsed, dict):
                    force_reauth = bool(parsed.get("force_reauth"))
            except json.JSONDecodeError:
                force_reauth = False

        result = _resolve_auth_link(force_reauth)
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

