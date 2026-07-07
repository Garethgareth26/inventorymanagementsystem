# Software Delivery Plan
## Sistem Inventori CV Akuna — Implementation Roadmap

| Info | Keterangan |
|---|---|
| Versi Dokumen | 1.0 |
| Status | Planning phase output — frozen artifacts referenced, no application code produced |
| Basis (frozen) | PRD, SAD, Domain Analysis Report, Domain Model Revision (BOM & Production), Excel Business Process Analysis, Database Design Document, ERD/DBML, UI Specification (incl. RBAC/Screen/UI-States addendum) |
| Role of this document | Technical Lead's implementation handbook — the single sequencing authority for build order, sprints, checklists, and deployment |
| Confirmed stack (SAD ADRs) | Laravel 12 / PHP 8.4, Livewire 3, Supabase PostgreSQL, Upstash Redis (session/cache/queue), Supabase Storage, barryvdh/laravel-dompdf, ApexCharts, Google Cloud Run + Cloud Scheduler |

This plan does not revisit any decision already frozen in the documents listed above. Where a sprint touches a business rule (e.g., the production hard-block, the atomic two-mutation write, single-active-parameter-set per ADR-004), it cites the source rather than re-deriving it — implementers should treat a citation like "(Domain Model Revision §4.2)" as the actual specification, and this plan as the sequencing/checklist layer on top of it.

---

## 1. Development Strategy

### 1.1 Methodology

**Iterative, milestone-gated delivery inside a single modular monolith**, not a big-bang build. Each milestone (Section 2) produces a demonstrably working vertical slice — a milestone isn't "done" until a real user flow works end-to-end (UI → Livewire → Policy → Service → DB), not just until models and migrations exist. This matches SAD ADR-001's modular-monolith structure: modules are organized by domain (Suppliers, Raw Materials, Production, etc.), each owning its own models/policies/Livewire components/services, communicating only through service classes — so the delivery plan can build and demo one module at a time without the others being stubbed out with fakes.

Sprints are short (Section 3 uses 3–5 working-day sprints) because this is a single small team working AI-assisted (Section 10) — long sprints hide integration problems that AI-assisted development actually surfaces quickly if checked often. A short sprint with a hard demo checkpoint catches a wrong assumption (e.g., a misread FK direction) before three more sprints get built on top of it.

### 1.2 Implementation Philosophy

- **Data integrity before UI polish.** Every module's Service-layer + DB-transaction logic is implemented and tested before its Livewire component is wired to real data. A pretty screen backed by a service that doesn't enforce the negative-stock block or the production atomicity requirement is worse than no screen, because it invites trusting numbers that can silently corrupt.
- **The ledger (`mutasi_stok`) is the spine of the system**, not an audit afterthought (Domain Model Revision §3.2–3.3). Every module that touches stock — Procurement receipt, Production, Stock Adjustment — is built to write through one shared `StockMutationService`, never directly to `bahan_baku.stok_saat_ini` or `finished_goods.stok_saat_ini`. This is enforced from Sprint 1 of Core Inventory onward, specifically so Procurement and Production don't each grow their own competing stock-update logic.
- **Build the calculation engine on real transactional data, not on the historical Excel estimation method.** The Domain Model Revision (§3.3) retired the "5 real / 5 estimated" sales-percentage technique — implementers must resist the shortcut of re-implementing that Excel logic because it's already written down; `Pemakaian Bulanan` is computed by aggregating `mutasi_stok` "keluar" rows, full stop.
- **Assumed-but-unconfirmed inputs are configuration, not literals** (UI Spec §0.3): Z-factor, ABC thresholds, order cost, holding cost % are seeded defaults in a Settings-backed config table, editable by Owner, read by the Calculation Engine at runtime — never hardcoded in a formula class.

### 1.3 Dependency Strategy

Strict bottom-up build order (expanded in Section 4): a module is not started until every module it depends on is functionally complete (migrated, policy-guarded, service-tested) — not just scaffolded. Concretely, Production is not started until BOM and Raw Materials/Finished Goods are done, because Production's core feature (BOM explosion) has no meaning without a working BOM editor and real stock figures to explode against. This is stated explicitly because AI-assisted development makes it tempting to generate a Production module's UI in isolation from a prompt — the dependency rule exists specifically to prevent that shortcut from producing a screen with nothing real underneath it.

### 1.4 Risk Management

