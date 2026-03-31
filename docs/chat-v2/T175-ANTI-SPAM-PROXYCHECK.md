# T175 — Anti-spam / anti-ban-evasion — proxycheck.io

## Goals

- **Detect** proxy/VPN/high-risk IPs using `proxycheck.io` (server-side).
- **Deny** high-risk traffic on the highest leverage endpoints: register/login/guest + posting messages (room/private).
- **Record** ban-related abuse signals (banned IP access attempts + proxycheck denies) for moderation follow-up.
- **Never leak** the secret API key to frontend assets.

## Integration points

- **Auth**
  - `POST /api/v1/auth/register` (`tag=auth_register`)
  - `POST /api/v1/auth/login` (`tag=auth_login`)
  - `POST /api/v1/auth/guest` (`tag=auth_guest`)
- **Posting**
  - `POST /api/v1/rooms/{room}/messages` (`tag=chat_post_room`)
  - `POST /api/v1/private/peers/{peer}/messages` (`tag=chat_post_private`)
- **Banned IP middleware**
  - `RejectBannedIp` records a `ban_evasion_events` row on every blocked request.

## Policy (current)

- **Proxy/VPN detection** (v2 `proxy=yes` or `type=VPN`): deny by default.
- **Risk score** (`risk` with `risk=2`): deny if `risk >= PROXYCHECK_DENY_RISK_THRESHOLD` (default **67**).
- **Graceful degradation**: if proxycheck is down/timeout/returns non-2xx, we **do not block** the request (but log a warning).

## Configuration

All configuration is server-side only (Laravel `config/services.php`):

- `PROXYCHECK_ENABLED` (default `false`)
- `PROXYCHECK_API_KEY` (secret; **never commit**)
- `PROXYCHECK_TIMEOUT_MS` (default `1500`)
- `PROXYCHECK_CACHE_TTL_SECONDS` (default `600`)
- `PROXYCHECK_DENY_IF_PROXY_OR_VPN` (default `true`)
- `PROXYCHECK_DENY_RISK_THRESHOLD` (default `67`)

## Storage / audit trail

Table `ban_evasion_events` stores:

- `action=banned_ip_request` — request blocked by `RejectBannedIp`
- `action=proxycheck_denied` — request denied by proxycheck policy

## API choice

We use **Stable v2** endpoint with flags:

- `vpn=1` (proxy + vpn checks)
- `risk=2` (risk score + attack history)
- `tag=<context>` (dashboards stats context; safe string)

Docs reference: `https://proxycheck.io/api`.

