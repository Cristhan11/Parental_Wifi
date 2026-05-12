#!/usr/bin/env python3
"""
Warm Tailscale paths to every online peer of this node.

Why: the first packet between two Tailscale nodes negotiates DERP relays and
direct UDP paths. If both sides are idle, the next connection attempt is slow
or "connection interrupted" until that handshake finishes. Pinging each online
peer keeps the cached paths warm so a parent's first request from the phone
lands instantly.

Run once a minute from cron (or a systemd timer). It auto-discovers peers via
`tailscale status --json`, so adding/removing devices needs no script edit.

Exits 0 on success, prints nothing on success (cron-friendly). On error or no
peers, exits 0 silently so cron does not spam mail. Use `--verbose` for ad-hoc
debugging.
"""

from __future__ import annotations

import argparse
import json
import os
import subprocess
import sys
from concurrent.futures import ThreadPoolExecutor

TAILSCALE_BIN = os.environ.get("TAILSCALE_BIN", "/usr/bin/tailscale")
PING_BIN = os.environ.get("PING_BIN", "/usr/bin/ping")
STATUS_TIMEOUT_SECONDS = 10
PING_TIMEOUT_SECONDS = 5
PING_DEADLINE_FLAG = "-W"
MAX_PARALLEL = 8


def _list_online_peer_ips() -> list[tuple[str, str]]:
    """Return (hostname, ip) for every online Tailscale peer (excludes Self)."""
    try:
        result = subprocess.run(
            [TAILSCALE_BIN, "status", "--json"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            timeout=STATUS_TIMEOUT_SECONDS,
            check=False,
        )
    except (FileNotFoundError, subprocess.TimeoutExpired):
        return []

    if result.returncode != 0 or not result.stdout:
        return []

    try:
        data = json.loads(result.stdout)
    except json.JSONDecodeError:
        return []

    peers = data.get("Peer") if isinstance(data, dict) else None
    if not isinstance(peers, dict):
        return []

    out: list[tuple[str, str]] = []
    for peer in peers.values():
        if not isinstance(peer, dict):
            continue
        if not peer.get("Online"):
            continue
        ips = peer.get("TailscaleIPs")
        if not isinstance(ips, list) or not ips:
            continue
        ip = next((str(x) for x in ips if isinstance(x, str) and x.startswith("100.")), None)
        if not ip:
            continue
        host = str(peer.get("HostName") or peer.get("DNSName") or ip)
        out.append((host, ip))
    return out


def _ping_one(ip: str) -> bool:
    try:
        rc = subprocess.run(
            [PING_BIN, "-c", "1", PING_DEADLINE_FLAG, "2", ip],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            timeout=PING_TIMEOUT_SECONDS,
            check=False,
        ).returncode
        return rc == 0
    except (FileNotFoundError, subprocess.TimeoutExpired):
        return False


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--verbose", action="store_true", help="print per-peer result")
    args = parser.parse_args()

    peers = _list_online_peer_ips()
    if not peers:
        if args.verbose:
            print("no online Tailscale peers (or status unavailable); nothing to warm")
        return 0

    with ThreadPoolExecutor(max_workers=min(MAX_PARALLEL, len(peers))) as ex:
        results = list(ex.map(lambda p: (p, _ping_one(p[1])), peers))

    if args.verbose:
        for (host, ip), ok in results:
            print(f"{'OK ' if ok else 'MISS'} {ip}  {host}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
