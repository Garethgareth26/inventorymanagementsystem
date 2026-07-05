# Software Architecture Document (SAD)
## Sistem Manajemen Inventori & Optimasi Persediaan — CV Akuna

| Info | Keterangan |
|---|---|
| Versi Dokumen | 1.0 |
| Tanggal | 4 Juli 2026 |
| Sumber | PRD v1.1 (Sistem Inventori CV Akuna) |
| Status | Draft — source of truth arsitektural sebelum desain database |
| Author | Principal Architect (working session with Product Owner) |

**Riwayat perubahan:**
- v1.0 — Dokumen awal, disusun setelah resolusi keputusan arsitektural terbuka dari PRD v1.1 (stack version, RBAC extensibility, parameter versioning, notification strategy, tooling choices).

---

## 1. Purpose & Scope of This Document

This document is the **architectural source of truth** for the CV Akuna Inventory Management System. It exists to answer *"how is this system built and why"* — as opposed to the PRD, which answers *"what does this system do."*

Every subsequent artifact (ERD, module specs, test plan, CI/CD pipeline) must trace back to a decision recorded here. Where the PRD left a decision open or contradictory, this document resolves it explicitly and records the resolution as an Architecture Decision Record (ADR) in Section 10.

This document does **not** contain implementation code. It contains structural decisions, module boundaries, data-flow descriptions, and deployment topology.

---

## 2. Architecture Decision Log (Resolutions to PRD Ambiguities)

These are the binding decisions for this project going forward, confirmed by the Product Owner on 4 Juli 2026, superseding the "or" statements and open contradictions in PRD v1.1.

| # | Topic | PRD Ambiguity | Resolved Decision |
|---|---|---|---|
| 1 | Framework/runtime | PRD said Laravel 13.x/PHP 8.3+; brief said Laravel 12/PHP 8.4 | **Laravel 12, PHP 8.4** |
| 2 | Parameter versioning | Section 9 said single active set; Section 12 Q6 asked about history log | **Single active parameter set per material.** Changes are recorded via audit trail entries only (who/when/old value/new value). No dedicated parameter-history table in v1. |
| 3 | Real-time notification mechanism | Acceptance criteria demanded "near real-time" but no broadcasting layer was specified | **Livewire polling** (`wire:poll`). WebSockets/broadcasting (Reverb/Pusher) explicitly out of scope for v1. |
| 4 | Charting library | Chart.js *or* ApexCharts | **ApexCharts** |
| 5 | PDF generator | dompdf *or* spatie/laravel-pdf | **barryvdh/laravel-dompdf** |
| 6 | File storage | GCS *or* Supabase Storage | **Supabase Storage** |
| 7 | RBAC extensibility vs. simplicity | NFR demanded future role/warehouse extensibility; tech choice recommended hardcoded 2-role Gates | **Extensible role architecture without a permissions package** — see Section 6. |

Any PRD open question **not** listed above (Section 12 of the PRD: ABC thresholds, Safety Stock Z-factor, historical data window, WhatsApp/Email notification, PDF letterhead template, data migration from Excel, user count/access pattern, remote access, hosting cost ownership, go-live date, out-of-scope re-confirmation) remains **Pending Client Decision** — see Section 11. These are not assumed in this document.

---

## 3. Architectural Style & Rationale

**Style: Modular Monolith, server-rendered, deployed as a single stateless container.**

Rationale (carried forward from PRD 8.1, still valid under the confirmed stack):
- Single business entity, 2–3 initial roles, small dev team → the coordination overhead of microservices (service discovery, distributed transactions, network boundaries between EOQ calculation and inventory data) buys nothing at this scale and actively slows delivery.
- Livewire 3 lets the same Laravel process serve both HTML and reactive UI updates, removing the need for a separate SPA build pipeline or a public REST API surface in v1.
- **"Monolith" describes deployment, not internal design.** Internally, the codebase is organized as a **modular monolith** (Section 5) so that if a module ever needs to be extracted into its own service later, the seams already exist.

