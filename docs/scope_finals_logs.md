# Scope Finals Logs - Frontend Logs and Filtering

## 1. Purpose

This document is the implementation reference for `frontend-logs-and-filtering` in `docs/scope_todo_finals.md`.
It explains the why, what, and process for building separated logs in the frontend for:

- child device activity
- parent/admin system changes

It also defines filtering behavior so logs are usable for monitoring, investigation, and reporting.

## 2. Why This Is Needed

1. Parents and admins need clear visibility of device behavior and policy changes.
2. Panel/manuscript guidance requires activity logs and clear role differentiation.
3. A single mixed log feed is hard to audit; split streams reduce confusion.
4. Filtering is required to quickly find violations, changes, and timeline events.
5. Structured logs become the base input for reporting and email alerts.

## 3. Scope (What To Build)

### 3.1 Frontend Log Streams

1. Child Device Activity Logs
   - source: websocket activity + stored browsing/access events
   - examples: connected/disconnected, blocked website accessed, flagged website visited, time expired, time granted

2. Parent/Admin Change Logs
   - source: backend audit trail of create/update/delete/config actions
   - examples: schedule updates, website blocklist changes, role assignment changes, device policy changes

### 3.2 Filtering and Viewing Features

1. Date range filter (`from`, `to`)
2. Role filter (`admin`, `parent`, `child-device`, `guest`)
3. Device filter (specific device or all)
4. Event type filter (connection, violation, policy-change, access-control, etc.)
5. Status filter (`info`, `warning`, `critical`, `success`, `failed`)
6. Keyword search (device name, domain, actor, notes)
7. Sorting by timestamp (default: newest first)
8. Pagination for large datasets
9. Export filtered result (CSV first, PDF optional)

## 4. Reference to Existing WebSocket Events

For child activity live updates, reuse currently implemented websocket events:

1. `DeviceConnected`
2. `DeviceDisconnected`
3. `BlockedWebsiteAccessed`
4. `FlaggedWebsiteVisited`
5. `TimeExpired`
6. `TimeGranted`

These events should populate the Child Device Activity Logs stream in near real-time, while keeping persisted history available through API/database-backed listing.

## 5. Data Contract (Suggested Log Entry Shape)

Use a normalized structure for both streams:

- `id`
- `timestamp`
- `stream` (`child_activity` or `parent_admin_changes`)
- `event_type`
- `status`
- `actor_type` (`system`, `admin`, `parent`, `device`)
- `actor_id` (nullable)
- `device_id` (nullable)
- `device_name` (nullable)
- `target` (domain, policy, schedule, account, etc.)
- `summary` (short human-readable text)
- `metadata` (JSON details for drill-down)

## 6. Frontend Process Flow

1. User opens Logs page.
2. User chooses stream tab:
   - Child Activity
   - Parent/Admin Changes
3. Frontend loads initial dataset using default filter preset:
   - date range: last 24 hours
   - sort: newest first
4. Frontend subscribes to websocket channel for live child events.
5. User applies filters/search.
6. Frontend requests filtered results from API.
7. UI updates table/timeline and count summary.
8. User can export current filtered view.

## 7. UI Structure (Suggested)

1. Header with stream selector and quick counters
2. Filter bar (date, role, device, event type, status, keyword)
3. Logs table/timeline
4. Right panel or modal for full metadata details
5. Export button tied to active filter state

## 8. Implementation Steps

1. Define backend endpoints for each stream with unified filter parameters.
2. Ensure parent/admin action logs are captured in backend audit logging.
3. Build child stream adapter that merges websocket live events with persisted logs.
4. Build reusable frontend filter component.
5. Implement logs list component with sorting and pagination.
6. Add export endpoint and frontend action.
7. Add authorization checks so parents only view owned scope; admin can view global scope.
8. Add tests for filters, role boundaries, and websocket rendering.

## 9. Acceptance Criteria

1. Child and parent/admin logs are displayed in separate streams.
2. All filters work together and return correct records.
3. Websocket events appear in child logs without full page reload.
4. Parents cannot see unrelated devices/accounts.
5. Exports respect active filters and role scope.
6. Performance remains usable for large records (pagination required).

## 10. Traceability Notes

- `docs/panel_adjustment.md`
  - Include activity logs
  - Differentiate roles clearly
- `docs/consultation_manuscriptPART.md`
  - Document admin activity logs
  - Maintain role-based clarity in system design
- `docs/scope_todo_finals.md`
  - Section: `frontend-logs-and-filtering`

## 11. Next Document Linkage

After implementing this logs scope:

1. Connect outputs to `reporting-and-email-config`
2. Update `documentation-alignment` last with actual evidence:
   - changed files
   - tested scenarios
   - screenshots/log samples

## 12. Implementation Reference Update (2026-03-13)

`frontend-logs-and-filtering` has been implemented with a unified logs page that separates:

1. `child_activity` stream
2. `parent_admin_changes` stream

### 12.1 Implemented Behavior

1. Added new logs routes:
   - `GET /logs` -> unified logs page
   - `GET /logs/export` -> CSV export for current filtered scope
2. Added stream tabs with separate datasets:
   - Child Activity (live + persisted event sources)
   - Parent/Admin Changes (configuration and policy updates)
3. Added filter bar with combined filtering support:
   - date/time range (`from`, `to`)
   - role (`admin`, `parent`, `child-device`, `guest`)
   - device
   - event type
   - status
   - keyword
   - sort order
