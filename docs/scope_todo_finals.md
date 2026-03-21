# Scope TODO Finals - Final Adjustments

## Purpose

This document is the final implementation checklist for project completion. It consolidates panel and manuscript guidance while prioritizing implementation work first and documentation-alignment last.

## Source References

- `docs/panel_adjustment.md`
- `docs/consultation_manuscriptPART.md`
- `docs/scope.md`

## Execution Order

1. frontend-logs-and-filtering
2. reporting-and-email-config
3. account-separation-and-access-model
4. remote-dashboard-access
5. validation-and-done-criteria
6. documentation-alignment (last)

## TODO Groups

### frontend-logs-and-filtering

Priority: High  
Expected output: Frontend log pages/components with separated data sources and filtering controls for practical review and investigation.  
Traceability reference: `docs/panel_adjustment.md` (include activity logs), `docs/consultation_manuscriptPART.md` (admin activity logs).
Implementation reference: `docs/scope_finals_logs.md`

- [x] `logs-01` Define separate frontend log views for child-device activity and parent/admin configuration actions.
- [x] `logs-02` Reuse websocket-driven events for child-device live activity entries where applicable.
- [x] `logs-03` Add filters: date range, role, device, event type, status, and keyword search.
- [x] `logs-04` Add pagination and default sorting by latest event timestamp.
- [x] `logs-05` Add export options (CSV/PDF) for filtered logs per role scope.

### reporting-and-email-config

Priority: High  
Expected output: Configurable parent reporting system with alert and digest schedules (daily/weekly/monthly), plus email templates and delivery tracking.  
Traceability reference: Extends final-adjustment scope from `docs/scope.md` next-phase reporting objective.
Implementation reference: `docs/scope_finals_reporting.md`

- [x] `report-01` Add reporting configuration UI for parent notification preferences and recipients.
- [x] `report-02` Add important log/violation alert pipeline to send immediate parent emails.
- [x] `report-03` Add scheduled digest reporting jobs for daily, weekly, and monthly parent summaries.
- [x] `report-04` Add templates for each report frequency with clear metric sections and violations summary.
- [x] `report-05` Add per-parent opt-in/opt-out controls with validation and audit trail.

### account-separation-and-access-model

Priority: High  
Expected output: Clear and enforced separation between child, parent, guest/visitor, and admin experiences across UI and backend permissions.  
Traceability reference: `docs/panel_adjustment.md` (differentiate roles), `docs/consultation_manuscriptPART.md` (role definition across manuscript sections).

- [ ] `acct-01` Finalize account separation rules for child devices, parent devices, guest/visitor devices, and admin.
- [ ] `acct-02` Define permission matrix for dashboard modules by account type.
- [ ] `acct-03` Enforce role-based navigation and page-level access controls in frontend.
- [ ] `acct-04` Enforce backend authorization checks for all protected endpoints by role.
- [ ] `acct-05` Add role labels and naming convention hints in device/account lists and forms.

Account/access baseline to enforce:

| Role type | Can access dashboard | Can manage devices | Can change policies/schedules | Can view logs | Can receive reports |
| --- | --- | --- | --- | --- | --- |
| Admin | Yes (full) | Yes (global) | Yes (global) | Yes (global) | Optional system-level |
| Parent | Yes (own scope) | Yes (owned devices only) | Yes (owned devices only) | Yes (owned devices only) | Yes |
| Child device | No parent/admin dashboard | No | No | No (self-service only if designed) | No |
| Guest/visitor device | No parent/admin dashboard | No | No | No | No |

### remote-dashboard-access

Priority: High  
Expected output: Controlled remote dashboard access for parent/admin roles with explicit security and deployment requirements.  
Traceability reference: Final-adjustment operational expansion aligned with parent/admin oversight goals in `docs/scope.md`.

- [ ] `remote-01` Define remote access scope for parent and admin dashboards (allowed actions and restrictions).
- [ ] `remote-02` Add secure authentication requirements for remote access (verified accounts, strong sessions, throttling).
- [ ] `remote-03` Add network exposure checklist (TLS, domain/port, firewall rules, reverse proxy, hardening).
- [ ] `remote-04` Add access audit logging for remote sign-ins, failed attempts, and sensitive actions.
- [ ] `remote-05` Add rollout plan with staged testing for local, LAN, and external access paths.

Security baseline for remote access:

- Require verified parent/admin account and secure password policy.
- Require HTTPS with valid certificates before enabling external access.
- Apply rate limiting and account lockout thresholds to login endpoints.
- Log remote login success/failure and high-risk actions with actor + timestamp + source IP.
- Restrict remote-visible data to role scope and enforce server-side checks.

### validation-and-done-criteria

Priority: High  
Expected output: Explicit completion criteria and evidence requirements to confirm all final adjustments are implemented and testable.  
Traceability reference: Completes compliance visibility requested across panel/manuscript-backed features.

