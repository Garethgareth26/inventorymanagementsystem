# Sprint 2 — Revised Execution Roadmap (v2)
## CV Akuna Inventory Management System

**Status:** Proposed — Awaiting Approval (M-2.2 already merged, unaffected)
**Supersedes:** `implementation_plan.md` v1 (M-2.2 → M-2.22, 21 milestones)
**Source of truth (unchanged):** UI Specification v2, Software Architecture Document, Domain Analysis, PRD, `implementation_plan.md` v1 (as the authoritative scope reference — this document reorganizes it, it does not replace its content)

---

## Why regroup

The v1 plan is correct in scope and sequencing but wrong in granularity for a single-developer execution rhythm. Three concrete problems observed:

1. **Context-switch tax.** M-2.16 → M-2.17 → M-2.18 are the same simulation-page pattern implemented three times as three separate "milestones," each with its own review/commit ceremony. The pattern only needs to be *designed* once.
2. **Artificial milestone boundaries mid-dependency-chain.** `StockMutationService` was introduced inside M-2.9 (Bahan Baku CRUD) but is immediately required again in M-2.12, M-2.13, M-2.14. Treating it as a foundation-layer service (built once, in Group 1) removes the "oh, I need to touch a service I built two milestones ago" pattern.
3. **21 separate review/PR cycles** for a single developer is overhead without a corresponding benefit — code review depth doesn't change whether you group 3 related CRUD screens into one review pass or three.

**What does NOT change:**
- Every file, service, table, and UI page in v1 is still built. Nothing is cut.
- Every acceptance criterion from v1 is preserved verbatim inside its new group.
- Every quality gate (`php artisan test`, `composer analyse`, `vendor/bin/pint --test`, `npm run build`, `migrate:fresh --seed`) still runs, and still must be 100% green before any commit — same standard as M-2.2.
- **Git granularity is unchanged.** A "milestone" in this revised roadmap is a *review/integration checkpoint*, not a commit. Each group below still produces multiple commits — one logical feature per commit, exactly as before. Grouping reduces how many times you stop for a full audit-and-review cycle (6 times instead of 21), not how many times you commit.
- Dependency order is identical to the v1 module dependency graph.

**What changes:**
- `StockMutationService` moves from M-2.9 into Group 1 (Core Domain Services), since Master Data, Operations, and parts of Optimization all depend on it. Building it once alongside `CalculationEngine`/`AuditLogger`/`SystemSettings` — all pure/service-layer, no Livewire — keeps Group 1 conceptually uniform ("backend services day") and removes a forward-dependency surprise from Group 3.
- The 21 granular review cycles become 6 grouped review cycles, each ending in a full audit (identical rigor to the M-2.2 audit already performed) before moving to the next group.

---

## Module Dependency Graph (unchanged shape, regrouped labels)

```
Domain Models (M-2.2) ✅ MERGED
        │
        ▼
┌───────────────────────────────────────────────┐
│ GROUP 1 — Core Domain Services                │
│ Seeders/Factories · CalculationEngine ·        │
│ AuditLogger · SystemSettings ·                 │
│ StockMutationService                           │
└───────────────────────────────────────────────┘
        │
        ├──► GROUP 2 — Dashboard Module (Owner + Employee)
        │
        ├──► GROUP 3 — Master Data Module
        │       Suppliers → Bahan Baku → Finished Goods → BOM Editor
        │
        ├──► GROUP 4 — Operations Module
        │       Purchase Orders → Production Entry → Stock Adjustment → Inventory Movements
        │
        ├──► GROUP 5 — Optimization & Analytics
        │       EOQ → Safety Stock → Reorder Point → ABC Analysis
        │
        └──► GROUP 6 — Reports & Administration
                Report Generator → User Management → System Settings
```

---

## Group 1 — Core Domain Services

*(Consolidates v1: M-2.3 Seeders, M-2.4 CalculationEngine, M-2.5 AuditLogger/SystemSettings, plus `StockMutationService` pulled forward from M-2.9)*

### Objective
Establish every foundational, framework-light service and the realistic sample dataset that every later module depends on — so Groups 2–6 only ever wire UI onto business logic that is already built and unit-tested, never discover a missing service mid-CRUD-screen.

### Scope
- **Domain Seeders & Factories** — one factory + one seeder per model (already scaffolded bare in M-2.2; this group gives them realistic, internally-consistent data), orchestrated by `DomainSeeder` in strict FK order. Volumes per UI Spec: 10 suppliers, ~10 raw materials, ~10 finished goods, ~132 POs, 12 months of mutation history. At least one material per ABC class (A/B/C).
- **CalculationEngine** — pure, framework-agnostic computation service. Zero Eloquent inside.
  - `computeEoq(D, S, H)`, `computeSafetyStock(Z, sdHarian, leadTimeDays)`, `computeRop(D, leadTimeDays, safetyStock)`, `classifyAbc(materials)`, `computeHoldingCost(hargaSatuan, holdingPct)`, `computeAnnualDemand(mutations, windowMonths)`, `computeDailyStdDev(mutations, windowMonths)`