**Trade-off being accepted:** all modules share one database connection pool and one deploy unit. A bug or long-running query in the Reporting module can, in theory, degrade the Dashboard module's response time. This is acceptable at CV Akuna's traffic scale (internal tool, small user count) and is mitigated by the caching and queuing strategy in Section 7.

---

## 4. System Context (C4 Level 1)

**Actors:**
- **Karyawan (Employee)** — full read/write on operational data, applies EOQ/SS/ROP parameters, manages procurement.
- **Owner** — read-only across the entire system; can run simulations but cannot persist results.
- **Cloud Scheduler** — external actor, not a human, triggers scheduled jobs (critical stock check, cache refresh) via authenticated HTTP calls.

**External Systems:**
- **Supabase (PostgreSQL)** — system of record for all business data.
- **Upstash Redis** — session store, cache store, queue backend.
- **Supabase Storage** — object storage for generated PDF reports.
- **GitHub / GitHub Actions** — source control and CI/CD pipeline.
- **Google Cloud Run / Artifact Registry** — compute and container registry.

```
                    ┌─────────────┐        ┌─────────────┐
                    │  Karyawan   │        │    Owner    │
                    └──────┬──────┘        └──────┬──────┘
                           │  HTTPS                │ HTTPS (read-only)
                           ▼                       ▼
                 ┌───────────────────────────────────────┐
                 │     CV Akuna Inventory System          │
                 │  (Laravel 12 Monolith on Cloud Run)    │
                 └───────┬─────────────┬─────────┬────────┘
                         │             │         │
              ┌──────────▼───┐ ┌───────▼──────┐ ┌▼─────────────┐
              │   Supabase    │ │   Upstash    │ │  Supabase     │
              │  PostgreSQL   │ │    Redis     │ │  Storage      │
              └───────────────┘ └──────────────┘ └───────────────┘
                         ▲
                         │ triggers cron endpoint
                 ┌───────┴────────┐
                 │ Cloud Scheduler │
                 └────────────────┘
```

---

## 5. Container & Module View (C4 Level 2–3)

### 5.1 Single Deployable Container

One Docker image containing PHP-FPM + Nginx, running the Laravel 12 application. This is the only compute container; there is no separate API service or worker service in v1 (queue workers run as a second Cloud Run process/revision consuming the same image if async jobs are needed — see Section 7.3).

### 5.2 Internal Module Boundaries

To satisfy SOLID and "avoid overengineering" simultaneously, the codebase is organized by **domain module** inside the standard Laravel structure, rather than a flat MVC-by-type layout. Each module owns its own models, Livewire components, policies, and business logic classes; modules communicate through well-defined service classes, not direct cross-module Eloquent queries.

Proposed module boundaries (naming to be finalized at implementation time, structure is what matters here):

| Module | Responsibility | Depends on |
|---|---|---|
| **Auth & Access** | Login, session, role assignment, policy resolution | — (foundation module) |
| **Inventory** | Bahan baku & barang jadi master data, stock mutation records, warehouse views | Auth & Access |
| **Calculation Engine** | EOQ / Safety Stock / ROP formulas, simulation vs. applied-parameter comparison, ABC classification | Inventory |
| **Procurement** | Purchase order tracking, status transitions (Menunggu → Dalam Proses → Diterima), auto-generates stock mutation on receipt | Inventory, Calculation Engine |
| **Dashboard & Alerts** | KPI aggregation, ABC donut chart, critical stock polling/alerts | Inventory, Calculation Engine |
| **Reporting** | PDF generation (valuasi aset, performa supplier, mutasi bulanan) | Inventory, Procurement |
| **Audit Trail** | Cross-cutting: records who/when/what for every mutating action | Used by all mutating modules |

**Why this matters architecturally:** the Calculation Engine and Audit Trail are deliberately modeled as **services consumed by other modules**, not modules with their own controllers/routes. This keeps the EOQ/SS/ROP formulas testable in isolation (a hard PRD requirement — Section 8.2 calls out Pest testing specifically for this) and keeps audit logging consistent instead of re-implemented per module.

### 5.3 Dependency Rule

