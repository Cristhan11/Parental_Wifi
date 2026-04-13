# Flagged websites — quick test (household-wide)

Flagged sites are **not** blocked in dnsmasq. They apply to **all child devices** for your parent account (`user_id`). Detection uses **DNS logs** + the **`ParseNetworkLogs`** job.

## 1. UI (CRUD)

1. Log in as a parent → **Flagged websites** (`/flagged-websites`).
2. **Flag Website** → enter a URL (e.g. `https://example.com`) → optional reason → submit.
3. Expect green success and a row with URL + extracted **domain** (no device column).
4. **Search / Filter** — try a domain fragment.
5. **Edit** / **Delete** — confirm success messages.

## 2. Duplicate domain

Flag the same normalized domain twice for the same parent → second save should show a **validation error** on the URL field.

## 3. Monitoring (Pi / real traffic)

Prerequisites on the Raspberry Pi:

- Child uses Pi as DNS (typical on your AP setup).
- dnsmasq logging to the file Laravel reads (`NETWORK_LOG_PATH` in `.env`).
- **Queue worker** and/or **scheduler** running so `ParseNetworkLogs` runs.

Then:

1. Add a flagged domain the child can open (must **not** be on the blocked list).
2. Visit it from the child device; wait for the next log parse cycle.
3. Check **Logs** / access attempts (or DB: `access_attempts` with `type = flagged_website`).

If nothing appears, verify logging path, queue, and `docs/BROWSING_LOGS_REFERENCE.md`.

## 4. Blocked vs flagged

If the same domain is **blocked**, DNS stops the visit — you should **not** expect a flagged visit for that domain.

## Related

- Full notes: `docs/FLAGGED_WEBSITES_TESTING_GUIDE.md` (includes legacy sections; see top “April 2026” note).
- Pi logging: `docs/BROWSING_LOGS_REFERENCE.md`
- dnsmasq one-time setup (blocked sites only): `docs/PI_DNSMASQ_ONE_TIME_SETUP.md`