- **AuditLogger** — `AuditLogger::log(User $actor, string $action, Model $subject, mixed $old, mixed $new): void`, writes one row to `audit_logs`. Called from every service in Groups 3–6, never directly from Livewire.
- **SystemSettings** — Redis-backed key-value store with DB fallback; pre-seeded defaults: Z=1.65, ABC thresholds A=80%/B=95%, historical window=12 months, biaya_pesan=75000, biaya_simpan=20%.
- **StockMutationService** *(pulled forward from v1 M-2.9)* — wraps every `mutasi_stok` + `stok_saat_ini` write in `DB::transaction()` + `lockForUpdate()`. This is the single choke point all stock-affecting operations in Groups 3 and 4 pass through.

### Dependencies
M-2.2 (Domain Models) — ✅ already merged.

### Expected files
```
database/factories/*.php                                    [COMPLETE — realistic data, replacing M-2.2 bare scaffolds]
database/seeders/SupplierSeeder.php                          [NEW]
database/seeders/BahanBakuSeeder.php                         [NEW]
database/seeders/InventoryParameterSeeder.php                [NEW]
database/seeders/FinishedGoodSeeder.php                      [NEW]
database/seeders/BomSeeder.php                                [NEW]
database/seeders/PesananPembelianSeeder.php                  [NEW]
database/seeders/ProductionEntrySeeder.php                   [NEW]
database/seeders/MutasiStokSeeder.php                         [NEW]
database/seeders/SystemSettingsSeeder.php                    [NEW]
database/seeders/DomainSeeder.php                             [NEW — orchestrator]
database/seeders/DatabaseSeeder.php                           [MODIFY]
database/migrations/..._create_system_settings_table.php     [NEW]
app/Services/CalculationEngine.php                            [NEW]
app/Services/AuditLogger.php                                  [NEW]
app/Services/SystemSettings.php                               [NEW]
app/Services/StockMutationService.php                         [NEW]
tests/Unit/CalculationEngineTest.php                          [NEW]
tests/Unit/AuditLoggerTest.php                                [NEW]
tests/Unit/SystemSettingsTest.php                             [NEW]
tests/Feature/StockMutationServiceTest.php                    [NEW]
tests/Feature/DomainSeederTest.php                            [NEW]
```

### Services involved
`CalculationEngine`, `AuditLogger`, `SystemSettings`, `StockMutationService` (all four built here; all four consumed by every later group).

### UI pages involved
None — this group is entirely backend/service layer, no Livewire components.

### Database interaction
- New migration: `system_settings` table.
- Seeders populate all 12 existing domain tables.
- `StockMutationService` performs read-modify-write on `bahan_baku.stok_saat_ini` / `finished_goods.stok_saat_ini` guarded by `lockForUpdate()` inside `DB::transaction()`, and inserts into `mutasi_stok`. No schema changes required for this service — the columns already exist.

### Tests required
- `CalculationEngineTest`: EOQ against hand-calculated values, Safety Stock with Z=1.65, ROP formula, ABC classification with 5 known materials, edge cases (D=0, single mutation in window, lead_time=1).
- `AuditLoggerTest`: correct actor/subject/old/new persisted.
- `SystemSettingsTest`: `get()`/`set()` round-trip, DB fallback when cache empty.
- `StockMutationServiceTest`: transaction atomicity (all-or-nothing), `lockForUpdate()` prevents lost updates under concurrent writes, negative-stock rejection at the service level (defense in depth — UI-level enforcement comes later in Group 4).
- `DomainSeederTest` (or a `migrate:fresh --seed` feature test): full seed completes, FK/CHECK constraints satisfied, ABC classes distributed across seeded materials.