Lower-level modules (Auth & Access, Inventory) must never depend on higher-level modules (Reporting, Dashboard). This is enforced by convention/code review in v1 — no architectural enforcement tooling (e.g., deptrac) is being introduced yet, to avoid overengineering for a small team. This can be revisited if the team grows.

---

## 6. Authorization Architecture (Extensible RBAC without a Permissions Package)

This resolves ADR #7. The goal: support Karyawan/Owner today, and Supervisor/Finance/new-warehouse-scoped-roles tomorrow, **without** pulling in Spatie Permission and without hardcoding `if ($user->role === 'owner')` checks scattered across the codebase.

**Design:**

1. **`roles` table** (not an enum column) — `id`, `name`, `slug`. Two rows exist at launch: `karyawan`, `owner`. Adding a role later is a data change, not a schema migration.
2. **`users.role_id`** — foreign key. A user has exactly one role in v1 (matches PRD's 2-role reality; multi-role-per-user is not requested and would be overengineering to support now).
3. **Laravel Gate + Policy classes**, one policy per module (e.g., `StockMutationPolicy`, `ProcurementPolicy`, `ParameterPolicy`). Policies check `$user->role->slug` against a small, explicit **capability map** defined once in a config file (e.g., `config/capabilities.php`) rather than inline per-policy conditionals. Example shape (illustrative, not final schema):
   - `capabilities.php` maps `role_slug → [capability_key, ...]`
   - Policies check `$user->hasCapability('stock.mutate')` rather than `$user->role->slug === 'karyawan'`
4. **Middleware** (`role:owner`, `role:karyawan` or capability-based middleware) blocks mutating routes server-side — this is non-negotiable per PRD Section 5's explicit design principle (disable in UI **and** block in backend).

**Why this satisfies both constraints:**
- Adding "Supervisor" later means: insert a role row, add entries to the capability map, done — no new package, no schema refactor, no policy class rewrites (unless Supervisor needs genuinely new logic, which is a business-logic change either way).
- No permissions package overhead (no `model_has_permissions` pivot tables, no package upgrade risk) — appropriate for 2–3 roles with no per-user permission overrides required.
- If the business later needs *per-user* permission overrides (not just per-role), that is the trigger to introduce Spatie Permission — a deliberate future decision, not a default.

---

## 7. Non-Functional Architecture

### 7.1 Statelessness (Cloud Run constraint)

- Session driver: `redis` (Upstash) — never `file` or `database` for session in production, since Cloud Run instances are ephemeral and can scale to multiple concurrent instances.
- Cache driver: `redis` — used for ABC classification results and dashboard KPI aggregates (Section 7.2).
- Generated files (PDF reports): written directly to Supabase Storage via Laravel's filesystem abstraction (`Storage::disk('supabase')`), never to local disk.

### 7.2 Caching Strategy

- ABC classification and dashboard KPI cards are **not** computed on every request. They are computed by a scheduled job (triggered via Cloud Scheduler → protected HTTP endpoint → Laravel Scheduler) and cached in Redis with a TTL slightly longer than the schedule interval, so a late job run doesn't cause a cache miss storm.
- Critical stock check (for the polling-based alert, Section 7.4) is a lighter, more frequent job than full ABC recalculation, since it only needs to compare `stok_saat_ini` against `reorder_point` — this is deliberately split from the heavier ABC job to avoid over-computing on every check cycle.

### 7.3 Queue

- Redis-backed queue for any deferred work (e.g., PDF generation for large date ranges, so the request doesn't block on dompdf rendering).
- Queue worker runs as a **separate Cloud Run service** (or a min-instance=1 revision) since Cloud Run's request-driven scaling model doesn't suit a long-lived `queue:work` process well. This is a deployment detail to finalize in the CI/CD document, flagged here so it isn't forgotten.

### 7.4 Notification / Near-Real-Time Alerting (ADR #3)

- Implementation: Livewire component on the dashboard uses `wire:poll.15s` (interval to be tuned) to re-check the critical-stock cache key.
- This is **polling, not push** — explicitly communicated as a v1 limitation. If CV Akuna later requires true push notifications (e.g., WhatsApp/Email per PRD open question #7, or sub-second in-app alerts), that requires introducing a broadcasting layer (Reverb or a third-party service), which is a deliberate architectural addition, not a tweak.
- Polling interval is a trade-off between perceived "real-time-ness" and unnecessary load/cost on a scale-to-zero Cloud Run instance — too aggressive a poll interval works against the cost-saving rationale for choosing Cloud Run in the first place.

### 7.5 Audit Trail Design

- Cross-cutting concern implemented as a dedicated `audit_logs` table (actor, action, subject type/id, old value, new value, timestamp) written to via a single `AuditLogger` service, called from model observers or explicit service-layer calls — not scattered `Log::info()` calls per controller.
- This directly satisfies PRD Section 7 (Auditability) and resolves ADR #2 (parameter changes are audit-logged, not versioned in a separate table).

### 7.6 Security

- Password hashing: Laravel's default (bcrypt or argon2, framework default is fine — no need to override).
- CSRF: Laravel's built-in protection, unchanged.
- Rate limiting: Laravel's built-in throttle middleware on the login route.
- Server-side role validation on every mutating route via middleware (Section 6) — this is the control that actually satisfies PRD's Acceptance Criteria #1 ("Owner cannot perform any write operation via UI or direct request manipulation").

### 7.7 Reliability

- Backup: handled at the Supabase project level (scheduled backups) — to be confirmed against the specific Supabase plan tier CV Akuna is on, since free-tier backup retention differs from paid tiers. **Flagged as a deployment-doc item**, not resolved here.
- Cold start and Supabase auto-pause (PRD 8.3) are accepted trade-offs, not defects — they should be documented in the deployment runbook and communicated to the client as an operating characteristic, not silently absorbed as a "bug we'll fix later."

---

## 8. Key Data Flows (Sequence Narratives)

These describe *behavior across module boundaries*, not database schema (that's the ERD's job, next deliverable).

**8.1 Stock Mutation (Manual, by Karyawan)**
Karyawan submits a mutation (masuk/keluar) → Inventory module validates quantity/material → Inventory module persists mutation and updates `stok_saat_ini` → Audit Trail service logs the action → Dashboard's cached KPI figures become stale until next scheduled recompute (not recomputed synchronously, per Section 7.2).

**8.2 EOQ/SS/ROP Simulation & Application**
Karyawan runs simulation with new cost/lead-time inputs → Calculation Engine computes EOQ/SS/ROP using formulas + Pending-Client-Decision inputs (Z-factor, historical window — Section 11) → result displayed alongside old values for comparison → Karyawan chooses to apply → Calculation Engine overwrites the single active parameter set on the material → Audit Trail logs old value, new value, actor, timestamp (per ADR #2, no separate history table).

**8.3 Purchase Order Receipt**
Karyawan updates PO status to "Diterima" → Procurement module validates the transition → Procurement module calls Inventory module's mutation service (not direct DB write) to record incoming stock → Inventory updates `stok_saat_ini` → Audit Trail logs both the PO status change and the resulting stock mutation.

**8.4 Critical Stock Alert (Polling)**
Cloud Scheduler triggers a lightweight scheduled check → job compares `stok_saat_ini` vs. `reorder_point` per material → writes/updates a Redis cache key of currently-critical materials → Dashboard's Livewire component polls this cache key every N seconds and re-renders the alert bell/table if changed.

**8.5 PDF Report Generation**
User selects report type + date range → Reporting module queries Inventory/Procurement data for that range → renders via dompdf → uploads result to Supabase Storage → returns a signed/temporary download link to the user (never a local file path, per Section 7.1).

---

## 9. Deployment Architecture

*(High-level here; a dedicated CI/CD & Deployment Runbook is the next-but-one deliverable per Section 12.)*

- **Build:** GitHub Actions builds a single Docker image (PHP-FPM + Nginx + Laravel 12/PHP 8.4) on push to main.
- **Test gate:** Pest test suite (unit tests for EOQ/SS/ROP formulas and RBAC policies are the non-negotiable minimum) must pass before deploy proceeds.
- **Registry:** Image pushed to Artifact Registry.
- **Deploy:** Cloud Run pulls the new image; environment variables/secrets (Supabase connection string, Upstash credentials, Supabase Storage keys) are injected via Cloud Run's secret manager integration — never committed to the repo.
- **Migrations:** run as a release step (e.g., a dedicated migration job/command invoked before traffic is routed to the new revision) — exact mechanism to be finalized in the CI/CD doc, since Cloud Run has no native "release phase" like some PaaS platforms.
- **Scheduled jobs:** Cloud Scheduler calls a protected HTTP endpoint (signed/secret-header authenticated) that triggers Laravel's scheduler — since Cloud Run has no built-in cron.

---

## 10. Architecture Decision Records (Summary)

| ADR | Decision | Status |
|---|---|---|
| ADR-001 | Modular monolith, server-rendered via Livewire 3, single Cloud Run container | Accepted |
| ADR-002 | Laravel 12 / PHP 8.4 (overrides PRD's Laravel 13.x/PHP 8.3+ reference) | Accepted |
| ADR-003 | Extensible RBAC via `roles` table + capability map, no permissions package | Accepted |
| ADR-004 | Parameter changes recorded via audit trail only; no parameter-history table | Accepted |
| ADR-005 | Near-real-time alerts via Livewire polling; broadcasting out of scope for v1 | Accepted |
| ADR-006 | ApexCharts, barryvdh/laravel-dompdf, Supabase Storage | Accepted |
| ADR-007 | Queue worker runs as separate Cloud Run process from web container | Accepted, detail pending in deployment runbook |

---

## 11. Pending Client Decisions

These are **not assumed** anywhere in this document or in future design work until resolved. Carried forward from PRD Section 12 (numbering matches PRD for traceability):

- **Q2** — Are additional roles (Supervisor, Finance) planned? (Architecture already accommodates this per Section 6, but confirming affects priority.)
- **Q3** — ABC classification thresholds (exact % cutoffs for A/B/C).
- **Q4** — Safety Stock service-level factor (Z-value) — fixed standard or client policy?
- **Q5** — Historical data window (days/months) used for usage average and standard deviation.
- **Q6** — Reconfirm: is an audit-trail-only approach (ADR-004) sufficient, or does the client actually want a queryable parameter version history? *(This document assumes audit-trail-only per your instruction, but this is a client-facing product decision, not just a technical one — worth explicit client sign-off.)*
- **Q7** — WhatsApp/Email notifications, in addition to in-app polling.
- **Q8** — PDF report template/letterhead requirements.
- **Q9** — Existing Excel data requiring migration/import.
- **Q10** — Expected active user count and usage pattern (business hours only vs. 24/7) — affects Cloud Run min-instance and cold-start tolerance decisions.
- **Q11** — Office-network-only access vs. remote access requirement — affects whether additional network/IP restriction controls are needed.
- **Q12** — Who bears hosting cost post-launch — affects whether cost-optimization trade-offs (scale-to-zero, Supabase free tier) remain acceptable long-term or are a temporary MVP measure.
- **Q13** — Target go-live date.
- **Q14** — Reconfirmation of PRD Section 4.2 out-of-scope items.

---

## 12. Next Steps

1. **Entity-Relationship Diagram (ERD)** and database schema design — this is the next deliverable, and it is directly gated by Pending Decisions Q3, Q4, Q5, and Q6 above (they determine whether certain fields/tables exist at all). I'll flag exactly which schema elements are affected when we get there.
2. **RBAC/Authorization Matrix** — concrete mapping of capability keys to routes/policies, expanding Section 6.
3. **Non-Functional Deep-Dive** — caching key structure, audit log schema, backup/DR runbook.
4. **Pest Testing Strategy** — test plan structure, with explicit focus on formula verification and policy/middleware coverage.
5. **CI/CD & Deployment Runbook** — concrete pipeline YAML structure (described, not written) and Cloud Run configuration decisions (min instances, queue worker separation, migration execution strategy).

---
*This document supersedes any conflicting statements in PRD v1.1 Sections 8 and 9 where explicitly noted above. All other PRD content remains authoritative unless resolved here.*
