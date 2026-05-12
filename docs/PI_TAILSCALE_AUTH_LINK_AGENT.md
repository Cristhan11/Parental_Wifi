# Pi Tailscale Auth Link Agent

This guide documents the local helper service used by Laravel Profile to request a Tailscale sign-in URL for the Raspberry Pi.

## Security model

- Service listens on `127.0.0.1` only.
- Requests must include `X-Pi-Agent-Token`.
- Only allowlisted command is executed (`/usr/bin/tailscale`).
- Command timeout is enforced (`PI_AGENT_COMMAND_TIMEOUT_SECONDS`).
- Response is sanitized JSON only (no raw command output is returned).

## API contract

### Health

- `GET /v1/tailscale/health`
- Response:

```json
{
  "status": "ok",
  "service": "pi_tailscale_auth_agent",
  "timestamp": "2026-05-08T03:00:00+00:00"
}
```

### Auth link

- `POST /v1/tailscale/auth-link`
- Header: `X-Pi-Agent-Token: <secret>`
- Optional JSON body (max ~4 KiB): `{ "force_reauth": true }`. When `force_reauth` is true, the agent runs `tailscale logout` on the Pi, then `tailscale login` and returns a fresh browser URL so the device can sign in with a different identity. The service user (often `www-data`) must be allowed to run `tailscale logout` (typically: add that user to the `tailscale` group and restart `pi_tailscale_auth_agent`).
- Response statuses:
  - `already_authenticated`
  - `action_required`
  - `unavailable`
  - `error`

Example:

```json
{
  "status": "action_required",
  "auth_url": "https://login.tailscale.com/...",
  "expires_at": "2026-05-08T03:02:10+00:00",
  "message": "Open this link to finish Tailscale sign-in on the Raspberry Pi."
}
```

## Deployment steps on Raspberry Pi

1. Place script:
   - `scripts/pi_tailscale_auth_agent.py`
2. Create service:
   - Copy `scripts/pi_tailscale_auth_agent.service` to `/etc/systemd/system/`
3. Add secret token using drop-in:
   - `sudo systemctl edit pi_tailscale_auth_agent`
   - Add:
     - `[Service]`
     - `Environment=PI_AGENT_TOKEN=<strong-random-token>`
4. Reload and start:
   - `sudo systemctl daemon-reload`
   - `sudo systemctl enable --now pi_tailscale_auth_agent`
5. Verify:
   - `curl -s http://127.0.0.1:9098/v1/tailscale/health`

## Laravel configuration

Set these values in `.env`:

- `PI_AGENT_BASE_URL=http://127.0.0.1:9098`
- `PI_AGENT_TOKEN=<same-token-as-agent>`
- `PI_AGENT_TIMEOUT_SECONDS=8`

## Troubleshooting

- `Pi helper service is unavailable`:
  - Check service: `sudo systemctl status pi_tailscale_auth_agent`
  - Check logs: `sudo journalctl -u pi_tailscale_auth_agent -n 100 --no-pager`
- `Pi helper service rejected the request`:
  - Verify `PI_AGENT_TOKEN` matches between Laravel and service.
- No auth URL returned:
  - Run `tailscale status` and `tailscale login` manually once to validate host setup.