4. Added pagination (20 rows per page).
5. Added CSV export that respects active filter state.
6. Added WebSocket live panel on Child Activity stream using existing events:
   - `DeviceConnected`
   - `DeviceDisconnected`
   - `BlockedWebsiteAccessed`
   - `FlaggedWebsiteVisited`
   - `TimeExpired`
   - `TimeGranted`
7. Added sidebar navigation link for Logs.

### 12.2 Implemented Files

1. `app/Http/Controllers/LogsController.php` (new)
   - stream aggregation
   - shared filter handling
   - sorting + pagination
   - CSV export
2. `resources/views/logs/index.blade.php` (new)
   - stream tabs
   - filter UI
   - logs table
   - pagination
   - child live websocket feed panel
3. `routes/web.php`
   - added `logs.index`
   - added `logs.export`
4. `resources/views/layouts/sidebar.blade.php`
   - connected Logs navigation item to logs routes

### 12.3 Verification Performed

1. Controller syntax check:
   - `php -l app/Http/Controllers/LogsController.php`
2. Route registration check:
   - `php artisan route:list --name=logs`
   - confirmed `logs.index` and `logs.export` are registered
3. IDE lints:
   - no lint errors in changed files

### 12.4 Notes / Current Constraints

1. Child stream persisted history is assembled from available persisted models (`access_attempts`, `browsing_logs`, `device_time_grants`, `device_sessions`) and is enhanced with live websocket events in UI.
2. Parent/Admin stream uses available persisted configuration/policy entities (`blocked_websites`, `flagged_websites`, `device_schedules`, `devices`) and derives change entries from create/update timestamps.
3. Delete-action history requires explicit backend audit trail persistence; this can be added next when implementing full audit logging for strict CRUD traceability.

## 13. Verification Evidence Update (2026-03-13)

This section records the final verification pass for `frontend-logs-and-filtering` after implementation updates.

### 13.1 Confirmed Functional Results

1. Child Activity stream is rendering persisted rows from:
   - `browsing_logs`
   - `access_attempts`
   - `device_time_grants`
   - `device_sessions`
2. Child live events are rendering in UI through websocket subscription on private `user.{id}` channels.
3. Parent/Admin Changes stream is rendering policy/config rows from:
   - `blocked_websites`
   - `flagged_websites`
   - `device_schedules`
   - `devices`
4. Role scope behavior is validated:
   - parent account sees own scope
   - admin account test data is viewable under admin session
5. Filter behavior is validated for combined usage:
   - date range, role, device, event type, status, keyword, sorting

### 13.2 Status Naming Alignment

1. UI and backend filter semantics were aligned from **severity** to **status** for contextual accuracy.
2. `status` values currently used:
   - `critical`
   - `warning`
   - `info`
   - `success`
   - (`failed` is supported in filter options for forward compatibility)
3. Backward compatibility was preserved for old query links by accepting legacy `severity` query parameter as fallback when `status` is absent.

### 13.3 Export Verification

1. Export structure was aligned to match frontend table order exactly:
   - `timestamp`, `type`, `status`, `role`, `device`, `target`, `summary`
2. Timestamp format in export was aligned with frontend table:
   - `Mon DD, YYYY HH:mm:ss` (example: `Mar 13, 2026 03:10:48`)
3. Styled Excel export was added for presentation and reporting clarity:
   - yellow highlighted header row
   - status-based color fill
   - auto-sized columns to avoid text overlap
   - frozen header row
4. Raw CSV export route remains available for machine-friendly export workflows.

### 13.4 Evidence Artifacts Captured

1. Child Activity table with normalized status badges and expected rows.
2. Parent/Admin Changes table showing configuration + policy-change rows from admin/parent actions.
3. Excel export sample confirming:
   - matching column order
   - readable spacing/no overlap
   - consistent timestamp format

### 13.5 Completion Status for This Scope

`frontend-logs-and-filtering` is functionally complete for current architecture and has verified outputs for:

1. separated streams
2. live + persisted child activity visibility
3. parent/admin change visibility
4. combined filters and pagination
5. role-scope boundaries (parent/admin)
6. export fidelity (CSV + styled Excel)

### 13.6 Event Type to Status Mapping (Implemented)

This section defines the implemented mapping rules used by `LogsController` to normalize events.

#### Child Activity stream

1. `violation` -> `critical`
   - source: `AccessAttempt`
   - condition: `type = blocked_website`
2. `access-control` -> `warning`
   - source: `AccessAttempt`
   - condition: `type = flagged_website`
3. `access-control` -> `info`
   - source: `BrowsingLog`
4. `time-granted` -> `success`
   - source: `DeviceTimeGrant`
5. `connection` -> `success`
   - source: `DeviceSession`
   - condition: session start event (`started_at`)
6. `connection` -> `info`
   - source: `DeviceSession`
   - condition: session end event (`ended_at`)

#### Parent/Admin Changes stream

1. `policy-change` -> `success`
   - create events for:
     - blocked website
     - flagged website
     - schedule
2. `policy-change` -> `info`
   - update events for:
     - blocked website
     - flagged website
     - schedule
   - update rule: only when `updated_at != created_at`
3. `configuration` -> `success`
   - create events for:
     - device
4. `configuration` -> `info`
   - update events for:
     - device
   - update rule: only when `updated_at != created_at`