### Acceptance criteria
- `php artisan migrate:fresh --seed` completes without errors.
- All unit tests pass within ±0.01 numeric tolerance on formulas.
- `AuditLogger::log()` writes correct rows; `SystemSettings::get('z_factor')` returns `1.65` after seeding and reflects updates on next request.
- `StockMutationService` is the only code path in the entire codebase that writes to `mutasi_stok` or mutates `stok_saat_ini` — verified by there being zero direct `stok_saat_ini` assignment outside this service (checked in code review, not by an automated rule).
- Zero Eloquent/DB imports in `CalculationEngine`.
- PHPStan level 5 (per actual repo config) passes on all new files with no `mixed` returns.

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
php artisan migrate:fresh --seed
```

### Recommended commit sequence
*(4 commits — same granularity as v1, now reviewed together as one integration checkpoint)*
```
feat(seeders): add domain model factories and realistic sample data seeders
feat(services): implement CalculationEngine with EOQ/SS/ROP/ABC formulas and unit tests
feat(services): add AuditLogger and SystemSettings infrastructure
feat(services): implement StockMutationService with atomic transactions and row locking
```

---

## Group 2 — Dashboard Module

*(Consolidates v1: M-2.6 Owner Dashboard, M-2.7 Employee Dashboard)*

### Objective
Deliver both role-specific dashboards as the first Livewire-facing module — this validates the full Livewire + DB + Cache + ApexCharts stack in one pass, since both dashboards share cache and notification infrastructure.

### Scope
**Owner Dashboard** (UI Spec §5.1, §17.2) — read-only, zero write surface:
- KPI row (4 cards): Total bahan baku aktif · Total nilai investasi tahunan (Rp) · Jumlah material kritis · Nilai stok barang jadi
- ABC Donut Chart (ApexCharts), Top-5 Cost Horizontal Bar Chart (ApexCharts)
- Critical Stock Table with `wire:poll.15s`
- Recent Activity Feed (last 15 `audit_log` entries), Upcoming Reorders Panel, notification bell badge (polled)
- No Add/Edit/Delete buttons anywhere

**Employee Dashboard** (UI Spec §5.2, §17.2) — action-focused cockpit:
- KPI row (3 cards): Material kritis hari ini · PO menunggu · Produksi bulan ini
- Critical Stock Table with per-row "Buat PO Darurat" quick action
- Quick Actions Bar: 3 pill buttons → Buat PO Baru, Catat Produksi, Sesuaikan Stok
- Recent Activity Feed (own actions, last 15), shared notification bell infrastructure

### Dependencies
Group 1 (CalculationEngine for KPI aggregates, AuditLogger for activity feed, SystemSettings for thresholds).

### Expected files
```
app/Livewire/Dashboard/OwnerDashboard.php                     [NEW]
app/Livewire/Dashboard/EmployeeDashboard.php                  [NEW]
resources/views/livewire/dashboard/owner-dashboard.blade.php  [NEW]
resources/views/livewire/dashboard/employee-dashboard.blade.php [NEW]
resources/views/dashboard/owner.blade.php                     [MODIFY]
resources/views/dashboard/employee.blade.php                  [MODIFY]
app/Console/Commands/RefreshDashboardCache.php                [NEW]
routes/web.php                                                 [MODIFY]
tests/Feature/Dashboard/OwnerDashboardTest.php                 [NEW]
tests/Feature/Dashboard/EmployeeDashboardTest.php               [NEW]
```

### Services involved
`CalculationEngine` (KPI aggregation), `AuditLogger` (activity feed read), `SystemSettings` (critical-stock thresholds). No new services.

### UI pages involved
Owner Dashboard, Employee Dashboard (both full-page Livewire components per SAD's Livewire-4-full-page-component principle).

### Database interaction
Read-only against `bahan_baku`, `finished_goods`, `mutasi_stok`, `audit_logs`, `pesanan_pembelian`, `production_entries`. No writes from this group.

### Tests required
- All 4 Owner KPIs render correct seeded values.
- ABC donut + Top-5 bar charts receive correctly shaped data.
- Critical stock table polling endpoint returns updated data without full reload.
- No action buttons present in Owner Dashboard markup (regression guard for the "read-only" rule).
- Employee Quick Actions Bar routes resolve correctly.
- Middleware blocks Owner from `/employee/dashboard` and vice versa (403, not redirect-to-login).

### Acceptance criteria
- Both dashboards match Stitch v2 (`owner_dashboard_cv_akuna`, `employee_dashboard_cv_akuna`).
- Owner dashboard has zero write-surface elements.
- Cross-role middleware enforcement verified by feature test, not just manual check.

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
npm run build
```

### Recommended commit sequence
```
feat(dashboard): implement Owner Dashboard with KPI cards, charts, and critical stock polling
feat(dashboard): implement Employee Dashboard with KPIs, quick actions, and activity feed
```

---

## Group 3 — Master Data Module

*(Consolidates v1: M-2.8 Suppliers, M-2.9 Bahan Baku, M-2.10 Finished Goods, M-2.11 BOM Editor — `StockMutationService` already built in Group 1)*

### Objective
Deliver all master-data CRUD screens as one architecturally uniform module: shared CRUD/validation/policy conventions are designed once (with Suppliers) and reused for the remaining three screens, ending with the most complex screen (BOM Editor) once the pattern is proven.