- [ ] `validate-01` Define acceptance criteria for logs filtering and separation behavior.
- [ ] `validate-02` Define acceptance criteria for reporting schedules and email delivery behavior.
- [ ] `validate-03` Define acceptance criteria for account separation and authorization boundaries.
- [ ] `validate-04` Define acceptance criteria for remote dashboard security and reliability.
- [ ] `validate-05` List required test evidence artifacts (screenshots, event samples, schedule outputs, mail logs).

### documentation-alignment (last phase)

Priority: Last (post-implementation)  
Expected output: Documentation updated only after implementation is stable, with exact references to the final code and test artifacts.  
Traceability reference: `docs/panel_adjustment.md`, `docs/consultation_manuscriptPART.md`.

- [ ] `docs-01` Update role differentiation references in docs after feature implementation is complete.
- [ ] `docs-02` Update naming conventions and role labeling references after UI changes are finalized.
- [ ] `docs-03` Update activity logging narrative to match implemented log architecture.
- [ ] `docs-04` Add manuscript traceability notes for each implemented final-adjustment feature.
- [ ] `docs-05` Mark each panel/manuscript adjustment as compliant with exact evidence links.

## Documentation Alignment Traceability Matrix (Complete Last)

| Adjustment target | Source reference | Implementation evidence placeholder |
| --- | --- | --- |
| Role differentiation (Admin/Parent/Child/Guest) | `docs/panel_adjustment.md`, `docs/consultation_manuscriptPART.md` Chapter I/IV role notes | `TBD after implementation` |
| Activity log coverage for admin and child-related activity | `docs/panel_adjustment.md` admin logs item, `docs/consultation_manuscriptPART.md` logging notes | `TBD after implementation` |
| Role labels and naming convention guidance | `docs/panel_adjustment.md` documentation and UI/UX role notes | `TBD after implementation` |
| Reporting and notification behavior for parents | `docs/scope.md` advanced reporting next phase + final adjustments | `TBD after implementation` |
| Remote dashboard access governance | Final-adjustment requirement in this checklist | `TBD after implementation` |

## Panel/Manuscript Mapping Notes (Fill After Build)

- `map-01` Role differentiation updates should cite:
  - `docs/panel_adjustment.md` -> Documentation and Roles
  - `docs/consultation_manuscriptPART.md` -> Chapter I `1.3 The Project/Solution`, Chapter IV `4.1.2 Software Design`
- `map-02` Activity logging updates should cite:
  - `docs/panel_adjustment.md` -> Admin Features (activity logs)
  - `docs/consultation_manuscriptPART.md` -> Chapter I `1.3` and Chapter IV `4.1.2` logging references
- `map-03` Role labels and naming convention updates should cite:
  - `docs/panel_adjustment.md` -> Documentation & Roles + UI/UX Improvements
  - `docs/consultation_manuscriptPART.md` -> role clarity comments in Chapters I/IV
- `map-04` Child/parent/guest/admin separation behavior documentation should cite:
  - `docs/panel_adjustment.md` -> role differentiation baseline
  - `docs/consultation_manuscriptPART.md` -> objectives and scope/delimitation role sections
- `map-05` Remote access and reporting additions should cite:
  - final-adjustment requirement in this file and implementation outputs
  - `docs/scope.md` next-phase reporting references when applicable

Use this evidence format for each completed mapping item:

- Feature/change:
- Source requirement:
- Implemented in:
- Test evidence:
- Compliance note:

## Final Acceptance Checklist

### Logs and Filtering

- [x] Child-device logs and parent/admin change logs are separated in frontend views.
- [x] Filters (date range, role, device, type, status, keyword) are all functional.
- [x] Websocket-based live entries appear in the expected child-activity stream.
- [x] Log sorting, pagination, and export follow current filter context.

### Reporting and Email

- [x] Parent reporting preferences are configurable per account.
- [x] Violation/important alerts are sent immediately to configured parent emails.
- [x] Daily/weekly/monthly scheduler jobs run and produce per-parent outputs.
- [x] Report templates include key metrics, violations summary, and period labels.

### Account Separation

- [ ] Admin, parent, child, and guest access boundaries are enforced at UI and backend levels.
- [ ] Parent actions are constrained to parent-owned devices and settings.
- [ ] Role labels and naming guidance are visible in relevant forms and lists.

### Remote Access

- [ ] Parent/admin remote login requires secure authentication controls.
- [ ] HTTPS and network hardening prerequisites are documented and validated before exposure.
- [ ] Remote activity is auditable (success/failure logins + sensitive action logs).
- [ ] External access tests pass for local, LAN, and remote routes according to rollout plan.

### Documentation Alignment (Last)

- [ ] Panel/manuscript references are updated only after implementation evidence exists.
- [ ] Each compliance note includes exact proof links to changed features and tests.
- [ ] Final docs use consistent role and feature terminology with implementation.