- **Sequence the highest-uncertainty work earliest**, not latest. The atomic two-mutation write (Domain Model Revision §4.2) and the negative-stock hard block are the two riskiest pieces of business logic in the whole system — both land in Milestone 3 (Production), deliberately before Reports or Dashboards, so there's maximum runway to catch a transaction-boundary bug.
- **Every "Pending Decision" flagged in the frozen documents is carried into this plan as an explicit sprint-level risk**, not silently resolved by the developer mid-build (Section 11 Risk Register enumerates each one with its assumed default, per Domain Model Revision §5 and UI Spec §19).
- **No sprint ships a mutating feature without its Policy/Gate test written in the same sprint** — permission logic is the second-highest-risk area (two roles, but with Owner's narrow administrative-write exception per UI Spec §0.2), and retrofitting authorization tests after the fact is how permission bugs survive to production.

### 1.5 Branching Strategy

- `main` — always deployable; protected, no direct pushes.
- `develop` — integration branch for the current milestone; merges from feature branches after review.
- `feature/{module}-{short-description}` (e.g., `feature/production-bom-explosion`) — one feature branch per sprint deliverable, branched from `develop`, merged back via PR.
- `hotfix/{description}` — branched from `main` directly, only for a production defect that can't wait for the next milestone merge.
- Tag a release (`v0.1.0`, `v0.2.0`, ...) at the end of every milestone, not every sprint — milestone boundaries are the meaningful "shippable" checkpoints (Section 2), so tags should mean something to a non-technical stakeholder glancing at the tag list.

### 1.6 Code Review Strategy

- Every PR requires at least one review before merging to `develop`, even on a small/AI-assisted team — the review is specifically checking three things an AI assistant is prone to get subtly wrong in this project: (1) does this write go through the shared `StockMutationService` rather than touching `stok_saat_ini` directly, (2) is the Policy check present on both the Livewire action *and* the underlying route/middleware (SAD §6 point 4's "disable in UI and block in backend" is a review checklist item, not just a design note), (3) does a stock-affecting action wrap its writes in a DB transaction.
- PRs are scoped to one sprint deliverable — a PR that touches both Production and Reports is a sign the dependency boundary (Section 1.3) was violated and should be split.
- Reviewer uses the relevant module's Definition of Done (Section 6) as the literal review checklist, not a subjective read-through.

---

## 2. Project Milestones

### Milestone 0 — Foundation

| | |
|---|---|
| **Purpose** | Stand up the project skeleton, auth, and the RBAC mechanism everything else depends on — nothing else can be built or demoed without this. |
| **Expected Outcome** | A deployed (staging) Laravel 12/Livewire 3 app with working login, role-resolved redirect to an (empty) dashboard shell, and a functioning capability-map + Policy pattern proven on one trivial resource. |
| **Dependencies** | None — this is the entry point. |
| **Completion Criteria** | A Karyawan and an Owner test account can log in, land on their respective (placeholder) dashboard, and a deliberately-attempted unauthorized action returns a real 403 (not just a hidden button) — proving the "block in backend" rule works before any real module is built on top of it. |

### Milestone 1 — Master Data

| | |
|---|---|
| **Purpose** | Build the reference data every other module reads from: Suppliers, Raw Materials, Finished Goods, BOM. |
| **Expected Outcome** | Full CRUD (per role, per UI Spec §20) for all four Master Data screens, including the reference-blocking delete rules (Raw Material blocked by active BOM, Finished Good blocked by BOM/Production Entry). |
| **Dependencies** | Milestone 0 (auth/RBAC). |
| **Completion Criteria** | A user can create a Supplier → a Raw Material sourced from it → a Finished Good → a BOM linking them, entirely through the UI, with every validation and permission rule from UI Spec §7/§20 enforced. |

### Milestone 2 — Core Inventory

| | |
|---|---|
| **Purpose** | Build the `mutasi_stok` ledger and the one shared service every stock-affecting module will call. |
| **Expected Outcome** | Stock Adjustment (manual mutation) working end-to-end with the negative-stock hard block; Inventory Movements ledger (immutable, filterable); Raw Material/Finished Goods Stock Overview screens. |
| **Dependencies** | Milestone 1 (needs real Raw Materials/Finished Goods to mutate). |
| **Completion Criteria** | A manual stock-out that would push a material negative is blocked with the exact error UI Spec §6.9 specifies; every mutation, regardless of origin, is visible and correctly attributed in Inventory Movements. |

### Milestone 3 — Purchasing & Production

| | |
|---|---|
| **Purpose** | Build the two transactional modules that generate system-originated mutations: Procurement (PO lifecycle) and Production (BOM explosion + atomic deduction). |
| **Expected Outcome** | Full PO lifecycle (Menunggu → Dalam Proses → Diterima) with auto-generated stock-in on receipt; Production Entry with live BOM explosion preview, hard-blocked on insufficient stock, and the atomic two-mutation write on submit. |
| **Dependencies** | Milestone 2 (both modules write through `StockMutationService`). |
| **Completion Criteria** | Receiving a PO visibly increases stock in the same view (Section 3.2 point 6); submitting a Production Entry either succeeds and writes exactly two linked `mutasi_stok` rows atomically, or is blocked pre-submit with no partial write possible — verified by a deliberately-forced mid-transaction failure test. |

### Milestone 4 — Calculation Engine

| | |
|---|---|
| **Purpose** | Build EOQ / Safety Stock / Reorder Point / ABC Analysis, sourced from real `mutasi_stok` aggregation, plus the Settings screen that holds their configurable defaults. |
| **Expected Outcome** | Overview + Simulation screens for all three formulas, Apply (Employee-only, audit-logged, single active parameter set per ADR-004), and a fully computed ABC Analysis Report. |
| **Dependencies** | Milestone 3 — the formulas need real production/procurement-driven usage history to aggregate; building this milestone earlier would force it onto fake or estimated data, which is exactly the pattern the Domain Model Revision retired. |
| **Completion Criteria** | `D` for any material matches a manual aggregation of its `mutasi_stok` "keluar" rows; EOQ/SS/ROP simulation results match the confirmed formulas (UI Spec §0.4) to the cent/unit; Apply overwrites the single active parameter set and logs old/new/actor/timestamp. |

### Milestone 5 — Dashboards & Alerting

| | |
|---|---|
| **Purpose** | Build the two role-specific dashboards and the polling-based critical-stock alert system. |
| **Expected Outcome** | Owner and Employee dashboards per UI Spec §5, live (polling) critical-stock table and bell badge, "Buat PO Darurat" quick action wired to a pre-filled emergency PO form. |
| **Dependencies** | Milestones 1–4 (dashboards are read-aggregations over everything built so far). |
| **Completion Criteria** | Dropping a material below its ROP via a test mutation causes the bell badge and critical-stock table to update within one polling interval, without a page refresh. |

### Milestone 6 — Reports

| | |
|---|---|
| **Purpose** | Build the three PDF report types, async generation, and Report History. |
| **Expected Outcome** | Report Generator (shared W for both roles) producing all three report types via a queued dompdf job, uploaded to Supabase Storage, surfaced via signed download links. |
| **Dependencies** | Milestones 1–4 (reports query Master Data, Inventory, and Calculation Engine data). |
| **Completion Criteria** | Generating a report for a large date range doesn't block the UI (Section 3.7 point 3); the resulting file is retrievable only via a signed URL, never a raw storage path. |

### Milestone 7 — Administration & Hardening

| | |
|---|---|
| **Purpose** | Build User Management and the remaining Settings screens; close out cross-cutting hardening (accessibility, responsive rules, full regression pass). |
| **Expected Outcome** | Owner-only User CRUD (soft-deactivate), Company Profile/Calculation Parameters/Notification Preferences screens; full accessibility and responsive audit against UI Spec §13–14. |
| **Dependencies** | All prior milestones (Settings' Calculation Parameters feeds defaults into Milestone 4's already-built simulation screens — this milestone retrofits configurability, it doesn't gate the engine's initial build). |
| **Completion Criteria** | Deactivating a user preserves all their historical `dicatat_oleh` references; every screen passes the accessibility checklist (Section 6). |

### Milestone 8 — Deployment

| | |
|---|---|
| **Purpose** | Take the system from staging to production on Cloud Run, with monitoring and a documented rollback path. |
| **Expected Outcome** | Production deployment live, scheduled jobs (critical-stock check, ABC recompute) running via Cloud Scheduler, backups confirmed, client sign-off obtained on the pending decisions carried through this whole plan (Section 11). |
| **Dependencies** | Milestone 7 (feature-complete + hardened). |
| **Completion Criteria** | Per Section 9's deployment roadmap. |

---

## 3. Sprint Breakdown

Sprints are sized for a small AI-assisted team (assume 1–2 developers operating Claude Code/Antigravity as described in Section 10). "Estimated Complexity" is Low/Medium/High/Very High, not story points, since velocity isn't yet established for this team; "Suggested Duration" assumes a 5-day working week.

### Milestone 0 — Foundation

**Sprint 0.1 — Project Scaffold & CI**
- *Objectives:* Laravel 12/PHP 8.4 project initialized; Docker image (PHP-FPM + Nginx) builds locally; GitHub Actions pipeline runs lint + test on every push.
- *Deliverables:* Repo with `main`/`develop` branches, base `.env.example`, working CI pipeline (green on an empty test suite).
- *Dependencies:* None.
- *Acceptance Criteria:* A PR triggers CI and blocks merge on failure.
- *Estimated Complexity:* Low
- *Suggested Duration:* 1–2 days

**Sprint 0.2 — Auth & Roles**
- *Objectives:* `users`/`roles` tables and migrations; login/logout; session driver set to Redis (Upstash) per SAD §7.1.
- *Deliverables:* Working Login screen (UI Spec §4.1/§6, Archetype G states from the UI Spec addendum §22.8) with role-resolved redirect.
- *Dependencies:* Sprint 0.1.
- *Acceptance Criteria:* Two seeded accounts (one per role) can log in and land on distinct (placeholder) dashboards.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 0.3 — Capability Map & Policy Pattern**
- *Objectives:* `config/capabilities.php` scaffolded; one real Policy class (e.g., `SupplierPolicy`) built end-to-end against a throwaway resource to prove the pattern before every other module copies it.
- *Deliverables:* Middleware enforcing capability checks at the route level; a documented example other developers/AI assistants copy for every subsequent Policy.
- *Dependencies:* Sprint 0.2.
- *Acceptance Criteria:* An Employee-only route hit directly by an Owner-authenticated session returns 403, verified by an automated test — not just a manual click-through.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

### Milestone 1 — Master Data

**Sprint 1.1 — Suppliers**
- *Objectives:* Full CRUD per UI Spec §4.3/§7.1/§20.
- *Deliverables:* Supplier List + Detail/Create/Edit, `SupplierPolicy` wired to real capability keys.
- *Dependencies:* Milestone 0.
- *Acceptance Criteria:* Employee can create/edit/delete; Owner sees read-only (write controls absent, not disabled, per UI Spec §1.2); delete blocked if a Raw Material still references this supplier.
- *Estimated Complexity:* Low
- *Suggested Duration:* 2 days

**Sprint 1.2 — Raw Materials**
- *Objectives:* Full CRUD per UI Spec §4.3/§7.2/§20, including the `stok_saat_ini` create-only/read-only-on-edit rule.
- *Deliverables:* Raw Material List + Detail/Create/Edit.
- *Dependencies:* Sprint 1.1 (Supplier picker).
- *Acceptance Criteria:* Delete blocked if referenced by an active BOM line (this rule is asserted by test even though BOM doesn't exist yet — assert against a manually-inserted `bom` row).
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 1.3 — Finished Goods**
- *Objectives:* Full CRUD per UI Spec §4.3/§7.3/§20.
- *Deliverables:* Finished Goods List + Detail/Create/Edit.
- *Dependencies:* Sprint 1.1–1.2 in parallel is fine (no direct FK dependency between Raw Materials and Finished Goods).
- *Acceptance Criteria:* Delete blocked if referenced by any BOM line or Production Entry (again, assert against manually-inserted rows pre-BOM-module).
- *Estimated Complexity:* Low
- *Suggested Duration:* 2 days

**Sprint 1.4 — Bill of Materials (BOM)**
- *Objectives:* BOM List + Editor per UI Spec §4.3/§7.4/§20, including the repeatable line-item editor, duplicate-material-in-same-BOM validation, and the informational (non-blocking) remove-line warning.
- *Deliverables:* Working BOM Editor that can compose a real recipe from existing Raw Materials against a Finished Good.
- *Dependencies:* Sprints 1.2 and 1.3.
- *Acceptance Criteria:* A finished good with a saved BOM shows correctly on both Raw Material Detail's "used in these BOMs" list and Finished Goods Detail's BOM summary.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 3 days

### Milestone 2 — Core Inventory

**Sprint 2.1 — StockMutationService & Ledger**
- *Objectives:* Build the shared `StockMutationService` (the single write path for all stock changes, per Domain Model Revision §3.2 and §4.1) and the immutable Inventory Movements ledger read screen.
- *Deliverables:* `mutasi_stok` migration with `sumber`/`po_id`/`production_entry_id` columns and the DB-level CHECK constraints from Database Design Document §4.8; Inventory Movements list screen (filterable, no write actions).
- *Dependencies:* Milestone 1 (mutations reference real materials/finished goods).
- *Acceptance Criteria:* The service, called directly (no UI yet), correctly writes a mutation and updates the running balance; the ledger screen renders it correctly with the right `sumber` badge.
- *Estimated Complexity:* High — this is the highest-leverage class in the system; get its transaction boundaries right here, not later.
- *Suggested Duration:* 3 days

**Sprint 2.2 — Stock Adjustment (Manual Mutation)**
- *Objectives:* Build the Stock Adjustment create form on top of `StockMutationService`, including the negative-stock hard block and the large-quantity soft-warning dialog.
- *Deliverables:* Stock Adjustment screen per UI Spec §4.6/§6.9/§7.7, Employee-only (Owner cannot reach the form, per UI Spec §0.2).
- *Dependencies:* Sprint 2.1.
- *Acceptance Criteria:* Attempting a "keluar" mutation larger than current stock is hard-blocked with the exact message in UI Spec §7.7; Owner navigating to the create URL directly gets a 403.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

**Sprint 2.3 — Stock Overview Screens**
- *Objectives:* Raw Material Stock Overview and Finished Goods Stock Overview (read views over existing master data + running balance).
- *Deliverables:* Both overview screens per UI Spec §4.6.
- *Dependencies:* Sprint 2.1.
- *Acceptance Criteria:* Figures match the ledger exactly for a manually-seeded set of test mutations.
- *Estimated Complexity:* Low
- *Suggested Duration:* 1–2 days

### Milestone 3 — Purchasing & Production

**Sprint 3.1 — Purchase Order Lifecycle (Data + Status Flow)**
- *Objectives:* `pesanan_pembelian` model/migration (flat, header-less per Domain Analysis Report §3.4), PO List/Detail, Create (Rutin), status advance Menunggu → Dalam Proses.
- *Deliverables:* PO List/Detail/Create(Rutin) screens per UI Spec §4.4/§6.6/§7.5, `ProcurementPolicy`.
- *Dependencies:* Milestone 1 (material/supplier), Milestone 2 (`StockMutationService`, not yet invoked).
- *Acceptance Criteria:* A PO can be created and its status advanced up to (not including) Diterima.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 3 days

**Sprint 3.2 — PO Receipt & Emergency Flow**
- *Objectives:* Wire the Diterima transition to `StockMutationService` (auto-generates a `sumber = po_penerimaan` stock-in, synchronously, per Section 3.2 point 6); build Create PO (Darurat) with emergency supplier/price/lead-time defaults and urgent badge.
- *Deliverables:* Full PO module complete; the critical-stock-alert-triggered pre-filled emergency PO path (this half of Sprint 3.6's dependency is satisfied here, the alert-triggering half lands in Milestone 5).
- *Dependencies:* Sprint 3.1.
- *Acceptance Criteria:* Marking a PO Diterima increases stock visibly in the same view, with the resulting mutation traceable back to the PO from both directions (PO Detail → mutation, mutation → PO).
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 3.3 — Production Entry: BOM Explosion Preview**
- *Objectives:* Build the live BOM explosion preview (required/available/resulting stock per line) as its own component, independent of submit logic — get the read-side calculation right and tested before wiring the write.
- *Deliverables:* Create Production Entry screen showing a correct, live-updating preview table per UI Spec §17.6/§6.7, including the insufficient-stock red-highlight state.
- *Dependencies:* Milestone 1 (BOM), Milestone 2 (current stock figures).
- *Acceptance Criteria:* Preview quantities match `qty_per_unit × jumlah_diproduksi` exactly for a manually-verified test case; Submit is disabled whenever any line is short, with no way to force-enable it client-side.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 3.4 — Production Entry: Atomic Submit**
- *Objectives:* Wire Submit to a DB transaction that writes the Production Entry plus exactly two linked `mutasi_stok` rows per BOM line-set (raw-material-out × N lines, finished-good-in × 1) atomically, per Domain Model Revision §4.2 and `production.record` capability (§4.3).
- *Deliverables:* Fully working Create Production Entry; single success toast covering both effects (Section 3.3 point 5); server-side re-validation of sufficiency (never trust the client-side block alone).
- *Dependencies:* Sprint 3.3.
- *Acceptance Criteria:* A deliberately-forced failure partway through the transaction (e.g., killing the DB connection mid-write in a test) leaves zero mutation rows written — not one, not a partial set. This is the single most important test in the whole project; it directly verifies the addendum's non-negotiable atomicity requirement.
- *Estimated Complexity:* Very High
- *Suggested Duration:* 3–4 days

### Milestone 4 — Calculation Engine

**Sprint 4.1 — Usage Aggregation & ABC Analysis**
- *Objectives:* Build the `mutasi_stok` "keluar" aggregation query (`Pemakaian Bulanan` → `D`), cached per SAD §7.2; build ABC Analysis (view-only, ranked by cumulative usage value, thresholds read from Settings-backed config, defaulting to 80/95 per UI Spec §0.3).
- *Deliverables:* ABC Analysis Report screen; the aggregation query, unit-tested against a known seeded mutation set.
- *Dependencies:* Milestone 3 (needs real production/procurement-driven mutations to aggregate against).
- *Acceptance Criteria:* `D` for a test material matches a manual sum of its "keluar" rows over the configured historical window, to the unit.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 4.2 — EOQ Overview & Simulation**
- *Objectives:* Build EOQ formula (`√(2DS/H)`), Overview + Simulation screens, Apply (Employee-only, ADR-004 single-active-set + audit log).
- *Deliverables:* EOQ module complete per UI Spec §4.7/§6.10/§7.8.
- *Dependencies:* Sprint 4.1.
- *Acceptance Criteria:* Simulated EOQ matches manual formula calculation for a test material; Apply overwrites the active value and writes an `audit_logs` row with old/new/actor/timestamp; Owner sees Apply disabled with tooltip, not hidden (this is the one within-page disabled state in the system, per UI Spec addendum §22.5).
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

**Sprint 4.3 — Safety Stock & Reorder Point**
- *Objectives:* Build SD Bulanan/SD Harian/SS/ROP formulas and their Overview + Simulation screens, same Apply pattern as EOQ.
- *Deliverables:* Safety Stock + Reorder Point modules complete.
- *Dependencies:* Sprint 4.2 (reuses the same Simulation component pattern).
- *Acceptance Criteria:* ROP status badges (OK/Near/Critical) compute correctly against seeded stock figures; formulas match UI Spec §0.4 exactly.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

### Milestone 5 — Dashboards & Alerting

**Sprint 5.1 — Critical Stock Job & Cache**
- *Objectives:* Build the scheduled job (Cloud Scheduler → Laravel scheduler) comparing every material's stock vs. ROP, writing the critical set to a Redis cache key, per SAD §7.4/§8.4.
- *Deliverables:* Working scheduled job, testable by manually invoking it against seeded data.
- *Dependencies:* Milestone 4 (needs real ROP values).
- *Acceptance Criteria:* Dropping a material below ROP via a test mutation causes it to appear in the cache key after the next job run.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

**Sprint 5.2 — Dashboards**
- *Objectives:* Build Owner Dashboard (4 KPIs, ABC donut, top-5 cost bar, critical stock table, activity feed, upcoming reorders) and Employee Dashboard (3 KPIs, critical stock + quick action, 3 quick-create buttons, own activity feed), per UI Spec §5/§17.2.
- *Deliverables:* Both dashboards, `wire:poll` wired to Sprint 5.1's cache key.
- *Dependencies:* Sprint 5.1, Milestones 1–4 (aggregates over everything).
- *Acceptance Criteria:* Bell badge and critical-stock table update within one polling interval of a stock change, no manual refresh; Owner dashboard has zero create/edit controls anywhere on the page.
- *Estimated Complexity:* High
- *Suggested Duration:* 3–4 days

**Sprint 5.3 — Emergency PO Quick Action**
- *Objectives:* Wire the "Buat PO Darurat" quick action from the critical-stock table to a pre-filled Emergency PO form (completing the loop opened in Sprint 3.2).
- *Deliverables:* Full Section 3.6 stock-alert flow working end-to-end.
- *Dependencies:* Sprint 5.2, Sprint 3.2.
- *Acceptance Criteria:* Clicking the quick action on a critical material lands on Create PO (Darurat) pre-filled with that material and its estimated shortfall.
- *Estimated Complexity:* Low
- *Suggested Duration:* 1 day

### Milestone 6 — Reports

**Sprint 6.1 — Report Generation Pipeline**
- *Objectives:* Build the async report-generation job (query → dompdf render → Supabase Storage upload → signed URL), queued via Upstash Redis, per SAD §7.3/§7.5/§8.5.
- *Deliverables:* Report Generator screen (3 report-type cards, date range, progress indicator) for one report type end-to-end first (e.g., Monthly Movement), proving the pipeline before replicating it.
- *Dependencies:* Milestones 1–4 (source data for reports).
- *Acceptance Criteria:* Generating a report doesn't block the UI; the resulting PDF is retrievable only via a signed, temporary URL.
- *Estimated Complexity:* High
- *Suggested Duration:* 3 days

**Sprint 6.2 — Remaining Report Types & History**
- *Objectives:* Replicate the pipeline for Warehouse Asset Valuation and Supplier Performance; build Report History.
- *Deliverables:* All three report types working; Report History list with download links.
- *Dependencies:* Sprint 6.1.
- *Acceptance Criteria:* All three report types generate correct data against seeded test scenarios; both roles can generate and download all three (PRD §6.6, the one fully shared write action).
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

### Milestone 7 — Administration & Hardening

**Sprint 7.1 — User Management**
- *Objectives:* User List/Detail/Create/Edit, Owner-only, soft-deactivate preserving `dicatat_oleh` referential integrity.
- *Deliverables:* Full User Management module per UI Spec §4.9/§6.15/§7.9.
- *Dependencies:* Milestone 0 (roles table).
- *Estimated Complexity:* Low
- *Suggested Duration:* 2 days
- *Acceptance Criteria:* Deactivating a user who has historical mutations/POs/production entries doesn't break any of those records' display.

**Sprint 7.2 — Settings**
- *Objectives:* Company Profile & PDF Letterhead, Calculation Parameters (feeding Milestone 4's already-built defaults), Notification Preferences (with the disabled WhatsApp/Email placeholder toggle).
- *Deliverables:* All three Settings sub-screens per UI Spec §4.10/§6.16/§7.10.
- *Dependencies:* Milestone 4 (Calculation Parameters must actually change what Simulation screens pre-fill).
- *Acceptance Criteria:* Changing the Z-factor default in Settings changes the pre-filled value on a *new* Safety Stock simulation (not retroactively on already-applied materials).
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 7.3 — Accessibility & Responsive Audit**
- *Objectives:* Full pass against UI Spec §13 (responsive) and §14 (accessibility) across every screen built so far.
- *Deliverables:* Audit checklist completed, fixes applied.
- *Dependencies:* All prior milestones.
- *Acceptance Criteria:* Keyboard-only navigation reaches every interactive element; WCAG AA contrast verified; no color-only status indicators remain.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2–3 days

**Sprint 7.4 — Full Regression Pass**
- *Objectives:* Run the complete test suite (Section 7) end-to-end; manually walk every Section 3 user flow once, fresh, as if a first-time user.
- *Deliverables:* Signed-off regression report.
- *Dependencies:* All prior sprints.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

### Milestone 8 — Deployment

**Sprint 8.1 — Staging Deployment & CI/CD Finalization**
- *Objectives:* Finalize the CI/CD pipeline (Section 9), deploy to a Cloud Run staging service, confirm Supabase/Upstash/Supabase Storage connections via secrets.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 2 days

**Sprint 8.2 — Production Deployment & Cutover**
- *Objectives:* Execute Section 9's roadmap, confirm scheduled jobs are live, obtain client sign-off on Section 11's pending decisions, go live.
- *Estimated Complexity:* Medium
- *Suggested Duration:* 1–2 days (plus a monitoring window)

---

## 4. Module Dependency Graph

```
Authentication
   │  (login, session; nothing works without an identified user)
   ▼
Authorization (RBAC / Capability Map)
   │  (every subsequent module's Policy depends on this pattern existing and being proven once)
   ▼
Master Data (Suppliers → Raw Materials → Finished Goods → BOM)
   │  (Production and Purchasing both reference these; BOM specifically requires
   │   Raw Materials AND Finished Goods to already exist)
   ▼
Core Inventory (StockMutationService + Ledger + Stock Adjustment)
   │  (this is the shared write path — Purchasing and Production both call it,
   │   so it must exist and be correct before either of them can be built,
   │   or they'd each invent their own competing stock-update logic)
   ▼
Purchasing  ──┐
              ├──▶  both feed real mutations into mutasi_stok
Production  ──┘     (Production specifically also depends on BOM from Master Data)
   ▼
Calculation Engine (EOQ / Safety Stock / ROP / ABC)
   │  (needs real, transactionally-generated usage history to aggregate against —
   │   building this before Purchasing/Production exist would force it onto fake
   │   or manually-seeded data, reproducing the exact estimation problem the
   │   Domain Model Revision retired)
   ▼
Dashboard & Alerting
   │  (a read-aggregation over every module above; the critical-stock alert
   │   specifically needs real ROP values from the Calculation Engine)
   ▼
Reports
   │  (queries Master Data + Inventory + Calculation Engine data; could technically
   │   move earlier, but is sequenced last among features since it has no other
   │   module depending on it — nothing is blocked by Reports arriving late)
   ▼
Administration (User Management, Settings) & Hardening
   │  (Settings' Calculation Parameters retrofits configurability into a Calculation
   │   Engine that must already exist and work with seeded defaults; User Management
   │   has no functional dependency on anything else, so it's scheduled by priority,
   │   not by a technical blocker)
   ▼
Deployment
```

**Why this order, specifically:**

1. **Auth/RBAC first** because every Policy class in every later module assumes the capability-map pattern already exists and is proven — building it once, correctly, in Milestone 0 means every subsequent module copies a working pattern instead of re-deriving one.
2. **Master Data before anything transactional** because Purchasing, Production, and the Calculation Engine all reference Suppliers/Raw Materials/Finished Goods/BOM by foreign key — there's nothing to purchase, produce, or calculate against without them existing first.
3. **Core Inventory (the ledger service) before Purchasing and Production** — this is the order most likely to be gotten wrong under time pressure, so it's stated explicitly: if Purchasing were built first and wrote its own stock-increment logic, Production would either duplicate that logic or, worse, bypass it, and the system would end up with two disagreeing sources of truth for "how does stock change." Building the shared service first forces both consumers to use it.
4. **Purchasing and Production before the Calculation Engine** because `D` (Kebutuhan Tahunan) is now defined as an aggregation over real `mutasi_stok` "keluar" records (Domain Model Revision §3.3) — there is no real usage to aggregate until real production/procurement events exist. Building the engine earlier would only be possible by feeding it fake data, which is precisely the estimation approach this project deliberately moved away from.
5. **Dashboard/Alerting after the Calculation Engine** because the critical-stock alert compares live stock against ROP, and ROP doesn't exist as a real, computed figure until Milestone 4 is done.
6. **Reports last among functional modules** because no other module depends on Reports existing — it's the one module that is purely a consumer of everything else and blocks nothing downstream, so it's correctly the lowest-priority functional module even though it's user-facing.
7. **Administration/Hardening before Deployment, not interleaved** — User Management and Settings are functionally independent of the operational modules (nothing about Production or Purchasing requires Settings to exist first), so they're scheduled late by priority/risk, not by a technical dependency; hardening (accessibility, regression) has to be last because it audits the whole system, not a module in isolation.
8. **Deployment last, always** — by construction, since it depends on the system being feature-complete and hardened.

---

## 5. Development Checklist

One checklist template, applied per sprint (Section 3). Not every item applies to every sprint (e.g., a read-only screen like ABC Analysis has no "Policy" write-check beyond view) — the template is a superset to check against, not a rigid requirement every box be filled.

| Category | Checklist Items |
|---|---|
| **Database** | Migration written and reviewed; foreign keys and indexes match Database Design Document/DBML exactly; DB-level CHECK constraints added via raw SQL where DBML can't express them (e.g., `mutasi_stok`'s "exactly one of bahan_baku_id/finished_goods_id" rule, Database Design Document §4.8) |
| **Seeders** | Factory-backed seeder for local/staging demo data; at minimum, enough seed data to exercise every UI state in the module (Empty, populated, near-limit) |
| **Models** | Eloquent model with explicit `$fillable`/`$casts`; relationships match the ERD directionality exactly (e.g., one Finished Good → many BOM lines) |
| **Factories** | Model factory covering realistic value ranges (e.g., `qty_per_unit` up to 4 decimals per UI Spec §7.4) |
| **Policies** | One Policy class per module (SAD §6); every ability in UI Spec §20.3's capability table has a corresponding Policy method; Owner/Employee split matches the RBAC matrix exactly, not just "roughly" |
| **Services** | Business logic lives in a Service class, not in the Livewire component or controller; any stock-affecting Service call is wrapped in a DB transaction |
| **Livewire Components** | One component per screen (or logical sub-section); component reflects the exact states cataloged in UI Spec §22 (Initial Loading, Skeleton, Empty, No Results, Validation Error, Success, Permission Denied, Offline/Error, Delete Confirmation, Processing) for its archetype |
| **Validation** | Form Request or Livewire validation rules match UI Spec §7's field-by-field table exactly (required-ness, ranges, uniqueness) |
| **Testing** | Feature test for the happy path; Policy test for both roles; a negative test for every hard-block business rule the module owns |
| **Documentation** | PR description states which UI Spec section(s) and Domain Model/SAD section(s) this sprint implements, so a reviewer (human or AI) can check compliance against the source rather than the code alone |

---

## 6. Definition of Done

Applied per module (not per sprint — a module may span multiple sprints, e.g., Production spans Sprints 3.3–3.4).

| Area | Criteria |
|---|---|
| **Code** | Passes lint/static analysis in CI; no direct writes to `stok_saat_ini` outside `StockMutationService`; no business logic embedded directly in a Livewire component or controller (belongs in a Service) |
| **Testing** | Feature tests cover every user flow in the relevant Section 3 subsection; Policy tests cover both roles for every mutating action; at least one negative/hard-block test where the module owns a hard business rule (production insufficiency, negative stock, PO status-can't-go-backward) |
| **UI** | Every state in UI Spec §22 for the screen's archetype is implemented and manually verified, not just the happy path; permission-denied behavior verified both as a hidden control (UI) and a 403 (backend) |
| **Performance** | High-volume tables (Purchase Orders, Production Entries, Inventory Movements) use server-side pagination as specified (UI Spec §8); the ABC/dashboard KPI aggregation path uses the Redis cache, not a live recomputation per request (SAD §7.2) |
| **Security** | Every mutating route is capability-gated via middleware, verified by an automated test attempting the action as the wrong role; CSRF/rate-limiting unchanged from Laravel defaults per SAD §7.6 |
| **Documentation** | Module's README/PR notes cite which frozen-document sections it implements; any deviation from a frozen spec is flagged to the client, not silently resolved |

---

## 7. Testing Strategy

| Test Type | Scope & Approach |
|---|---|
| **Unit Tests** | Pure business-logic classes in isolation — formula classes (EOQ/SS/ROP), the BOM-explosion calculation, the ABC-classification ranking logic — tested against hand-computed expected values, not against the database. |
| **Feature Tests** | Full request-to-response flow through Livewire components — e.g., "Employee submits a valid Production Entry and sees the success toast"; run against a real (test) database, not mocks, since the whole point is verifying the transaction behavior. |
| **Calculation Tests** | A dedicated suite verifying every formula in UI Spec §0.4 against known inputs/outputs, including edge cases: zero historical usage, a lead time of 1 day, a Z-factor at its boundary (0 and 3). This suite is the one most worth over-investing in, since a formula bug here silently mis-prices every reorder decision downstream. |
| **Authorization Tests** | For every capability key in UI Spec §20.3: a test asserting the allowed role succeeds and the disallowed role receives a 403 — run as a matrix, not spot-checked, since this is a 2-role system and the full cross-product is small enough to be exhaustive. |
| **Integration Tests** | Cross-module flows: PO receipt → mutation appears in ledger → stock overview reflects it; Production Entry → two linked mutations → both stock overviews reflect it → next ABC/EOQ recompute picks it up. |
| **UI Tests** | Browser-level tests (e.g., Laravel Dusk or Livewire's testing helpers) for the highest-risk interactive screens: Create Production Entry's live explosion preview and hard block, EOQ/SS/ROP Simulation's live recompute, Stock Adjustment's negative-stock block. |
| **Regression Tests** | The full suite re-run at the end of every milestone (not just before deployment) — catching a Milestone 2 regression during Milestone 5 work is far cheaper than catching it in Sprint 7.4's regression pass. |
| **Deployment Verification** | Post-deploy smoke test hitting: login, one read-only list screen, one write action, one scheduled-job-dependent feature (critical-stock badge) — confirming the production environment's Redis/Supabase/Storage wiring actually works, not just that the container started. |

---

## 8. Technical Debt Prevention

- **Coding Standards:** PSR-12 formatting, enforced in CI (not by convention alone); one linter config committed to the repo so every AI-assisted contribution is auto-formatted to the same standard regardless of which tool generated it.
- **Service Layer Rules:** A Livewire component or controller may call a Service method; it may never contain a raw Eloquent write to a stock-bearing table itself. This is the single rule most worth automating a static-analysis check for (e.g., a custom PHPStan rule forbidding direct writes to `bahan_baku`/`finished_goods` stock columns outside `StockMutationService`), because it's exactly the shortcut an AI coding assistant will take if asked to "quickly add a stock update" inside a Livewire action.
- **Controller Responsibilities:** Given this is a Livewire-first app (SAD §5.1), most "controllers" are Livewire components — the same Service-layer rule applies to them identically; traditional controllers, where they exist (e.g., signed-URL download endpoints), stay thin and delegate to Services too.
- **Transaction Handling:** Any write touching more than one row across `mutasi_stok`, `bahan_baku`/`finished_goods`, or a source document (`pesanan_pembelian`, `production_entries`) is wrapped in `DB::transaction()` — no exceptions, per the addendum's atomicity requirement (Domain Model Revision §4.2), generalized to Procurement receipt as well even though that addendum only named Production explicitly.
- **Database Integrity:** DB-level CHECK constraints (Database Design Document §4.8) are added via raw migration SQL, not left as application-layer-only validation — a direct DB write (a future script, a manual fix, a different service) should still be unable to violate them.
- **Performance:** Redis caching is applied wherever SAD §7.2 specifies it (ABC classification, dashboard KPIs) from the first implementation, not added retroactively once a screen is observed to be slow — retrofitting caching into a screen already built around live queries is a common source of subtle staleness bugs.
- **Future Maintainability:** Adding a role (e.g., a future "Supervisor," per SAD §6's stated extensibility goal) should require only a new `roles` row and new capability-map entries — if any sprint's implementation makes this untrue (e.g., a hardcoded `if ($user->role === 'owner')` slips in anywhere), that's a code-review-blocking defect, not a style nitpick.

---

## 9. Deployment Roadmap

| Stage | Activities |
|---|---|
| **Development** | Local development against a Supabase dev project + local or dev-tier Upstash Redis; feature branches merge to `develop` after review (Section 1.5–1.6). |
| **Testing** | Full automated suite (Section 7) runs in CI on every PR to `develop` and `main`; no merge without a green build. |
| **Staging** | `develop` deploys automatically to a Cloud Run staging service on merge; staging uses its own Supabase project/Upstash instance (never shares data with production), so demo/test data can be freely reset. |
| **Production** | `main` deploys to the production Cloud Run service only on an explicit tagged release (Section 1.5); this is a deliberate gate, not automatic-on-merge, given the financial/stock-integrity stakes of this system. |
| **Database Migration** | Migrations run as a release step before traffic is routed to the new revision (SAD §8.6) — exact mechanism (a dedicated migration job/command) to be finalized in a follow-up CI/CD runbook, per SAD's own flagged open item. |
| **Environment Variables** | Supabase connection string, Upstash credentials, Supabase Storage keys injected via Cloud Run's secret manager integration — never committed to the repository, per SAD §8.6. |
| **Rollback Strategy** | Cloud Run's revision-based deployment allows immediate traffic rollback to the prior revision if a deployment misbehaves; because migrations run as a release step, any migration in a rolled-back release must be additive/backward-compatible (never a destructive column drop in the same release as the feature that needs it) so that rolling back the app revision doesn't leave the database in a state the prior code can't run against. |

---

## 10. AI Development Workflow

This project is built primarily with AI-assisted tools. Each tool is suited to a different point in the loop described in Section 1 — using the wrong tool for a task (e.g., delegating the atomic-transaction Production sprint to a tool with no persistent codebase context) is itself a project risk, not just an efficiency loss.

| Tool | When to Use | Why |
|---|---|---|
| **Claude (Claude Code / this assistant)** | Primary implementation driver for anything touching business logic, the shared `StockMutationService`, Policy classes, and test-writing — i.e., most of Sections 2–3's sprints. Also the right tool for reviewing a diff against a frozen spec section before it's merged. | Deep, sustained reasoning over the actual frozen documents (PRD/SAD/Domain Analysis/UI Spec) already produced in this project — Claude has read and cross-referenced all of them, so it can catch a diff that quietly contradicts, say, the atomicity requirement or the RBAC matrix, rather than just generating plausible-looking code in isolation. Best suited to single-threaded, high-stakes correctness work: the Production atomic-write sprint, Policy/Gate implementation, and calculation-engine formula code all belong here. |
| **Google Antigravity** | Longer-running, multi-file or multi-surface tasks where autonomous verification adds value — e.g., "implement the full CRUD scaffold for a Master Data module (migration, model, policy, Livewire list/detail/create/edit, and feature tests) and verify it in the browser," or parallel background tasks like generating boilerplate for several structurally-similar screens (the three near-identical EOQ/SS/ROP Simulation screens, Sprint 4.2–4.3, are a good fit) while a developer focuses elsewhere. | Antigravity's agent-first model can plan, write code, run the app, and use its integrated browser to click through and verify a feature autonomously — well suited to the repetitive, well-specified scaffolding this project has a lot of (every Master Data module follows the same CRUD shape; every List screen follows the same Archetype-A state pattern from the UI Spec addendum). It's a better fit for breadth (many similar screens) than for the single highest-risk piece of logic in the system — reserve Claude for that. |
| **ChatGPT** | Fast, disposable lookups that don't need project context: a Tailwind utility-class question, a quick "what's the Laravel 12 syntax for X," a sounding board for a naming decision, or drafting a non-binding first pass at something like a commit message or a stakeholder-facing summary. | Lowest-friction for quick, stateless questions where pulling in the full project context (as Claude or Antigravity would) is overkill; not the right tool for anything that needs to stay consistent with the frozen specs across a whole session, since it has no persistent view of this project's documents unless manually pasted in each time. |

**Workflow shape across a sprint:** plan the sprint against Section 3's spec (any tool can assist drafting this, but a human/Claude should finalize it against the frozen documents) → implement via Claude or Antigravity depending on the risk/repetition profile above → review the diff with Claude against the relevant Definition of Done (Section 6) → merge only after the automated suite (Section 7) is green. No tool's output merges to `develop` without passing the same review bar regardless of which tool produced it — "an AI wrote it" is never itself a reason to relax the checklist in Section 5.

---

## 11. Risk Register

| # | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| 1 | Production Entry's atomic two-mutation write has a transaction-boundary bug, allowing a partial write (stock deducted but finished goods not credited, or vice versa) | Medium | Very High — silently corrupts the ledger that every downstream calculation depends on | Dedicated forced-failure test (Sprint 3.4) that kills the transaction mid-write and asserts zero rows persisted; code review specifically checks for `DB::transaction()` wrapping on this path (Section 1.6, Section 8) |
| 2 | A developer or AI assistant bypasses `StockMutationService` and writes directly to `stok_saat_ini` from a new feature (e.g., a future report or bulk-import tool) | Medium | High — creates a second, disagreeing source of truth for stock | Static-analysis rule forbidding direct writes to stock columns outside the Service (Section 8); Service-layer rule stated explicitly in every module's Definition of Done |
| 3 | UI-level "hide the button" permission enforcement isn't backed by an equivalent server-side Policy/Gate check on every route | Medium | High — a crafted request could perform an action the UI never exposes | Authorization Tests (Section 7) run as an exhaustive role × capability matrix, not spot-checked; Sprint 0.3 proves the pattern once before every module copies it |
| 4 | Pending client decisions (Z-factor, ABC thresholds, order/holding cost, insufficient-stock handling default, BOM versioning default, password-reset flow, PDF letterhead fields, WhatsApp/Email notifications, report retention) get silently hardened into final behavior without explicit sign-off | Medium | Medium — could ship a behavior the client didn't actually confirm | Every one of these is implemented as editable configuration (never a literal), per UI Spec §0.3, and is explicitly listed as a go-live sign-off item in Milestone 8 / Section 9, not assumed closed by virtue of being coded |
| 5 | The Calculation Engine is accidentally built or tested against estimated/fake usage data instead of real `mutasi_stok` aggregation, reproducing the retired sales-percentage technique | Low (given Milestone 4's placement after Milestone 3) | High — would silently reintroduce the exact inaccuracy this project's domain work eliminated | Milestone/dependency ordering (Section 4) deliberately sequences the Calculation Engine after Purchasing/Production are live; Sprint 4.1's acceptance criteria explicitly requires matching a manual ledger aggregation, not just "a plausible number" |
| 6 | Cloud Run's scale-to-zero cold start or Supabase auto-pause degrades perceived performance in a way the client interprets as a defect | Medium | Low–Medium | Documented as an accepted operating characteristic in the deployment runbook and communicated to the client proactively (per SAD §7.6), rather than left to be "discovered" post-launch |
| 7 | Polling-based (not push) alerting is perceived as "not real-time enough" once the client sees it in practice | Medium | Low–Medium | Explicitly communicated as a stated v1 limitation (SAD §7.4) before Milestone 5 demo, with the broadcasting-layer upgrade path (Reverb/Pusher) named as a deliberate future addition, not a bug |
| 8 | Backup/retention policy for the Supabase project tier isn't confirmed before go-live | Medium | High if a real data-loss event occurs post-launch | Explicit Milestone 8 checklist item to confirm the Supabase plan tier's backup retention before production cutover (SAD §7.6 flags this as unresolved) |
| 9 | AI-assisted development produces plausible-looking code that quietly deviates from a frozen spec (e.g., a slightly different validation range, a missing reference-block on delete) | Medium | Medium | Every PR description cites which frozen-document section it implements (Section 5); review checks the diff against that citation, not just against "does this look reasonable" |
| 10 | Queue worker (separate Cloud Run process, per ADR-007) isn't correctly provisioned before Reports/async work goes live, causing generation jobs to silently never run | Low–Medium | Medium | Sprint 6.1's acceptance criteria specifically requires observing a queued job complete in the staging environment, not just that the code compiles; queue worker provisioning is a named Milestone 8 checklist item |

---

## 12. Implementation Order

The single sequence to follow, exactly, from setup to production:

1. Initialize repository, branching model, and CI pipeline (Sprint 0.1).
2. Implement Authentication and session infrastructure (Sprint 0.2).
3. Implement the RBAC capability-map + Policy pattern against one throwaway resource (Sprint 0.3).
4. Build Suppliers (Sprint 1.1).
5. Build Raw Materials (Sprint 1.2).
6. Build Finished Goods (Sprint 1.3).
7. Build Bill of Materials (Sprint 1.4).
8. Build the shared `StockMutationService` and Inventory Movements ledger (Sprint 2.1).
9. Build Stock Adjustment on top of the service (Sprint 2.2).
10. Build the Raw Material / Finished Goods Stock Overview screens (Sprint 2.3).
11. Build Purchase Order data model, List/Detail/Create(Rutin), and status flow up to Dalam Proses (Sprint 3.1).
12. Wire PO receipt (Diterima) to the stock-mutation service and build Create PO (Darurat) (Sprint 3.2).
13. Build the Production Entry BOM-explosion preview, read-only first (Sprint 3.3).
14. Wire Production Entry's atomic two-mutation submit, with the forced-failure transaction test (Sprint 3.4).
15. Build usage aggregation and ABC Analysis (Sprint 4.1).
16. Build EOQ Overview/Simulation/Apply (Sprint 4.2).
17. Build Safety Stock and Reorder Point Overview/Simulation/Apply (Sprint 4.3).
18. Build the critical-stock scheduled job and Redis cache key (Sprint 5.1).
19. Build the Owner and Employee Dashboards (Sprint 5.2).
20. Wire the Emergency PO quick action from critical-stock alerts (Sprint 5.3).
21. Build the report-generation pipeline for one report type (Sprint 6.1).
22. Build the remaining two report types and Report History (Sprint 6.2).
23. Build User Management (Sprint 7.1).
24. Build Settings (Company Profile, Calculation Parameters, Notification Preferences) (Sprint 7.2).
25. Conduct the accessibility and responsive audit (Sprint 7.3).
26. Conduct the full regression pass (Sprint 7.4).
27. Deploy to staging and finalize CI/CD (Sprint 8.1).
28. Confirm backups, scheduled jobs, and secrets; obtain client sign-off on the Risk Register's pending decisions (Section 11); deploy to production (Sprint 8.2).

This sequence is the official implementation roadmap for the project. Any deviation (e.g., pulling a later module forward to satisfy a stakeholder demo request) should be evaluated against Section 4's dependency reasoning first — a module pulled out of order is either genuinely independent (safe to reorder) or silently depends on something not yet built (a five-minute check against Section 4 before agreeing to reorder anything is much cheaper than discovering the dependency mid-sprint).
