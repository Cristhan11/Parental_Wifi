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
import queue
import re
import shutil
import subprocess
import sys
import threading
import time
import traceback
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, HTTPServer
from socketserver import ThreadingMixIn
from typing import Any


HOST = os.getenv("PI_AGENT_HOST", "127.0.0.1")
PORT = int(os.getenv("PI_AGENT_PORT", "9098"))
TOKEN = os.getenv("PI_AGENT_TOKEN", "")
# Short timeout for status/logout (keep chained calls from stacking into multi-minute waits).
CMD_TIMEOUT = int(os.getenv("PI_AGENT_COMMAND_TIMEOUT_SECONDS", "15"))
# `tailscale login` usually prints the URL quickly; cap avoids hanging the HTTP request for ages.
LOGIN_TIMEOUT = int(os.getenv("PI_AGENT_LOGIN_TIMEOUT_SECONDS", "75"))


def _tailscale_executable() -> str:
    configured = os.getenv("PI_AGENT_TAILSCALE_BIN", "/usr/bin/tailscale")
    if os.path.isfile(configured) and os.access(configured, os.X_OK):
        return configured
    found = shutil.which("tailscale")
    return found if found else configured


TAILSCALE_BIN = _tailscale_executable()

STATUS_ALLOWED = {"already_authenticated", "action_required", "unavailable", "error"}
# Regional login hosts (e.g. login.us.tailscale.com) use the same path shape after the host.
AUTH_URL_PATTERN = re.compile(
    r"https://login(?:\.[a-z0-9-]+)?\.tailscale\.com/[^\s\"'<>]+",
    re.IGNORECASE,
)


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


def _run_tailscale(args: list[str], *, seconds: int | None = None) -> subprocess.CompletedProcess[str]:
    limit = CMD_TIMEOUT if seconds is None else seconds
    return subprocess.run(
        [TAILSCALE_BIN] + args,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
        timeout=limit,
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

    Never raises: timeouts or a missing tailscale binary must not crash the HTTP handler.
    """
    try:
        r = _run_tailscale(["status", "--json"])
        if r.returncode != 0:
            return None
        out = (r.stdout or "").strip()
        if not out:
            return None
        try:
            data = json.loads(r.stdout)
            if isinstance(data, dict):
                hint = _login_from_status_json(data)
                if hint:
                    return hint
        except json.JSONDecodeError:
            pass

        hint = _login_from_status_text(r.stdout or "")
        if hint:
            return hint

        r2 = _run_tailscale(["status"])
        if r2.returncode != 0:
            return None
        hint2 = _login_from_status_text(r2.stdout or "")
        return hint2 if hint2 else ""
    except (subprocess.TimeoutExpired, FileNotFoundError, OSError):
        return None


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


def _decode_proc_output(value: Any) -> str:
    """
    Defensive decoder. `subprocess.run(text=True)` returns str on success, but
    `subprocess.TimeoutExpired.stdout`/`.stderr` are bytes on Python 3.13. We use
    this anywhere we touch captured output so a TypeError never crashes the agent.
    """
    if value is None:
        return ""
    if isinstance(value, bytes):
        return value.decode("utf-8", errors="replace")
    return str(value)


def _request_login_url() -> dict[str, Any]:
    """
    Spawn `tailscale login`, stream its merged output, and return as soon as the
    sign-in URL appears. The CLI is then terminated; `tailscaled` (the daemon)
    owns the pending sign-in state so the user can still finish in the browser.
    """
    try:
        proc = subprocess.Popen(
            [TAILSCALE_BIN, "login"],
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            bufsize=1,
        )
    except FileNotFoundError:
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": (
                "Tailscale CLI was not found. Install Tailscale or set PI_AGENT_TAILSCALE_BIN "
                "to the full path from `which tailscale` on the Pi."
            ),
        }
    except OSError as exc:
        return {
            "status": "unavailable",
            "auth_url": None,
            "expires_at": None,
            "message": f"Could not start tailscale login: {exc}.",
        }

    line_queue: queue.Queue[str | None] = queue.Queue()

    def _reader() -> None:
        try:
            assert proc.stdout is not None
            for line in proc.stdout:
                line_queue.put(_decode_proc_output(line))
        except Exception:
            traceback.print_exc(file=sys.stderr)
        finally:
            line_queue.put(None)

    thread = threading.Thread(target=_reader, daemon=True)
    thread.start()

    auth_url: str | None = None
    captured: list[str] = []
    deadline = time.monotonic() + max(1, LOGIN_TIMEOUT)
    try:
        while True:
            remaining = deadline - time.monotonic()
            if remaining <= 0:
                break
            try:
                line = line_queue.get(timeout=min(0.5, max(0.05, remaining)))
            except queue.Empty:
                continue
            if line is None:
                break
            captured.append(line)
            found = _extract_auth_url(line)
            if found:
                auth_url = found
                break
    finally:
        if proc.poll() is None:
            try:
                proc.terminate()
                try:
                    proc.wait(timeout=3)
                except subprocess.TimeoutExpired:
                    proc.kill()
            except (ProcessLookupError, OSError):
                pass

    if not auth_url:
        auth_url = _extract_auth_url("".join(captured))

    if auth_url:
        return {
            "status": "action_required",
            "auth_url": auth_url,
            "expires_at": _iso_now(),
            "message": "Open this link to finish Tailscale sign-in on the Raspberry Pi.",
        }

    return {
        "status": "unavailable",
        "auth_url": None,
        "expires_at": None,
        "message": (
            "Tailscale login did not print a sign-in URL within the timeout. "
            "Run `sudo tailscale login` on the Pi to confirm the CLI prints a "
            "https://login.tailscale.com/... URL, or raise PI_AGENT_LOGIN_TIMEOUT_SECONDS."
        ),
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

        try:
            result = _resolve_auth_link(force_reauth, dashboard_email)
            if result.get("status") not in STATUS_ALLOWED:
                result = {
                    "status": "error",
                    "auth_url": None,
                    "expires_at": None,
                    "message": "Internal status validation failed.",
                }
            _json_response(self, 200, result)
        except Exception:
            traceback.print_exc(file=sys.stderr)
            _json_response(
                self,
                200,
                {
                    "status": "error",
                    "auth_url": None,
                    "expires_at": None,
                    "message": "Pi helper hit an unexpected error. Check journalctl -u pi_tailscale_auth_agent.",
                },
            )

    def log_message(self, fmt: str, *args: Any) -> None:
        # Prevent leaking command data to stdout logs by default.
        return


class ThreadingHTTPServer(ThreadingMixIn, HTTPServer):
    daemon_threads = True


if __name__ == "__main__":
    server = ThreadingHTTPServer((HOST, PORT), Handler)
    server.serve_forever()