### Scope
**Suppliers** (UI Spec §6.2, §7.1): List (Kode, Nama, Alamat, Kontak, # Bahan Baku Terkait, Aksi), search, client-side pagination 25/page, inline modal create/edit, delete blocked (with linked-material count, not a generic error) if referenced by `bahan_baku`. RBAC: Employee R+W; Owner sees table only (buttons hidden, not disabled).

**Bahan Baku** (UI Spec §6.3, §7.2): List with Kelas ABC badge, filters (ABC class, supplier). Create with editable `stok_saat_ini` → seeds initial `mutasi_stok` (masuk, sumber=manual) via `StockMutationService`. Edit: `stok_saat_ini` read-only with help text. Delete blocked if referenced by active BOM line. Auto-creates `inventory_parameters` row with `SystemSettings` defaults on new material.

**Finished Goods** (UI Spec §6.4, §7.3): List with "BOM Terdefinisi?" badge, filter by BOM status. Create with editable `stok_saat_ini` → `StockMutationService`. Edit: `stok_saat_ini` read-only. Delete blocked if referenced by any BOM line or `ProductionEntry`.

**BOM Editor** (UI Spec §6.5, §7.4): Two-level UI — BOM list per finished good, and a dynamic ingredient-line editor (searchable material picker, qty_per_unit, satuan). Duplicate material in same BOM blocked inline. Remove line requires confirmation only if the material was used in a production entry under this recipe. Save is a single transaction — delete existing lines + insert new lines, all-or-nothing.

### Dependencies
Group 1 (`StockMutationService`, `AuditLogger`, `SystemSettings`). Internal order: Suppliers → Bahan Baku (needs Suppliers) → Finished Goods → BOM Editor (needs Bahan Baku + Finished Goods).

### Expected files
```
app/Livewire/MasterData/Suppliers.php                        [NEW]
app/Livewire/MasterData/BahanBaku.php                          [NEW]
app/Livewire/MasterData/FinishedGoods.php                      [NEW]
app/Livewire/MasterData/BomList.php                             [NEW]
app/Livewire/MasterData/BomEditor.php                           [NEW]
resources/views/livewire/master-data/suppliers.blade.php        [NEW]
resources/views/livewire/master-data/bahan-baku.blade.php       [NEW]
resources/views/livewire/master-data/finished-goods.blade.php   [NEW]
resources/views/livewire/master-data/bom-list.blade.php          [NEW]
resources/views/livewire/master-data/bom-editor.blade.php        [NEW]
routes/web.php                                                   [MODIFY]
tests/Feature/MasterData/SuppliersTest.php                       [NEW]
tests/Feature/MasterData/BahanBakuTest.php                       [NEW]
tests/Feature/MasterData/FinishedGoodsTest.php                   [NEW]
tests/Feature/MasterData/BomEditorTest.php                       [NEW]
```

### Services involved
`StockMutationService` (initial stock seeding on Bahan Baku / Finished Goods create), `AuditLogger` (every create/update/delete), `SystemSettings` (default `inventory_parameters` values).

### UI pages involved
Suppliers list/editor, Bahan Baku list/editor, Finished Goods list/editor, BOM list, BOM editor (Stitch v2: `daftar_supplier_cv_akuna`, `detail_supplier_cv_akuna`, `bahan_baku_cv_akuna`, `barang_jadi_cv_akuna`, `detail_barang_jadi_cv_akuna`, `bom_editor_cv_akuna`, `komposisi_bom_cv_akuna`, `detail_bill_of_materials_cv_akuna`).

### Database interaction
Writes to `suppliers`, `bahan_baku`, `finished_goods`, `inventory_parameters` (auto-create), `bom` (transactional replace-all-lines), plus `mutasi_stok` + `stok_saat_ini` via `StockMutationService` on initial stock entry.

### Tests required
- Kode uniqueness validated inline (blur) and on submit, for both Suppliers and Bahan Baku/Finished Goods where applicable.
- `AuditLogger::log()` called on every create/update/delete across all four screens.
- Create with `stok_saat_ini > 0` generates a `mutasi_stok` row (Bahan Baku and Finished Goods).
- ABC class badge shows "—" when no `inventory_parameters` row exists yet.
- Delete-blocking logic: Supplier blocked by linked `bahan_baku`; Bahan Baku blocked by active BOM line; Finished Good blocked by BOM line or `ProductionEntry`.
- BOM Editor: duplicate material shows inline error (not a failed save); save is fully transactional (partial-save impossible, verified with a forced-failure test); BOM status badge on Finished Goods list updates after save.

### Acceptance criteria
- All four screens match their respective Stitch v2 references.
- RBAC dual-layer (middleware + `$this->authorize()`) enforced on every write action.
- Delete-blocking always explains *why* (count of linked records), never a raw DB constraint error.

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
```

### Recommended commit sequence
```
feat(master-data): implement Suppliers CRUD with RBAC, validation, and audit logging
feat(master-data): implement Bahan Baku CRUD with initial stock seeding via StockMutationService
feat(master-data): implement Finished Goods CRUD
feat(master-data): implement BOM Editor with dynamic ingredient lines and transactional save
```

---

## Group 4 — Operations Module

*(Consolidates v1: M-2.12 Purchase Orders, M-2.13 Production Entry, M-2.14 Stock Adjustment, M-2.15 Inventory Movements)*

### Objective
Deliver every stock-affecting operational screen and the read-only ledger that audits them — the highest-risk module in Sprint 2 from a data-integrity standpoint, since Production Entry alone creates N+1 mutations atomically.

### Scope
**Purchase Orders** (UI Spec §6.6, §7.5, §4.4): List (server-side pagination 25/page; filters: Status, Jenis, Supplier, date range). Create Rutin (defaults from routine supplier/price/EOQ) or Darurat (+20% emergency price, urgent visual). Status machine: Menunggu → Dalam Proses → Diterima (confirmation dialog required). "Diterima" triggers `StockMutationService` → `masuk` mutation (`sumber='po_penerimaan'`, `po_id` set). Cancel only from "Menunggu." PO Detail shows status timeline + linked mutation. RBAC: Employee R+W; Owner R only.

**Production Entry** (UI Spec §6.7, §7.6) — most transactionally critical screen: Create form with Barang Jadi picker (BOM-required), Jumlah Diproduksi, Tanggal. Live BOM explosion preview computed client-side in Alpine.js; server validates on submit only. Submit disabled if any BOM line insufficient; advisory (non-blocking) dialog if any line approaches ROP. Hard server-side block if insufficient stock at submit. Transaction: `ProductionEntry` row + N `mutasi_stok` (bahan_baku keluar) + 1 `mutasi_stok` (finished_good masuk); `lockForUpdate()` on bahan_baku rows in **ascending ID order** (deadlock avoidance). RBAC: Employee W (+R); Owner R only. New service: `ProductionService` owns the BOM explosion transaction logic.

**Stock Adjustment** (UI Spec §6.9, §7.7) — the manual "escape hatch": create-only form (Jenis Item toggle, Item picker, Jenis Mutasi toggle, Jumlah, Keterangan). Keluar cannot produce negative stock (hard server-side block). Advisory dialog if quantity > 3× average monthly movement. `StockMutationService` called with `sumber='manual'`. RBAC: Employee W; page entirely hidden from Owner by middleware.

**Inventory Movements Ledger** (UI Spec §6.8) — read-only `mutasi_stok` audit ledger, the highest-volume table, immutable by design: columns Tanggal/Item(+type badge)/Jenis/Jumlah/Sumber(badge)/Referensi(link)/Dicatat Oleh. Filters: item type, jenis_mutasi, sumber, specific item, date range. Server-side pagination 50/page. CSV export respecting active filters. Identical read-only access for both roles.

### Dependencies
Group 3 (Bahan Baku, Finished Goods, Suppliers, BOM), Group 1 (`StockMutationService`, `CalculationEngine` for ROP advisory). Internal order: PO first (simplest — 1 mutation) → Production (most complex — N+1 mutations) → Stock Adjustment / Inventory Movements (either order after).

### Expected files
```
app/Livewire/Purchasing/PurchaseOrders.php                     [NEW]
app/Livewire/Purchasing/CreatePurchaseOrder.php                 [NEW]
app/Livewire/Purchasing/PurchaseOrderDetail.php                 [NEW]
app/Livewire/Production/ProductionList.php                       [NEW]
app/Livewire/Production/CreateProduction.php                     [NEW]
app/Livewire/Inventory/StockAdjustment.php                       [NEW]
app/Livewire/Inventory/InventoryMovements.php                    [NEW]
app/Services/ProductionService.php                                [NEW]
resources/views/livewire/purchasing/*.blade.php                  [NEW — 3 views]
resources/views/livewire/production/*.blade.php                  [NEW — 2 views]
resources/views/livewire/inventory/stock-adjustment.blade.php     [NEW]
resources/views/livewire/inventory/inventory-movements.blade.php [NEW]
app/Policies/ProcurementPolicy.php                                [COMPLETE]
app/Policies/ProductionPolicy.php                                 [COMPLETE]
app/Policies/StockMutationPolicy.php                              [COMPLETE]
routes/web.php                                                     [MODIFY]
tests/Feature/Purchasing/PurchaseOrdersTest.php                   [NEW]
tests/Feature/Production/ProductionEntryTest.php                  [NEW]
tests/Feature/Production/ProductionEntryConcurrencyTest.php       [NEW — deadlock/locking regression guard]
tests/Feature/Inventory/StockAdjustmentTest.php                    [NEW]
tests/Feature/Inventory/InventoryMovementsTest.php                 [NEW]
```

### Services involved
`StockMutationService` (PO receipt, Stock Adjustment), `ProductionService` (new — BOM explosion), `CalculationEngine` (ROP advisory in Production), `AuditLogger` (all writes).

### UI pages involved
PO list/create/detail, Production list/create, Stock Adjustment, Inventory Movements ledger (Stitch v2: `pesanan_pembelian_cv_akuna`, `detail_pesanan_pembelian_cv_akuna`, `terima_pesanan_cv_akuna`, `catat_produksi_cv_akuna`, `riwayat_produksi_cv_akuna`, `detail_produksi_cv_akuna`, `mutasi_stok_cv_akuna`).

### Database interaction
Writes: `pesanan_pembelian`, `production_entries`, `mutasi_stok` (heaviest write volume in the system), `bahan_baku.stok_saat_ini`, `finished_goods.stok_saat_ini` — all through `StockMutationService`/`ProductionService` transactions with `lockForUpdate()`. Inventory Movements is read-only.

### Tests required
- PO: advancing to Diterima sets `mutasi_stok.sumber='po_penerimaan'` + `po_id`, increments stock; cannot advance status backward (server-validated).
- Production: all N+1 mutations created atomically — a forced mid-transaction failure test proves partial success is impossible; insufficient-stock block enforced at both UI (disabled button, tested via component state) and server (policy check); **concurrency test** simulating two simultaneous production entries claiming the same bahan_baku to verify ascending-ID lock ordering prevents deadlock.
- Stock Adjustment: Keluar rejected server-side even with client validation bypassed; resulting mutation has `sumber='manual'`, `po_id=NULL`, `production_entry_id=NULL`; Owner receives 403 on the route.
- Inventory Movements: all filter combinations return correct result sets; Referensi column links correctly to PO or ProductionEntry; CSV export respects active filters.

### Acceptance criteria
- Every acceptance criterion from v1 M-2.12 through M-2.15 holds individually.
- Zero possibility of negative stock, partial N+1 transactions, or lock-order deadlocks — each backed by an automated test, not just manual verification.

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
```

### Recommended commit sequence
```
feat(purchasing): implement Purchase Orders with status machine and stock receipt integration
feat(production): implement Production Entry with BOM explosion preview and atomic stock deduction
feat(inventory): implement Stock Adjustment with negative-stock protection
feat(inventory): implement Inventory Movements ledger with multi-filter and CSV export
```

---

## Group 5 — Optimization & Analytics

*(Consolidates v1: M-2.16 EOQ, M-2.17 Safety Stock, M-2.18 Reorder Point, M-2.19 ABC Analysis)*

### Objective
Deliver all four simulation/analysis screens as one module — EOQ establishes the overview+simulation+apply interaction pattern once; Safety Stock and Reorder Point reuse it directly; ABC Analysis (a pure report, no simulation) closes the module.

### Scope
**EOQ** (UI Spec §6.10, §7.8): Overview list (Kode, Nama, Kelas ABC, D, S, H, current EOQ, Aksi). Simulation page: S/H editable, D auto-computed, live result, side-by-side old vs. new. Apply (Employee only — disabled with tooltip for Owner): saves to `inventory_parameters`, calls `AuditLogger`, success toast. Reset to Defaults repopulates from `SystemSettings`.

**Safety Stock** (UI Spec §6.11): Same interaction pattern, different inputs — Z-factor (0–3), historical window (1–24 months), lead time, SD Harian. Apply/disabled-for-Owner identical to EOQ.

**Reorder Point** (UI Spec §6.12): Overview with critical-stock status badges (Critical = `stok_saat_ini <= reorder_point`; Near = `stok_saat_ini <= 1.2 × reorder_point`; OK otherwise) — these badges also drive the Dashboard (Group 2) and Upcoming Reorders panel. Critical rows get a "Buat PO Darurat" quick action pre-filled with shortfall quantity, linking into Group 4's PO Create.

**ABC Analysis** (UI Spec §6.13): Fully system-computed, no manual overrides. Donut chart (ApexCharts, A/B/C segments) — clicking a segment filters the table. Table: Kode, Nama, Nilai Pemakaian Tahunan, % Individual, % Kumulatif, Kelas badge. Filter by class, client-side 25/page, sort by % Kumulatif ascending. CSV export. Data from cache (Group 2 infrastructure) with live-query fallback.

### Dependencies
Group 1 (`CalculationEngine`, `SystemSettings`, `AuditLogger`), Group 2 (dashboard cache infrastructure, for ABC), Group 3 (`BahanBaku`/`InventoryParameter`), Group 4 (PO Create link from Reorder Point).

### Expected files
```
app/Livewire/Optimization/EoqOverview.php                      [NEW]
app/Livewire/Optimization/EoqSimulation.php                     [NEW]
app/Livewire/Optimization/SafetyStockOverview.php                [NEW]
app/Livewire/Optimization/SafetyStockSimulation.php              [NEW]
app/Livewire/Optimization/ReorderPointOverview.php               [NEW]
app/Livewire/Optimization/ReorderPointSimulation.php             [NEW]
app/Livewire/Optimization/AbcAnalysis.php                        [NEW]
resources/views/livewire/optimization/*.blade.php                [NEW — 7 views]
app/Policies/ParameterPolicy.php                                  [COMPLETE]
routes/web.php                                                     [MODIFY]
tests/Feature/Optimization/EoqTest.php                             [NEW]
tests/Feature/Optimization/SafetyStockTest.php                    [NEW]
tests/Feature/Optimization/ReorderPointTest.php                   [NEW]
tests/Feature/Optimization/AbcAnalysisTest.php                    [NEW]
```

### Services involved
`CalculationEngine` (all four formulas), `AuditLogger` (Apply actions), `SystemSettings` (Reset to Defaults).

### UI pages involved
EOQ overview/simulation, Safety Stock overview/simulation, Reorder Point overview/simulation, ABC Analysis (Stitch v2: `simulasi_eoq_cv_akuna`, `detail_simulasi_eoq_cv_akuna`, `simulasi_safety_stock_cv_akuna`, `reorder_point_dashboard_cv_akuna`, `analisis_abc_cv_akuna`).

### Database interaction
Reads `bahan_baku`, `inventory_parameters`, `mutasi_stok` (historical window queries for SD/D computation). Writes `inventory_parameters` only on Apply, always through the same audited path.

### Tests required
- Computed EOQ/SS/ROP match `CalculationEngine` output for identical inputs (regression guard against UI/service drift).
- Owner's Apply button disabled with tooltip; Employee's Apply persists and creates an `AuditLog` row with old/new values — across all three simulation screens.
- Historical window input correctly queries `mutasi_stok` for SD computation (Safety Stock).
- Reorder Point badge logic matches the three-tier threshold exactly at boundary values.
- ABC classification matches `CalculationEngine::classifyAbc()` output for seeded data; donut segment click filters table via Alpine → Livewire event.

### Acceptance criteria
- All four screens match their Stitch v2 references.
- Apply/Reset-to-Defaults behavior is consistent across EOQ, Safety Stock, and Reorder Point (same pattern, verified once, applies to all three by construction).

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
```

### Recommended commit sequence
```
feat(optimization): implement EOQ Overview and Simulation with Apply/Audit flow
feat(optimization): implement Safety Stock Overview and Simulation
feat(optimization): implement Reorder Point Overview and Simulation with critical-stock badges
feat(optimization): implement ABC Analysis with donut chart and classification table
```

---

## Group 6 — Reports & Administration

*(Consolidates v1: M-2.20 Report Generator, M-2.21 User Management, M-2.22 System Settings)*

### Objective
Close out Sprint 2 with the reporting pipeline (needs real data from every prior module) and the two owner-only administration screens, which can be built in either order relative to each other.

### Scope
**Report Generator & History** (UI Spec §6.14, §4.8, §8.5) — the one PRD §6.6 write action shared by both roles: 3 report types (Valuasi Aset, Performa Supplier, Mutasi Bulanan). Generator screen: type selector cards + date range picker + Generate; confirmation dialog if an identical type+range report already exists. Generation flow: Livewire → queued `GenerateReport` Job → dompdf → Supabase Storage → history row updated. Progress via `wire:poll.3s`. History screen: list with signed download links.

**User Management** (UI Spec §6.15, §7.9, §4.9) — Owner-only, the only admin module with write access: List (Nama, Email, Role badge, Status, Login Terakhir, Aksi), filters (role, status), search. Create/Edit modal (Password on Create only; "Reset Password" on Edit). Deactivate = soft delete (`is_active=false`) with confirmation — no hard delete. Employee gets 403 (not redirect-to-login) on any access attempt.

**System Settings** (UI Spec §6.16, §7.10, §4.10) — Owner-only: Company Profile (name, address, logo upload to Supabase Storage), Calculation Parameters (Z-factor, ABC thresholds, historical window, biaya_pesan, biaya_simpan%, saved to `SystemSettings`, with Reset to Defaults confirmation), Notification Preferences (polling interval informational; WhatsApp/Email toggle disabled, labeled "pending client decision").

### Dependencies
Group 4 (PO + Inventory Movements data for reports), Group 1 (seeded data, `SystemSettings`), Group 1/M-2.2 (`User` model + RBAC).

### Expected files
```
app/Livewire/Reports/ReportGenerator.php                        [NEW]
app/Livewire/Reports/ReportHistory.php                            [NEW]
app/Jobs/GenerateReport.php                                        [NEW]
app/Services/ReportService.php                                     [NEW]
resources/views/pdf/valuasi-aset.blade.php                        [NEW]
resources/views/pdf/performa-supplier.blade.php                   [NEW]
resources/views/pdf/mutasi-bulanan.blade.php                      [NEW]
resources/views/livewire/reports/*.blade.php                     [NEW — 2 views]
database/migrations/..._create_report_history_table.php          [NEW]
app/Livewire/Admin/UserManagement.php                              [NEW]
resources/views/livewire/admin/user-management.blade.php          [NEW]
app/Livewire/Admin/Settings.php                                    [NEW]
resources/views/livewire/admin/settings.blade.php                  [NEW]
routes/web.php                                                      [MODIFY]
tests/Feature/Reports/ReportGeneratorTest.php                      [NEW]
tests/Feature/Admin/UserManagementTest.php                          [NEW]
tests/Feature/Admin/SettingsTest.php                                [NEW]
```

### Services involved
`ReportService` (new), `GenerateReport` queued job, `AuditLogger` (user management + settings changes), `SystemSettings` (settings screen writes back to it).

### UI pages involved
Report Generator, Report History, User Management, System Settings (Stitch v2: `generate_laporan_cv_akuna`, `preview_laporan_pdf_cv_akuna`, `manajemen_user_cv_akuna`, `detail_user_cv_akuna`, `pengaturan_sistem_cv_akuna`).

### Database interaction
New `report_history` table (writes on generation, reads on history list). `users` table writes (create/deactivate/reset password) — soft delete only, no hard delete. `system_settings` writes from the Settings screen.

### Tests required
- Report generation completes and produces a download link in History; date validation (end >= start, range required before Generate enables).
- Employee gets 403 (not redirect) on any User Management route; deactivated user cannot authenticate (`is_active` checked at login); email uniqueness validated on save.
- Settings: updating Z-factor changes the pre-fill in Safety Stock Simulation (Group 5) — this is an explicit cross-module regression test; ABC threshold validation (A% < B%, both ≤ 100%).

### Acceptance criteria
- All three screens match their Stitch v2 references.
- Report queue/Storage abstraction works against the local disk driver in tests (`Storage::disk('supabase')` abstracted early, per v1 risk mitigation) and the real Supabase driver in staging/production.
- Settings changes propagate to Group 5 simulation defaults without a page-specific override.

### Quality gates
```bash
php artisan test
composer analyse
vendor/bin/pint --test
npm run build
php artisan migrate:fresh --seed
```
*(Full gate suite, including `migrate:fresh --seed`, since this is the final group of Sprint 2 — a full rebuild must succeed end-to-end before Sprint 2 is declared complete.)*

### Recommended commit sequence
```
feat(reports): implement PDF report generator with queue, Supabase upload, and download history
feat(admin): implement User Management with deactivation and Owner-only RBAC guard
feat(admin): implement System Settings with calculation parameter management and logo upload
```

---

## Consolidated Summary

| # | Group | Absorbs (v1 milestones) | Commits inside group | Review checkpoints |
|---|---|---|---|---|
| 1 | Core Domain Services | M-2.3, M-2.4, M-2.5, + StockMutationService (was M-2.9) | 4 | 1 |
| 2 | Dashboard Module | M-2.6, M-2.7 | 2 | 1 |
| 3 | Master Data Module | M-2.8, M-2.9 (CRUD only), M-2.10, M-2.11 | 4 | 1 |
| 4 | Operations Module | M-2.12, M-2.13, M-2.14, M-2.15 | 4 | 1 |
| 5 | Optimization & Analytics | M-2.16, M-2.17, M-2.18, M-2.19 | 4 | 1 |
| 6 | Reports & Administration | M-2.20, M-2.21, M-2.22 | 3 | 1 |

**Totals: 6 review checkpoints (down from 21) · 21 commits (unchanged — same one-logical-feature-per-commit granularity) · same file count, same test count, same quality gates as v1.**

### Recommended execution order
Unchanged from v1 — Group 1 → Group 2 → Group 3 → Group 4 → Group 5 → Group 6. Internal ordering within each group also follows v1's dependency notes exactly (documented in each group's Scope section above).

### Full quality gate checklist (required before every commit, every group)
```bash
php artisan test                  # All unit + feature tests pass
composer analyse                  # PHPStan — zero errors
vendor/bin/pint --test            # Code style clean
npm run build                     # Vite/Tailwind compiles without error
php artisan migrate:fresh --seed  # Full DB rebuild succeeds (run at minimum after Group 1 and Group 6; recommended after every group)
```

### Risks & mitigations (carried over from v1, unchanged)
| Risk | Mitigation |
|---|---|
| BOM explosion deadlock (concurrent Production entries) | Ascending-ID lock ordering + dedicated concurrency test (Group 4) |
| Supabase Storage auth complexity | Abstract behind `Storage::disk('supabase')` early; local disk driver in tests (Group 6) |
| ApexCharts + Livewire re-render race | Destroy/re-init chart on `wire:navigated`; `@this.on()` event hook (Group 2, Group 5) |
| BOM preview causing excess requests | Client-side Alpine.js computation; server validates only on submit (Group 4) |
| PHPStan `mixed` inference in CalculationEngine | Explicit `@param Collection<int, MutasiStok>` PHPDoc (Group 1) |
| Quota interruption mid-group | Each *commit* (not just each group) is a complete, independently committable unit — commit at the end of every logical feature, not just at group boundaries |

---

> [!NOTE]
> This document reorganizes execution sequencing and review cadence only. For exact field-level specs, Blade component signatures, and Stitch v2 screen references, `implementation_plan.md` v1 remains the line-level source of truth — this roadmap tells you *when to stop and review*, not *what to build*, which is unchanged.
