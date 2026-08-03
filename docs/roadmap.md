# Roadmap — From Single-Queue Demo to a Hospital-Grade System

Status: current build is a working single-counter queue app (one global line,
auto-generated `A-XXX` tickets, admin board + live customer display, real-time
updates via Laravel Reverb). This roadmap describes what is needed to make it
usable in a hospital setting.

Legend: [ ] pending  [~] in progress  [x] done

## Phase A — Multi-service / counters (top priority)

A hospital does not have one global queue. There are registration, multiple
polyclinics, labs, and pharmacies.

- [ ] `services` / `locations` model; add `service_id` + `counter` to `queue_entries`
- [ ] Contextual ticket codes (e.g. `RD-A-012`, `PG-003`) instead of a single `A-XXX`
- [ ] Per-service filtering on the admin board and customer display
- [ ] "A-012 → Counter 3" shown on the display + voice call (TTS)
- [ ] Per-service admin access (a service admin cannot manage other services)

**Goal:** support many counters and services with one shared codebase.
**Schema changes:** new `services` table; `queue_entries` gets `service_id`, `counter`.
**App changes:** new/updated models, controllers, board/display pages, seeder.
**Benefit:** unlocks every other hospital feature (per-service stats, priority,
per-counter display).

## Phase B — Patient data

Tickets are currently just codes. In a hospital a ticket is bound to a patient.

- [ ] `patients` model (name, no RM, NIK, date of birth, gender) + FK from `queue_entries`
- [ ] Registration / intake flow replaces the manual "Add ticket" action
- [ ] Privacy: no personal health information on the public display or broadcast channel

**Goal:** link every ticket to real patient data.
**Schema changes:** new `patients` table, `patient_id` FK on `queue_entries`.
**App changes:** registration form, patient lookup, board/dashboard display of
names (server-side only).
**Benefit:** records become meaningful and auditable.

## Phase C — Priority & triage

- [ ] Priority levels per ticket (normal / elderly / pregnant / emergency) with weighting
- [ ] Formalize skip / no-show handling (existing `skipped` status + `cancel_reason`)
- [ ] Wait-time estimates from average service duration per service

**Goal:** fairness for urgent patients without abandoning FIFO for normal cases.
**Schema changes:** `priority` + `joined_at` on `queue_entries`; optional `cancel_reason`.
**App changes:** priority-aware ordering in `next()`, estimated wait on display.
**Benefit:** safer triage and better patient expectations.

## Phase D — Statistics & reporting

- [ ] Volume per day / service / counter
- [ ] Average and p95 service duration
- [ ] Peak hours and no-show rate
- [ ] Managerial dashboard (extend existing `dashboard`) + CSV export

**Goal:** data for hospital management decisions.
**App changes:** aggregation queries, dashboard widgets, export endpoints.
**Benefit:** justifies the system to management and staff.

## Phase E — Operations & security

- [ ] Audit trail & soft-delete (changes never silently lost)
- [ ] Appointments / scheduling (slotted, not always end-of-queue)
- [ ] Kiosk self-service intake (public form)
- [ ] Rate-limit public endpoints; no PHI on broadcast; DB backups
- [ ] Granular roles (front-office, per-service admin) beyond `is_admin`

**Goal:** production-readiness, compliance, and resilience.
**App changes:** role management, audit logging, appointment model, kiosk page.
**Benefit:** safe, compliant, and reliable enough for daily hospital operation.

## Dependencies

- Phases C and D depend on Phase A (per-service data).
- Phase B is independent and can be started at any time.
- Phase E items can be introduced incrementally at any point.

Suggested start: **Phase A** — nearly every other feature builds on multi-service support.
