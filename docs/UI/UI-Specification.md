# UI Specification Document
## Sistem Inventori CV Akuna — v1.0

| Info | Keterangan |
|---|---|
| Versi Dokumen | 1.0 |
| Tanggal | 4 Juli 2026 |
| Basis | PRD v1.1, SAD v1.0, Domain Analysis Report v1.0, Domain Model Revision (BOM & Production), Database Design Document v1.0, Excel Perhitungan Lengkap Inventory 2025 |
| Status | Draft — blueprint for Google Stitch + Laravel implementation |
| Author role | Senior Product Designer / Solution Architect |
| Constraint | No code, no HTML, no Tailwind classes, no Laravel Blade — UI/UX architecture only |

---

## 0. Conflicts Found & Resolutions (Read This First)

Cross-referencing all six source documents surfaced real contradictions — not typos, but different documents making genuinely different claims about permissions and calculation parameters. I'm resolving them here, explicitly, rather than silently picking one and letting it disappear into the rest of the spec.

### 0.1 Employee module access — Master Prompt vs. PRD/SAD (real conflict, resolved in favor of PRD/SAD)

The Master Prompt lists Employee's accessible modules as: Dashboard, Purchase, Production, Inventory, Reports (limited). It explicitly excludes Suppliers, Raw Materials, Finished Goods, BOM, EOQ, Safety Stock, Reorder Point, and ABC Analysis from Employee's list.

But the PRD's own RBAC table (Section 5) and the SAD's actor description say something materially different:
- PRD Section 5: **Karyawan** can "Input produk baru & parameter (biaya, lead time)" (✅) — this is master data (raw materials/finished goods), not just transactions.
- PRD Section 5: **Karyawan** can "Jalankan simulasi EOQ/SS/ROP" ✅ *and* "Terapkan hasil simulasi sebagai parameter resmi" ✅ — Employee is the *only* role that can apply calculation results.
- SAD Section 4: "Karyawan — full read/write **on operational data**, applies EOQ/SS/ROP parameters, **manages procurement**."

Applying calculated parameters and managing procurement are core Karyawan responsibilities per the two most detailed, table-form, explicitly-authoritative sources on permissions. A sidebar that hides EOQ/Safety Stock/Reorder Point from Employee would make it structurally impossible for Karyawan to do the one thing the PRD says only Karyawan can do.

**Resolution applied throughout this document:** Employee's sidebar includes Master Data (Suppliers, Raw Materials, Finished Goods, BOM) and the full Inventory Optimization group (EOQ, Safety Stock, Reorder Point, ABC Analysis), matching PRD Section 5 and SAD Section 4. The Master Prompt's narrower list is treated as an incomplete simplification rather than an intentional restriction. **This is a judgment call, not a rubber-stamped fact — flag if the Master Prompt's shorter list was actually intentional (e.g., a later, unwritten decision to lock master data behind Owner-only editing) and I'll redesign the permission matrix accordingly.**

### 0.2 What "Owner is read-only" does *not* mean

SAD Section 4 states Owner is "read-only across the entire system." Taken absolutely, this would leave no one able to manage Users or Settings, since Employee's access is explicitly capped at operational modules in every source, including the ones I'm otherwise deferring to above. The Master Prompt is the only document that assigns User Management and Settings to a role at all, and it assigns both to Owner.

**Resolution applied:** "Read-only" describes Owner's relationship to *business/transactional data* (stock, purchases, production, calculation parameters) — matching every concrete PRD example, which is always about inventory or procurement actions. Administration (Users, Settings) is treated as a separate concern where Owner is the sole actor with any write capability, by elimination. This is the one place in the entire system where Owner performs create/edit/delete actions.

### 0.3 ABC thresholds and Safety Stock Z-factor — pending decisions with a working default

SAD Section 11 lists ABC classification thresholds (Q3) and the Safety Stock service-level factor (Q4) as **unresolved, not assumed anywhere**. However, the historical Excel workbook (`Perhitungan Lengkap Inventory 2025`, sheet "Catatan Asumsi & Metodologi") already used concrete values to build the 2025 seed data:
- ABC: Class A = cumulative usage value up to 80%, Class B = 80–95%, Class C = 95–100%, sorted descending by `Nilai Pemakaian Tahunan (D × Harga Satuan)`.
- Safety Stock Z-factor = 1.65 (95% service level).
- Order cost (S) = Rp 75.000/order, flat across all materials.
- Holding cost (H) = 20% of unit price per year.
- Historical window used = 12 months (all of 2025).

These are explicitly labeled in that sheet as **research/seed-data assumptions requiring verification with the actual company**, not client-confirmed production parameters. **Resolution applied:** every screen touching these values (EOQ, Safety Stock, Reorder Point, ABC Analysis, and a new Settings → Calculation Parameters screen) treats them as **editable, seeded defaults** rather than hardcoded constants — pre-filled with the values above so the system is immediately usable, but changeable the moment CV Akuna confirms Q3/Q4/Q5 with the client. This also means the underlying calculation engine must read these as configuration, not literals — flagging for the implementation team even though this document doesn't specify code.

### 0.4 Formula spec confirmed (for grounding Section 6 below)

From the same workbook sheet — these are the formulas every EOQ/Safety Stock/ROP screen in this document assumes:

```
EOQ = √(2 × D × S / H)
SD Bulanan = sample standard deviation of 12 months' usage
SD Harian = SD Bulanan / √30
Safety Stock (SS) = Z × SD Harian × √(Lead Time)
ROP = (D / 365 × Lead Time) + SS
```

Where `D` (Kebutuhan Tahunan) is now sourced from `mutasi_stok` "keluar" aggregation per the BOM/Production addendum, not the old sales-percentage estimation.

---

## 1. Information Architecture

### 1.1 Navigation Hierarchy

Two-level hierarchy: a persistent left sidebar for top-level module groups, and in-page tabs or sub-navigation for module-internal views (e.g., Raw Materials vs. Finished Goods within Inventory). No third level — depth is capped at 2 to keep daily use fast, per the "minimal learning curve" objective.

```
Sidebar (Level 1 — Module Groups)
 ├─ Dashboard
 ├─ Master Data
 │   ├─ Suppliers
 │   ├─ Raw Materials
 │   ├─ Finished Goods
 │   └─ Bill of Materials (BOM)
 ├─ Purchasing
 ├─ Production
 ├─ Inventory
 │   ├─ Raw Material Stock
 │   ├─ Finished Goods Stock
 │   ├─ Inventory Movements (ledger)
 │   └─ Stock Adjustment
 ├─ Inventory Optimization
 │   ├─ EOQ
 │   ├─ Safety Stock
 │   ├─ Reorder Point
 │   └─ ABC Analysis
 ├─ Reports
 ├─ User Management        (Owner only)
 └─ Settings                (Owner only)
```

### 1.2 Sidebar Behavior

- Sidebar is always visible on desktop/laptop (per desktop-first priority); collapsible to icon-only rail on narrower viewports (Section 13).
- Module groups with sub-items (Master Data, Inventory, Inventory Optimization) expand in place — accordion behavior, one group open at a time to avoid a sprawling always-open tree.
- The active module group and active sub-item are both visually indicated (Section 12 — Design System covers exact treatment).
- Sidebar items a role cannot access are **not rendered at all** for Employee (User Management, Settings never appear). This differs from disabling: hiding is cleaner for a 2-role system with a hard module boundary, and reduces daily clutter for Employee's actual permitted, larger module set (Section 0.1). Owner, by contrast, sees every module but with write controls disabled/hidden per-screen (Section 6 permission matrices) — Owner's restriction is about actions within a page, not entire pages.

### 1.3 Page Grouping Logic

- **Master Data** groups anything that's configured occasionally and referenced constantly (suppliers, materials, products, recipes) — low transaction frequency, high reference frequency.
- **Purchasing / Production / Inventory** are the three high-frequency daily-use groups — this is where both roles spend most active session time, so they sit at the top of the sidebar, immediately below Dashboard.
- **Inventory Optimization** is separated from Inventory itself because its cadence is different: EOQ/SS/ROP are reviewed/simulated periodically (per PO cycle or monthly), not touched on every stock movement.
- **Reports** stands alone since both roles use it but for different reasons (Employee: operational proof/audit; Owner: strategic review).
- **User Management / Settings** are isolated at the bottom, visually separated (a divider line above them), signaling "administrative, not operational."

### 1.4 User Navigation Flow (High-Level)

```
Login → role resolved server-side → redirect to role-appropriate Dashboard
   ├─ Owner Dashboard → any module (read) → Reports (download) → Logout
   └─ Employee Dashboard → any operational module (read/write) → Reports → Logout
```

Both roles land on a dashboard first — never a bare module page — because the dashboard is where the "critical stock" and "recent activity" signals live, and both PRD and SAD treat immediate visibility of these as a top priority (PRD Section 2: "Owner tidak punya visibilitas real-time... tanpa meminta laporan manual").

---

## 2. Sitemap

```
Authentication
├─ Login
└─ Logout (action, not a page — redirects to Login)

Dashboard
├─ Owner Dashboard
└─ Employee Dashboard

Master Data
├─ Suppliers
│   ├─ Supplier List
│   └─ Supplier Detail / Create / Edit
├─ Raw Materials
│   ├─ Raw Material List
│   └─ Raw Material Detail / Create / Edit
├─ Finished Goods
│   ├─ Finished Goods List
│   └─ Finished Goods Detail / Create / Edit
└─ Bill of Materials (BOM)
    ├─ BOM List (by Finished Good)
    └─ BOM Editor (per Finished Good)

Purchasing
├─ Purchase Order List
├─ Purchase Order Detail
├─ Create Purchase Order (Rutin)
└─ Create Purchase Order (Darurat / Emergency)

Production
├─ Production Entry List
└─ Create Production Entry

Inventory
├─ Raw Material Stock Overview
├─ Finished Goods Stock Overview
├─ Inventory Movements (Mutasi Stok Ledger)
└─ Stock Adjustment (Create Manual Mutation)

Inventory Optimization
├─ EOQ
│   ├─ EOQ Overview (all materials)
│   └─ EOQ Simulation (per material)
├─ Safety Stock
│   ├─ Safety Stock Overview
│   └─ Safety Stock Simulation (per material)
├─ Reorder Point
│   ├─ Reorder Point Overview
│   └─ Reorder Point Simulation (per material)
└─ ABC Analysis
    └─ ABC Analysis Report (table + donut chart)

Reports
├─ Report Generator
│   ├─ Warehouse Asset Valuation (Valuasi Aset Gudang)
│   ├─ Supplier Performance (Performa Supplier)
│   └─ Monthly Movement (Mutasi Bulanan)
└─ Report History (previously generated PDFs)

User Management (Owner only)
├─ User List
└─ User Detail / Create / Edit

Settings (Owner only)
├─ Company Profile & PDF Letterhead
├─ Calculation Parameters (Z-factor, ABC thresholds, historical window, order cost, holding cost %)
└─ Notification Preferences
```

Total distinct screens: **34** (counted individually in Section 4).

---

## 3. User Flows

### 3.1 Login

1. User lands on Login screen (only unauthenticated route besides password-reset, if enabled — see Section 9 open item).
2. Enters email/username + password. No role selector — role is resolved server-side from the account record (PRD 6.1, explicit security requirement).
3. On success: redirect to Owner Dashboard or Employee Dashboard based on resolved role.
4. On failure: inline error below the password field ("Email/username atau kata sandi salah"), rate-limited after repeated attempts (SAD 7.6) — after the throttle limit, show a countdown-style message rather than a generic error, so the user understands *why* the form is temporarily locked rather than assuming it's broken.
5. No "remember me" / "forgot password" flow is specified in any source document — flagged as an open item (Section 9).

### 3.2 Purchase (Routine and Emergency)

**Routine (Rutin):**
1. Employee opens Purchase Order List → Create Purchase Order (Rutin).
2. Selects raw material, supplier (defaults to the material's routine supplier per Master Data), quantity, expected price (defaults to last known routine price).
3. System shows estimated arrival (order date + supplier lead time).
4. On submit: PO created with status **Menunggu**.
5. Employee updates status Menunggu → Dalam Proses when the supplier confirms, then → Diterima on physical receipt.
6. On transition to **Diterima**: system auto-generates a `mutasi_stok` "masuk" record (sumber = `po_penerimaan`), increments the raw material's `stok_saat_ini`, and logs both the status change and the resulting mutation to the audit trail (SAD 8.3). This happens synchronously in the same action — Employee sees the updated stock figure immediately, no separate step.

**Emergency (Darurat):** Same flow, but the Create screen defaults to the material's emergency supplier (Jakarta, per Master Bahan Baku data), shows the emergency lead time (typically 1–3 days) and emergency price (routine price +20%, per the historical workbook's convention), and is visually flagged (an amber/urgent badge) throughout its lifecycle so it's distinguishable from routine orders in the list view. This flow is typically triggered directly from a critical-stock alert (Section 3.6).

### 3.3 Production and Automatic BOM Deduction

1. Employee opens Production → Create Production Entry.
2. Selects a Finished Good and enters quantity to produce.
3. **Before showing a submit button**, the system displays a BOM explosion preview: every raw material the BOM references, the quantity required (`qty_per_unit × jumlah_diproduksi`), current stock, and resulting stock after deduction — so the Employee sees the consequence before committing, not after.
4. If any required quantity exceeds current stock, the preview flags the offending line(s) in an error state and **the submit action is disabled** — this is the UI expression of the approved "block the entry" decision (Domain Model Revision, Section 5, Decision 1). No production entry can be submitted while any line is short.
5. On submit (only reachable when all lines pass): system creates the Production Entry and, atomically, two `mutasi_stok` records — one "keluar" per BOM line (raw material), one "masuk" for the finished good — per the addendum's atomicity requirement. The UI shows a single success confirmation covering both effects ("12 unit Sabun Batang X diproduksi — 4 bahan baku terpakai"), not two separate toasts, since to the user this was one action.
6. The BOM explosion preview and the post-submit result both link through to Inventory Movements filtered to this Production Entry, so the Employee (or Owner, reviewing later) can see the exact ledger rows this entry created.

### 3.4 Inventory Updates (Manual Mutation)

1. Employee opens Inventory → Stock Adjustment.
2. Selects item type (Raw Material or Finished Goods), selects the specific item, chooses direction (masuk/keluar), enters quantity and a reason/note.
3. Manual mutations always carry `sumber = manual` — this field is never user-selectable; it's implied by using this screen at all (system-generated mutations from PO receipt or Production never route through this form).
4. On submit: `mutasi_stok` record created, `stok_saat_ini` updated, audit trail logs actor + timestamp (PRD 6.3).
5. Owner never sees this screen's write controls — Owner can view the resulting ledger (Inventory Movements) but the Stock Adjustment create form itself is not reachable for Owner (Section 0.2).

### 3.5 EOQ / Safety Stock / Reorder Point Calculation

1. User (Employee or Owner) opens EOQ (or Safety Stock, or Reorder Point — same interaction pattern across all three).
2. Overview screen shows current official values for every raw material, sourced from each material's active parameter set.
3. User drills into a specific material's Simulation screen, optionally overriding order cost, holding cost %, lead time, or historical window (all pre-filled with current values/defaults per Section 0.3).
4. System computes the simulated result using the confirmed formulas (Section 0.4) and displays it **side-by-side with the current official value** — PRD 6.4's explicit "tabel perbandingan nilai lama vs hasil simulasi" requirement.
5. **Employee** sees an "Apply as Official Parameter" action; confirming overwrites the material's single active parameter set and logs old value/new value/actor/timestamp to the audit trail (no parameter-history table, per ADR-004).
6. **Owner** sees the identical comparison screen but the Apply action is replaced with a disabled control showing a tooltip ("Owner dapat menjalankan simulasi, tidak dapat menerapkan hasil — PRD 5") — Owner can still explore "what if" scenarios freely, just never persist one.

### 3.6 Stock Alerts

1. Background: a scheduled job (Cloud Scheduler → Laravel scheduler, SAD 8.4) compares every raw material's `stok_saat_ini` against its `reorder_point` and writes the currently-critical set to a Redis cache key.
2. Both dashboards poll this cache key via Livewire (`wire:poll`, interval per SAD 7.4) and re-render the notification bell badge count and the "Live Stock Critical Alert" table when it changes — this is near-real-time, not instant (explicitly a documented v1 limitation, SAD 7.4).
3. Clicking the bell opens a dropdown listing critical materials (kode, nama, stok saat ini, ROP, deficit). Clicking a row navigates to that material's Raw Material Detail screen.
4. From the critical-stock table (dashboard or Raw Material Detail), Employee has a direct "Buat PO Darurat" quick action that pre-fills an Emergency Purchase Order form with that material and the estimated shortfall quantity — this is the concrete UI expression of PRD 6.5's stockout-driven emergency ordering logic. Owner sees the same table without this action (monitoring only, per PRD 5: Owner "hanya memantau").

### 3.7 PDF Report Generation

1. User opens Reports → Report Generator, selects one of three report types (Warehouse Asset Valuation, Supplier Performance, Monthly Movement).
2. Selects a date range (required before the generate action becomes active).
3. Clicks Generate → system shows a loading/progress state (report generation is not instant — it queries data, renders via dompdf, and uploads to Supabase Storage server-side, SAD 8.5) — the UI must not block the rest of the page during this, so this runs as a background action with a visible progress indicator, not a full-page spinner.
4. On completion, a signed/temporary download link appears (never a raw file path, per SAD 7.1) and the report is added to Report History.
5. Both roles can generate and download all three report types (PRD 6.6: "dapat diunduh oleh kedua role") — no permission split here, unlike every other mutating flow in the system.

### 3.8 Logout

1. User clicks Logout (in the top-right user menu, present on every authenticated screen).
2. Session invalidated server-side (PRD 6.1: "Logout aman, invalidasi sesi") — no confirmation dialog needed, logout is non-destructive to data and reversible by logging back in.
3. Redirect to Login.

---

## 4. Screen Inventory

34 screens total, grouped by module. "Permissions" states each role's capability on that specific screen (R = read/view, W = write/mutate, — = no access).

### 4.1 Authentication

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Login | Authenticate and route to the correct dashboard | Centered form card, email/username field, password field, submit button, error banner | Submit login | — | None (pre-auth) | Public |

### 4.2 Dashboard

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Owner Dashboard | Strategic, at-a-glance oversight; zero write surface | KPI cards, ABC donut chart, top-5-cost bar chart, critical stock table, recent activity feed, upcoming reorders panel | Drill into any card/chart to its full module | Download a quick report shortcut | All KPIs, all-role activity feed | R only |
| Employee Dashboard | Operational cockpit for daily tasks | KPI cards (operational subset), critical stock table with quick actions, recent activity (own + system-generated), quick-create buttons | Quick-create PO / Production Entry / Stock Adjustment; resolve critical stock | Same drill-ins as Owner | Operational KPIs, own recent actions | R + W (via quick actions) |

### 4.3 Master Data

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Supplier List | Browse/search suppliers | Data table, search box, filter, "Add Supplier" button | Create, edit, delete supplier | Export list, sort columns | kode, nama, alamat, kontak, # materials supplied | Employee: R+W · Owner: R |
| Supplier Detail/Create/Edit | View or edit one supplier's full record | Form (create/edit) or read panel (view), linked raw-materials list | Save, delete (with confirmation) | Cancel/discard changes | Full supplier fields + materials sourced from them | Employee: R+W · Owner: R |
| Raw Material List | Browse/search raw materials | Data table, search, filters (ABC class, supplier), "Add Material" button | Create, edit, delete material | Export, sort, bulk ABC re-tag (system-only, not manual — see 4.3 note) | kode, nama, satuan, stok_saat_ini, ABC class, harga_satuan | Employee: R+W · Owner: R |
| Raw Material Detail/Create/Edit | View/edit one material; hub linking to its EOQ/SS/ROP and BOM usage | Form or read panel, stock summary card, linked EOQ/SS/ROP mini-panel, "used in these BOMs" list, recent mutations mini-table | Save, delete (blocked if referenced by an active BOM — see validation, Section 7) | Jump to full EOQ/SS/ROP simulation, jump to Inventory Movements filtered to this material | Full fields, current parameter set, recent 10 mutations | Employee: R+W · Owner: R |
| Finished Goods List | Browse/search finished goods | Data table, search, "Add Finished Good" button | Create, edit, delete finished good | Export, sort | kode, nama, satuan, stok_saat_ini | Employee: R+W · Owner: R |
| Finished Goods Detail/Create/Edit | View/edit one finished good; hub linking to its BOM | Form or read panel, stock summary card, "View/Edit BOM" link, recent production entries mini-table | Save, delete (blocked if referenced by any BOM or Production Entry — see validation) | Jump to BOM Editor, jump to Inventory Movements | Full fields, current active BOM summary, recent production | Employee: R+W · Owner: R |
| BOM List | Overview of which finished goods have a defined recipe | Data table (one row per finished good), "missing BOM" indicator | Open BOM Editor for a finished good | Filter to "no BOM defined yet" | finished good, # ingredient lines, last edited | Employee: R+W · Owner: R |
| BOM Editor | Define/edit one finished good's raw-material composition | Line-item editor (add/remove rows), each row: material picker, qty per unit, unit | Add line, remove line, save | Reorder lines (cosmetic only) | Current BOM lines for this finished good | Employee: R+W · Owner: R |

*Note on Raw Material List "bulk ABC re-tag": there is no manual bulk action here — ABC class is always system-computed (PRD 9: "diklasifikasikan otomatis oleh sistem"). This column is display-only on every screen; flagged so it isn't mistaken for an editable field anywhere in implementation.*

### 4.4 Purchasing

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Purchase Order List | Track all POs and their logistics status | Data table, status filter (Menunggu/Dalam Proses/Diterima), type filter (Rutin/Darurat), search | Create PO, advance a PO's status | Export, sort | kode_po, material, supplier, jumlah, status, tanggal_pesan, estimasi_tiba | Employee: R+W · Owner: R |
| Purchase Order Detail | Full record of one PO, including the mutation it produced once received | Header info, status timeline (Menunggu → Dalam Proses → Diterima), linked mutation record (once Diterima) | Advance status, cancel (if Menunggu only) | Jump to linked mutation in Inventory Movements | All PO fields, status history, resulting mutasi_stok if received | Employee: R+W · Owner: R |
| Create Purchase Order (Rutin) | Log a routine, planned order | Form: material, supplier (defaulted), quantity, price (defaulted), computed estimated arrival | Submit | Cancel | Defaults sourced from Master Data | Employee: W · Owner: — |
| Create Purchase Order (Darurat) | Log an emergency order, usually alert-triggered | Same form, pre-filled with emergency supplier/price/lead-time, urgent visual treatment | Submit | Cancel | Same, emergency-sourced defaults; may arrive pre-filled from a critical-stock quick action | Employee: W · Owner: — |

### 4.5 Production

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Production Entry List | History of all production runs | Data table, date filter, finished-good filter | Create entry | Export, sort | finished good, jumlah_diproduksi, tanggal, dicatat_oleh | Employee: R+W · Owner: R |
| Create Production Entry | Log a production run and trigger BOM explosion | Form: finished good picker, quantity input, live BOM explosion preview table | Submit (disabled if any line insufficient) | Cancel | BOM lines with required/available/resulting stock per line | Employee: W · Owner: — |

### 4.6 Inventory

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Raw Material Stock Overview | "Gudang Bahan Baku" — current state of all raw materials (PRD 6.3) | Data table, search/filter by kode/nama/category | Jump to material detail, jump to Stock Adjustment | Export | kode, nama, satuan, stok_saat_ini, ROP status badge | Employee: R+W (via linked actions) · Owner: R |
| Finished Goods Stock Overview | "Gudang Barang Jadi" — current state of all finished goods | Data table, search/filter | Jump to finished good detail | Export | kode, nama, satuan, stok_saat_ini | Employee: R+W · Owner: R |
| Inventory Movements | Single ledger of all stock mutations, any origin | Data table, filters (item type, jenis_mutasi, sumber, date range, specific item) | View mutation detail (read-only, immutable record) | Export, sort | Full mutasi_stok fields incl. sumber and originating record link | Employee: R · Owner: R |
| Stock Adjustment | Create a manual mutation | Form: item type toggle, item picker, direction, quantity, note | Submit | Cancel | — | Employee: W · Owner: — |

### 4.7 Inventory Optimization

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| EOQ Overview | All materials' current official EOQ at a glance | Data table, sort by EOQ/class | Drill into simulation | Export | kode, nama, D, S, H, current EOQ | Employee: R+W (via drill-in) · Owner: R |
| EOQ Simulation | Compute and optionally apply a new EOQ for one material | Input panel (S, H, D — editable, pre-filled), computed result, side-by-side old-vs-new comparison | Employee: Apply · Owner: (disabled, tooltip) | Reset to defaults | Formula inputs/outputs, comparison table | Employee: R+W · Owner: R (simulate only) |
| Safety Stock Overview | All materials' current official SS | Data table | Drill into simulation | Export | kode, nama, SD harian, lead time, Z, current SS | Employee: R+W · Owner: R |
| Safety Stock Simulation | Compute/apply new SS | Input panel (Z, historical window, lead time — pre-filled), computed result, comparison | Employee: Apply · Owner: (disabled) | Reset | Formula inputs/outputs | Employee: R+W · Owner: R (simulate only) |
| Reorder Point Overview | All materials' current official ROP, with critical-stock flags | Data table with status badges (OK/Near/Critical) | Drill into simulation, jump to emergency PO | Export | kode, nama, stok_saat_ini, ROP, status | Employee: R+W · Owner: R |
| Reorder Point Simulation | Compute/apply new ROP | Input panel, computed result, comparison | Employee: Apply · Owner: (disabled) | Reset | Formula inputs/outputs | Employee: R+W · Owner: R (simulate only) |
| ABC Analysis Report | Full classification table + visual breakdown | Donut chart (A/B/C), data table sorted by cumulative %, class filter | None (system-computed, no manual override) | Export | kode, nama, nilai pemakaian tahunan, % individual, % kumulatif, kelas | Employee: R · Owner: R |

### 4.8 Reports

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Report Generator | Configure and generate one of 3 PDF report types | Report-type selector (3 cards), date range picker, generate button, progress indicator | Generate | Cancel generation in progress | — (config screen) | Employee: W · Owner: W (generation is the one shared write action, PRD 6.6) |
| Report History | List of previously generated reports | Data table: type, date range, generated by, generated at, download link | Download | Delete old reports (if storage retention becomes a concern — flagged as open item) | Report metadata + signed download links | Employee: R+W · Owner: R+W (download only, per above) |

### 4.9 User Management (Owner only)

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| User List | See all system accounts | Data table, search | Create user, deactivate user | Sort | name, email, role, status, last login | Owner: R+W · Employee: — |
| User Detail/Create/Edit | Manage one account | Form: name, email, role selector, active toggle, password reset trigger | Save, deactivate | Cancel | Account fields | Owner: R+W · Employee: — |

### 4.10 Settings (Owner only)

| Screen | Purpose | Main Components | Primary Actions | Secondary Actions | Displayed Data | Permissions |
|---|---|---|---|---|---|---|
| Company Profile & PDF Letterhead | Configure report branding (SAD Q8 pending — placeholder fields) | Form: company name, address, logo upload | Save | — | Current values | Owner: R+W · Employee: — |
| Calculation Parameters | Configure the system-wide defaults flagged in Section 0.3 | Form: Z-factor, ABC thresholds (A/B cutoffs), historical window (months), default order cost, default holding cost % | Save | Reset to seeded defaults (80/95/1.65/75000/20%) | Current global parameter values | Owner: R+W · Employee: — |
| Notification Preferences | Configure alerting behavior | Form: polling interval display (informational, not user-editable — it's an infra setting per SAD 7.4), future WhatsApp/Email toggle (disabled, labeled "pending client decision") | Save | — | Current preferences | Owner: R+W · Employee: — |

---

## 5. Dashboard Specification

### 5.1 Owner Dashboard

**Purpose:** give the Owner everything the PRD's Section 2 problem statement asks for — "visibilitas real-time terhadap kondisi gudang tanpa meminta laporan manual" — with zero write surface anywhere on the page.

| Zone | Content |
|---|---|
| KPI Cards (top row, 4 cards) | Total jenis bahan baku aktif · Total nilai investasi tahunan (Rp, sum of Nilai Pemakaian Tahunan) · Jumlah material berstatus kritis (below ROP) · Nilai stok barang jadi saat ini |
| ABC Donut Chart | Segments for Class A/B/C by count or value (toggle), click a segment → ABC Analysis Report filtered to that class |
| Top-5 Cost Bar Chart | Horizontal bar, 5 most expensive raw materials by Nilai Pemakaian Tahunan (PRD 6.2's explicit requirement) |
| Critical Stock Table | Live, polling-updated (Section 3.6): kode, nama, stok_saat_ini, ROP, deficit — sortable by deficit descending so the worst case surfaces first |
| Recent Activity Feed | Last ~15 mutations/POs/production entries across the whole system (Owner sees everything, unlike Employee's feed) |
| Upcoming Reorders Panel | Materials projected to cross ROP within N days based on recent average daily usage (a light forward projection, not a new calculation engine — reuses existing `D`/365 daily-usage figure) |
| Notification Bell | Top-right, badge count = current critical materials |

**No quick-create buttons anywhere on this dashboard** — that's the one deliberate visual difference from Employee's dashboard, reinforcing "read-only" as a page-level design signal, not just a disabled-button afterthought (PRD 7, Usability: "elemen terkunci untuk Owner diberi indikator visual jelas, bukan sekadar hilang" — but for *entire actions*, the clearest indicator is that the action's entry point doesn't exist on this page at all, while still being reachable and visibly-disabled at the point of use, e.g. inside EOQ Simulation's Apply button).

### 5.2 Employee Dashboard

**Purpose:** operational cockpit — what needs doing today.

| Zone | Content |
|---|---|
| KPI Cards (top row, 3 cards) | Material kritis hari ini · PO menunggu konfirmasi/penerimaan · Produksi tercatat bulan ini |
| Critical Stock Table | Same data as Owner's, plus a per-row "Buat PO Darurat" quick action (Section 3.6) |
| Quick Actions Bar | Three prominent buttons: Buat PO Baru, Catat Produksi, Sesuaikan Stok — the fast paths into the three highest-frequency write actions |
| Recent Activity Feed | Employee's own recent actions, prioritized above other actors' activity |
| Notification Bell | Same mechanism as Owner |

No ABC donut / top-5-cost chart on Employee's dashboard by default — those are strategic-review artifacts Owner needs and Employee doesn't need daily; both remain one click away via Inventory Optimization → ABC Analysis if an Employee wants them, keeping Employee's landing page focused on action rather than analysis.

---

## 6. Module Specifications

Each module below covers: Purpose, table columns, filters, search, sorting, pagination, actions, dialogs, empty state, loading state, validation errors, success messages, and its permission matrix.

### 6.1 Dashboard
Covered fully in Section 5 — not a data-table module, so most of the standard fields below don't apply. Loading state: skeleton cards + skeleton chart placeholders while KPI queries resolve (dashboard KPIs are cached/scheduled per SAD 7.2, so this is typically a near-instant cache read, not a live query wait).

### 6.2 Suppliers
- **Table columns:** Kode, Nama, Alamat, Kontak, # Bahan Baku Terkait, Aksi
- **Filters:** none needed at expected data volume (10 suppliers in current data)
- **Search:** by kode or nama
- **Sorting:** nama (default asc), kode
- **Pagination:** client-side, 25/page (dataset is small; server-side pagination is over-engineering here but the table component itself should support it for future growth)
- **Actions:** Add Supplier, Edit, Delete (row-level)
- **Dialogs:** Delete confirmation ("Hapus supplier ini? Bahan baku yang terhubung tidak akan terhapus, tapi referensinya akan dikosongkan.")
- **Empty state:** "Belum ada supplier tercatat" + Add Supplier button inline
- **Loading state:** table skeleton rows (5 placeholder rows)
- **Validation errors:** Kode must be unique — inline error under the field on save attempt
- **Success message:** toast, "Supplier {nama} berhasil disimpan"
- **Permission matrix:** Employee R+W, Owner R (Delete/Edit/Add hidden for Owner, not just disabled — list-level actions follow the same hide-don't-disable pattern as dashboard quick actions)

### 6.3 Raw Materials
- **Table columns:** Kode, Nama, Satuan, Stok Saat Ini, Kelas ABC (badge), Harga Satuan, Supplier Rutin, Aksi
- **Filters:** Kelas ABC (A/B/C/All), Supplier
- **Search:** kode or nama
- **Sorting:** any column, default by Kode asc
- **Pagination:** 25/page
- **Actions:** Add Material, Edit, Delete (row-level, blocked with inline explanation if referenced by an active BOM)
- **Dialogs:** Delete confirmation (with the BOM-reference block shown as a disabled-delete state + explanation, not a dialog that then fails)
- **Empty state:** "Belum ada bahan baku tercatat"
- **Loading state:** table skeleton
- **Validation errors:** Kode unique, Stok Saat Ini ≥ 0, Satuan required
- **Success message:** toast, "Bahan baku {nama} berhasil disimpan"
- **Permission matrix:** Employee R+W, Owner R

### 6.4 Finished Goods
- **Table columns:** Kode, Nama, Satuan, Stok Saat Ini, BOM Terdefinisi? (yes/no badge), Aksi
- **Filters:** BOM status (defined/not defined)
- **Search:** kode or nama
- **Sorting:** Kode asc default
- **Pagination:** 25/page
- **Actions:** Add Finished Good, Edit, Delete (blocked if referenced by any BOM line or Production Entry)
- **Dialogs:** Delete confirmation with same reference-block pattern as Raw Materials
- **Empty state:** "Belum ada barang jadi tercatat" — matters especially at go-live, since the addendum notes FinishedGoods started with zero sample data
- **Loading state:** table skeleton
- **Validation errors:** Kode unique, Satuan required
- **Success message:** toast, "Barang jadi {nama} berhasil disimpan"
- **Permission matrix:** Employee R+W, Owner R

### 6.5 Bill of Materials (BOM)
- **Table columns (BOM List):** Finished Good, # Ingredient Lines, Terakhir Diubah, Aksi
- **Filters:** "Belum ada BOM" toggle
- **Search:** by finished good name
- **Sorting:** finished good name
- **Pagination:** 25/page
- **Actions (List):** Open Editor
- **Actions (Editor):** Add Line, Remove Line, Save
- **Dialogs:** Remove-line confirmation only if the material has already been used in a production entry under this recipe (informational, not blocking — the immutable mutation history already protects historical accuracy per Decision 2)
- **Empty state (Editor):** "Belum ada bahan baku dalam resep ini — tambahkan baris pertama"
- **Loading state:** editor skeleton (a few blank line rows)
- **Validation errors:** each line requires material + qty_per_unit > 0 + satuan; duplicate material in the same BOM blocked inline ("Bahan baku ini sudah ada di baris lain")
- **Success message:** toast, "Komposisi produk {nama} berhasil disimpan"
- **Permission matrix:** Employee R+W, Owner R

### 6.6 Purchases
- **Table columns:** Kode PO, Bahan Baku, Supplier, Jenis (Rutin/Darurat badge), Jumlah, Status (badge), Tanggal Pesan, Estimasi Tiba, Aksi
- **Filters:** Status, Jenis, Supplier, date range
- **Search:** kode PO or material name
- **Sorting:** tanggal_pesan desc default
- **Pagination:** server-side, 25/page (this table grows fast — 132 historical rows already exist in one year of dummy data)
- **Actions:** Create PO (Rutin/Darurat — a 2-option menu, not two separate list buttons), Advance Status, Cancel (Menunggu only)
- **Dialogs:** Advance-to-Diterima confirmation ("Konfirmasi penerimaan {jumlah} {satuan} {material}? Stok akan otomatis bertambah."), Cancel confirmation
- **Empty state:** "Belum ada pesanan pembelian" + Create PO button inline
- **Loading state:** table skeleton
- **Validation errors:** Jumlah > 0, Supplier required, can't advance status backward
- **Success messages:** toast per action ("PO {kode} berhasil dibuat", "Status PO {kode} diperbarui ke {status}")
- **Permission matrix:** Employee R+W, Owner R

### 6.7 Production
- **Table columns:** Tanggal, Barang Jadi, Jumlah Diproduksi, Dicatat Oleh, Aksi
- **Filters:** date range, finished good
- **Search:** finished good name
- **Sorting:** tanggal desc default
- **Pagination:** server-side, 25/page
- **Actions:** Create Production Entry; row-level "Lihat Detail Mutasi" (jumps to Inventory Movements filtered to this entry)
- **Dialogs:** Submit confirmation only if any BOM line will bring a material within 10% of its ROP after deduction — an advisory, non-blocking dialog ("Produksi ini akan membuat stok {material} mendekati titik pemesanan ulang. Lanjutkan?") distinct from the hard block on insufficient stock
- **Empty state:** "Belum ada produksi tercatat"
- **Loading state:** table skeleton; BOM explosion preview shows its own inline skeleton while computing
- **Validation errors:** Jumlah Diproduksi > 0; any BOM line insufficient → submit disabled + per-line red highlight + summary banner ("Stok tidak mencukupi untuk {n} bahan baku")
- **Success message:** toast, "{jumlah} unit {barang jadi} berhasil diproduksi — stok bahan baku & barang jadi diperbarui"
- **Permission matrix:** Employee W (+R on list), Owner R only

### 6.8 Inventory Movements
- **Table columns:** Tanggal, Item (material/finished good, with type badge), Jenis (masuk/keluar), Jumlah, Sumber (badge: manual/PO/produksi), Referensi (link to PO or Production Entry if applicable), Dicatat Oleh
- **Filters:** item type, jenis_mutasi, sumber, specific item, date range
- **Search:** item name/kode
- **Sorting:** tanggal desc default
- **Pagination:** server-side, 50/page (this is the highest-volume table in the system)
- **Actions:** none — every row is immutable, view-only, by design (this is the audit ledger)
- **Dialogs:** none
- **Empty state:** "Belum ada mutasi stok tercatat"
- **Loading state:** table skeleton
- **Validation errors:** n/a (read-only)
- **Success messages:** n/a
- **Permission matrix:** Employee R, Owner R (identical — this is the one inventory-adjacent screen where both roles have exactly the same capability, since viewing the ledger isn't a mutation)

### 6.9 Stock Adjustment
- **Table columns:** n/a (this is a create-only form screen, not a list — its output appears in Inventory Movements)
- **Filters / Search / Sorting / Pagination:** n/a
- **Actions:** Submit, Cancel
- **Dialogs:** Submit confirmation if quantity is unusually large relative to recent average movement for that item (soft warning, not a hard block — "Jumlah ini jauh lebih besar dari rata-rata mutasi bulanan. Lanjutkan?")
- **Empty state:** n/a
- **Loading state:** form fields disabled + spinner on submit button during save
- **Validation errors:** Item required, Jumlah > 0, direction required; if "keluar" would bring stock negative → hard block, matching the same negative-stock philosophy applied to production (Decision 1's spirit extends here even though the addendum only formally decided it for production)
- **Success message:** toast, "Mutasi stok berhasil dicatat"
- **Permission matrix:** Employee W, Owner —

### 6.10 EOQ / 6.11 Safety Stock / 6.12 Reorder Point
These three share one interaction pattern (Overview + per-material Simulation), so specified together; differences noted inline.
- **Table columns (Overview):** Kode, Nama, Kelas ABC, [formula-specific inputs — D/S/H for EOQ; SD Harian/Lead Time/Z for SS; Stok Saat Ini/Daily Usage/Lead Time/SS for ROP], Current Official Value, Aksi
- **Filters:** Kelas ABC
- **Search:** kode/nama
- **Sorting:** by current value, by class
- **Pagination:** client-side, 25/page (10 materials currently — table component still supports pagination for scale)
- **Actions (Overview):** Drill into Simulation
- **Actions (Simulation):** Recompute (live, as inputs change — no separate "calculate" button needed since the formulas are cheap), Apply (Employee only), Reset to Defaults
- **Dialogs:** Apply confirmation ("Terapkan {nilai baru} sebagai parameter resmi untuk {material}? Nilai lama: {nilai lama}.")
- **Empty state:** n/a (always populated once materials exist)
- **Loading state:** simulation panel skeleton while recomputing (should be near-instant; skeleton mainly covers network round-trip if computation is server-side)
- **Validation errors:** all numeric inputs must be > 0; Z-factor between 0 and 3 (sanity bound, not a formula requirement, to catch fat-finger entry)
- **Success message:** toast, "Parameter {jenis} untuk {material} berhasil diperbarui"
- **Permission matrix:** Employee R+W (simulate + apply), Owner R (simulate only, Apply disabled with tooltip)

### 6.13 ABC Analysis
- **Table columns:** Kode, Nama, Nilai Pemakaian Tahunan, % Individual, % Kumulatif, Kelas
- **Filters:** Kelas (A/B/C)
- **Search:** kode/nama
- **Sorting:** % Kumulatif asc (default, matches the ranking logic itself)
- **Pagination:** client-side, 25/page
- **Actions:** none — fully system-computed, no manual override (Section 4.3 note)
- **Dialogs:** none
- **Empty state:** "Klasifikasi ABC akan muncul setelah data pemakaian tersedia"
- **Loading state:** table + donut chart skeleton
- **Validation errors:** n/a
- **Success messages:** n/a
- **Permission matrix:** Employee R, Owner R (identical, view-only for both — nobody manually sets ABC class)

### 6.14 Reports
- **Table columns (History):** Jenis Laporan, Rentang Tanggal, Dibuat Oleh, Tanggal Dibuat, Unduh
- **Filters:** report type, date generated
- **Search:** n/a at expected volume
- **Sorting:** tanggal dibuat desc
- **Pagination:** client-side, 25/page
- **Actions:** Generate (Generator screen), Download (History screen)
- **Dialogs:** none required — generation itself is non-destructive
- **Empty state:** "Belum ada laporan dibuat" on History
- **Loading state:** progress bar/spinner during generation (not a skeleton, since this represents actual server-side work in progress, per SAD 8.5)
- **Validation errors:** date range required before Generate enables; end date ≥ start date
- **Success message:** toast + auto-add to History, "Laporan {jenis} berhasil dibuat"
- **Permission matrix:** Employee R+W, Owner R+W (shared, per PRD 6.6 — the one fully-shared write action in the system)

### 6.15 Users (Owner only)
- **Table columns:** Nama, Email, Role, Status (aktif/nonaktif), Login Terakhir, Aksi
- **Filters:** role, status
- **Search:** nama/email
- **Sorting:** nama asc default
- **Pagination:** client-side (small user count expected per SAD Q10, still pending)
- **Actions:** Add User, Edit, Deactivate (soft, not hard delete — preserves `dicatat_oleh` referential integrity across historical mutations/POs/production entries)
- **Dialogs:** Deactivate confirmation ("Nonaktifkan akun ini? Pengguna tidak dapat login, tapi riwayat aktivitasnya tetap tersimpan.")
- **Empty state:** never truly empty (at least one Owner account must exist to reach this screen)
- **Loading state:** table skeleton
- **Validation errors:** email unique + valid format, role required
- **Success message:** toast, "Pengguna {nama} berhasil disimpan"
- **Permission matrix:** Owner R+W, Employee — (page not rendered)

### 6.16 Settings (Owner only)
- **Table columns:** n/a (form-based screens)
- **Actions:** Save (per sub-screen), Reset to Defaults (Calculation Parameters only)
- **Dialogs:** Reset-to-defaults confirmation
- **Empty state:** n/a
- **Loading state:** form skeleton on first load
- **Validation errors:** Calculation Parameters — Z between 0–3, ABC thresholds must be ascending and ≤100 (A cutoff < B cutoff < 100)
- **Success message:** toast, "Pengaturan berhasil disimpan"
- **Permission matrix:** Owner R+W, Employee — (page not rendered)

---

## 7. Forms

Every Create/Edit form in the system, specified field-by-field. Grouped by module; shared conventions stated once up front.

**Shared conventions across all forms:** Required fields are marked with a red asterisk, not color alone (accessibility — Section 14). Every form shows inline field-level errors on blur *and* a summary banner on failed submit ("Periksa kembali {n} bidang yang bermasalah"). Numeric fields use a locale-appropriate thousands separator for Rupiah amounts but store/submit raw numbers. Currency fields display an "Rp" prefix inside the input, not as separate static text, so the value stays legible while scrolling a long form.

### 7.1 Supplier Create/Edit
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Kode | Text | Unique, max 30 chars | Yes | "SUP-001" | — | "Kode sudah digunakan" | Digunakan sebagai referensi singkat di seluruh sistem |
| Nama | Text | Max 150 chars | Yes | "CV. Multi Kimia" | — | "Nama wajib diisi" | — |
| Alamat | Textarea | Max 500 chars | No | "Jl. ..." | — | — | — |
| Kontak | Text | Max 100 chars | No | "081234567890 / email" | — | — | Nomor telepon atau email PIC |

### 7.2 Raw Material Create/Edit
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Kode | Text | Unique, max 30 chars | Yes | "BB01" | — | "Kode sudah digunakan" | — |
| Nama | Text | Max 150 chars | Yes | "Methyl Ester Sulfonate (MES)" | — | "Nama wajib diisi" | — |
| Satuan | Select | From fixed unit list (kg, liter, gram, ml, pcs) | Yes | — | — | "Pilih satuan" | Menentukan satuan seluruh transaksi bahan baku ini |
| Stok Saat Ini | Number | ≥ 0 | Yes (only on Create; read-only on Edit — stock changes only via mutations) | "0" | 0 | "Stok tidak boleh negatif" | Setelah dibuat, ubah stok melalui Mutasi Stok, bukan di sini |
| Supplier Rutin | Select (searchable) | Must exist in Suppliers | Yes | "Pilih supplier" | — | "Supplier wajib dipilih" | Digunakan sebagai default saat membuat PO Rutin |
| Harga Satuan | Number (Rp) | > 0 | Yes | "32000" | — | "Harga wajib diisi" | Harga dari supplier rutin — dipakai dalam kalkulasi EOQ/SS/ROP |
| Lead Time (Hari) | Number | > 0, integer | Yes | "4" | — | "Lead time wajib diisi" | Waktu tunggu pengiriman dari supplier rutin |

### 7.3 Finished Good Create/Edit
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Kode | Text | Unique, max 30 chars | Yes | "FG01" | — | "Kode sudah digunakan" | — |
| Nama | Text | Max 150 chars | Yes | "Sabun Batang Lavender" | — | "Nama wajib diisi" | — |
| Satuan | Select | Fixed unit list | Yes | — | — | "Pilih satuan" | — |
| Stok Saat Ini | Number | ≥ 0 | Yes (Create only, read-only on Edit) | "0" | 0 | "Stok tidak boleh negatif" | Ubah melalui Produksi atau Mutasi Stok setelahnya |

### 7.4 BOM Line (repeatable row within BOM Editor)
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Bahan Baku | Select (searchable) | Must exist, unique within this BOM | Yes | "Pilih bahan baku" | — | "Bahan baku ini sudah ada di baris lain" | — |
| Qty per Unit | Number | > 0, up to 4 decimals | Yes | "0.0500" | — | "Kuantitas wajib > 0" | Jumlah bahan baku dibutuhkan per 1 unit barang jadi |
| Satuan | Select | Fixed unit list | Yes | — | Inherits from selected material | "Pilih satuan" | — |

### 7.5 Create Purchase Order (Rutin / Darurat)
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Bahan Baku | Select (searchable) | Must exist | Yes | "Pilih bahan baku" | — | "Bahan baku wajib dipilih" | — |
| Supplier | Select (searchable) | Must exist | Yes | — | Routine or emergency supplier per material, based on order type | "Supplier wajib dipilih" | Dapat diganti manual jika perlu |
| Jumlah | Number | > 0 | Yes | "27.58" | Suggested EOQ value for Rutin; shortfall quantity for Darurat (from alert) | "Jumlah wajib diisi" | — |
| Harga Satuan | Number (Rp) | > 0 | Yes | — | Routine or emergency price (+20%) per material | "Harga wajib diisi" | Dapat disesuaikan bila supplier memberi harga berbeda |
| Tanggal Pesan | Date | Not in the past | Yes | — | Today | "Tanggal tidak valid" | — |
| Lead Time (Hari) | Number | > 0 | Yes | — | Routine or emergency lead time per material | — | Digunakan untuk menghitung estimasi tiba |

### 7.6 Create Production Entry
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Barang Jadi | Select (searchable) | Must have an active BOM defined | Yes | "Pilih barang jadi" | — | "Barang jadi ini belum memiliki resep (BOM)" | Buat BOM terlebih dahulu di menu Bill of Materials |
| Jumlah Diproduksi | Number | > 0 | Yes | "12" | — | "Jumlah wajib > 0" | Memicu perhitungan otomatis kebutuhan bahan baku |
| Tanggal Produksi | Date | Not in the future | Yes | — | Today | "Tanggal tidak valid" | — |

### 7.7 Stock Adjustment (Manual Mutation)
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Jenis Item | Toggle (Bahan Baku / Barang Jadi) | — | Yes | — | Bahan Baku | — | Menentukan daftar item di bawah |
| Item | Select (searchable) | Must exist for chosen type | Yes | "Pilih item" | — | "Item wajib dipilih" | — |
| Jenis Mutasi | Toggle (Masuk / Keluar) | — | Yes | — | Masuk | — | — |
| Jumlah | Number | > 0; if Keluar, ≤ stok saat ini | Yes | "10" | — | "Stok tidak mencukupi untuk mutasi keluar ini" | — |
| Keterangan | Textarea | Max 255 chars | No | "Alasan penyesuaian stok..." | — | — | Direkomendasikan diisi untuk kejelasan audit |

### 7.8 EOQ / Safety Stock / Reorder Point Simulation Inputs
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Biaya Pesan (S) | Number (Rp) | > 0 | Yes (EOQ) | "75000" | Global default from Settings | "Biaya pesan wajib > 0" | — |
| Biaya Simpan (H) | Number (Rp/unit/th) | > 0 | Yes (EOQ) | — | 20% × harga satuan | "Biaya simpan wajib > 0" | — |
| Z-Factor | Number | 0–3 | Yes (Safety Stock) | "1.65" | Global default from Settings | "Nilai Z di luar rentang wajar" | 1.65 = tingkat layanan 95% |
| Periode Historis (bulan) | Number | 1–24, integer | Yes (Safety Stock) | "12" | Global default from Settings | — | Jumlah bulan data pemakaian yang dipakai untuk SD |
| Lead Time (Hari) | Number | > 0 | Yes (Safety Stock, ROP) | — | Material's current lead time | "Lead time wajib > 0" | Dapat diuji dengan skenario lead time berbeda |

### 7.9 User Create/Edit (Owner only)
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Nama | Text | Max 150 chars | Yes | "Nama lengkap" | — | "Nama wajib diisi" | — |
| Email | Email | Unique, valid format | Yes | "nama@ceahakuna.com" | — | "Email sudah digunakan / tidak valid" | Digunakan untuk login |
| Role | Select | Karyawan / Owner | Yes | — | Karyawan | "Pilih role" | Menentukan seluruh hak akses akun ini |
| Password | Password | Min 8 chars (Create only; Edit shows "Reset Password" action instead) | Yes on Create | — | — | "Kata sandi minimal 8 karakter" | — |
| Status Aktif | Toggle | — | Yes | — | Aktif | — | Nonaktifkan alih-alih menghapus akun |

### 7.10 Calculation Parameters (Settings, Owner only)
| Field | Input Type | Validation | Required | Placeholder | Default | Error Message | Help Text |
|---|---|---|---|---|---|---|---|
| Z-Factor Default | Number | 0–3 | Yes | "1.65" | 1.65 | "Nilai di luar rentang wajar" | Berlaku sebagai nilai awal di setiap simulasi Safety Stock baru |
| Ambang Kelas A (%) | Number | 0–100 | Yes | "80" | 80 | "Harus lebih kecil dari Ambang Kelas B" | Kumulatif nilai pemakaian hingga persentase ini masuk Kelas A |
| Ambang Kelas B (%) | Number | 0–100, > Ambang A | Yes | "95" | 95 | "Harus lebih besar dari Ambang Kelas A" | Kumulatif 80–95% masuk Kelas B, sisanya Kelas C |
| Periode Historis Default (bulan) | Number | 1–24 | Yes | "12" | 12 | — | — |
| Biaya Pesan Default (Rp) | Number | > 0 | Yes | "75000" | 75000 | — | Nilai awal untuk simulasi EOQ baru |
| Biaya Simpan Default (%/tahun) | Number | 0–100 | Yes | "20" | 20 | — | Persentase dari harga satuan, dipakai jika bahan baku tidak punya nilai kustom |

---

## 8. Tables

All data tables in the system follow one shared component spec, with per-table column definitions already given in Section 6. Common behavior:

| Aspect | Specification |
|---|---|
| **Sorting** | Click column header toggles asc/desc; sort icon indicates current direction; default sort stated per module in Section 6 |
| **Filtering** | Filter controls sit in a row above the table, collapsible into a "Filters" button on narrower viewports (Section 13); active filters shown as removable chips |
| **Bulk Actions** | Only Purchase Order List and User List support bulk selection (bulk status export / bulk deactivate respectively) — every other table's row count and action set doesn't justify bulk selection UI overhead |
| **Export** | CSV export available on every list table (button top-right of table), respecting current filters/search — not a separate "export everything" path |
| **Pagination** | Server-side pagination for high-volume tables (Purchase Orders, Production Entries, Inventory Movements); client-side for small/bounded datasets (Suppliers, Raw Materials, Finished Goods, EOQ/SS/ROP/ABC overviews, Users) — the distinction is stated per-module in Section 6 |
| **Responsive Behavior** | On tablet/mobile, tables collapse to a stacked card-per-row layout (Section 13) rather than horizontal scroll, since horizontal scroll on data-dense inventory tables actively hides the columns staff need most |

---

## 9. Modal/Dialog Specifications

| Modal | Trigger | Content | Primary Action | Secondary Action |
|---|---|---|---|---|
| Delete Confirmation (generic) | Delete on Supplier/Raw Material/Finished Good | Warning text + reference-count if applicable | Hapus (destructive style) | Batal |
| Delete Blocked (reference exists) | Delete attempted on a referenced Raw Material/Finished Good | Explanation of what references it (BOM lines, active production history) | Lihat Referensi | Tutup |
| Advance PO Status | Status change action on a Purchase Order | Summary of the transition; on Diterima specifically, a note that stock will auto-increase | Konfirmasi | Batal |
| Confirm Production | Submit on Create Production Entry (only shown when soft-warning threshold hit, Section 6.7) | BOM explosion summary, near-ROP warning if applicable | Lanjutkan Produksi | Batal |
| Insufficient Stock (informational) | Submit blocked on Create Production Entry | List of insufficient materials with required vs. available | Tutup (no override option — hard block) | — |
| Apply Simulation Result | Apply on EOQ/SS/ROP Simulation | Old value vs. new value | Terapkan | Batal |
| Generate Report | Generate on Report Generator (only if report already exists for the identical type + range) | "Laporan dengan rentang ini sudah pernah dibuat. Buat ulang?" | Buat Ulang | Gunakan yang Sudah Ada (jumps to History) |
| Reset Filters | "Reset" link near active filter chips | none — direct action, no modal needed for such a low-stakes, reversible action | — | — |
| Deactivate User | Deactivate on User List/Detail | Explanation that history is preserved, login is blocked | Nonaktifkan | Batal |
| Reset Calculation Parameters | Reset to Defaults on Settings | List of values that will revert | Reset | Batal |
| Unsaved Changes | Navigating away from a dirty form | "Perubahan belum disimpan. Tinggalkan halaman ini?" | Tinggalkan | Tetap di Halaman |

Note: "Reset Filters" is listed per the Master Prompt's example set but is deliberately **not** implemented as a modal (see row above) — a confirmation dialog for a free, reversible, low-consequence action like clearing filters would violate the "minimal learning curve, fast daily usage" objective by adding friction where none is warranted.

---

## 10. Notification System

| Type | Trigger | Visual Treatment | Persistence |
|---|---|---|---|
| Success | Any successful create/edit/delete/status-change action | Green toast, top-right, auto-dismiss ~4s | Transient |
| Error | Failed validation, failed server action | Red toast + inline field errors where applicable | Transient (toast); inline errors persist until corrected |
| Warning | Soft, non-blocking cautions (e.g., near-ROP production, large stock adjustment) | Amber toast or inline banner, does not auto-dismiss if inline | Transient (toast) or until dismissed (inline banner) |
| Critical Stock | Material crosses below ROP (polling-detected) | Red badge on bell icon + row in Live Stock Critical Alert table | Persistent until resolved (stock rises above ROP again or is acknowledged) |
| Reorder Reminder | Material approaching ROP (Upcoming Reorders panel, Owner dashboard) | Amber row in Upcoming Reorders panel, no toast interruption | Persistent, dashboard-only |
| Production Completed | Production Entry submitted successfully | Green toast summarizing both stock effects (Section 3.3) | Transient |
| Purchase Received | PO status advanced to Diterima | Green toast + updated stock figure visible immediately in the same view | Transient |

All toasts stack (max 3 visible, older ones queue) rather than overlapping, and are dismissible manually before their auto-timeout. Per SAD 7.4, critical-stock detection itself is polling-based (near-real-time, not instant) — this is stated once here rather than re-qualified on every notification row, but applies specifically to the Critical Stock and Reorder Reminder types.

---

## 11. UI Components

| Component | Notes |
|---|---|
| Buttons | Primary (solid, one per view max for the "main" action), Secondary (outline), Destructive (red, delete-class actions only), Disabled (Owner's blocked write actions — always paired with a tooltip explaining why, never a silently-inert button) |
| Cards | KPI Card, Stat Card (number + trend indicator), Content Card (wraps forms/panels) |
| Data Tables | Per Section 8 |
| Charts | Donut (ABC), Horizontal Bar (top-5 cost), Line (usage trend, optional future addition — not in current PRD scope but the component should support it) — all via ApexCharts (SAD ADR-006) |
| Badges | Status badges (PO status, ABC class, critical-stock level) — color + text label together, never color alone (Section 14) |
| Alerts | Inline banners for form-level and page-level warnings/errors |
| Modals | Per Section 9 |
| Tabs | Used within Master Data detail screens where a record has multiple related views (e.g., Raw Material Detail: Info / EOQ-SS-ROP / History tabs) |
| Forms | Per Section 7 |
| Breadcrumbs | Module Group / Module / Record — e.g., "Master Data / Raw Materials / BB01" |
| Dropdowns | Searchable select for any foreign-key picker (material, supplier, finished good) — plain select only for small fixed enums (unit, status, role) |
| Pagination | Per Section 8 |
| Search Box | Consistent placement top-left of table toolbar across all modules |
| Filters | Per Section 8 |
| Status Chips | Removable chips representing active filters |
| Timeline | Used on Purchase Order Detail (Menunggu → Dalam Proses → Diterima) |

---

## 12. Design System

**Color Palette (Light Theme):**
- Primary (brand/action): deep blue — conveys the "professional ERP" tone requested, distinct from playful consumer-app palettes
- Success: green
- Warning: amber
- Danger/Destructive: red
- Neutral grays: for backgrounds, borders, secondary text — the majority of the interface, since this is a data-dense tool, not a marketing surface
- ABC class colors: Class A = a strong accent (highest priority), Class B = a mid-tone, Class C = neutral gray — reflects genuine priority ordering, not arbitrary categorical colors

**Typography:** A single modern sans-serif family throughout (e.g., Inter or similar geometric grotesque) — one family, weight variation (regular/medium/semibold) does the hierarchy work rather than mixing families. Numeric/tabular data uses tabular figures so columns of numbers align.

**Spacing:** 4px base unit, scaling in multiples (4/8/12/16/24/32/48) — consistent rhythm across cards, form fields, and table cell padding.

**Border Radius:** Small-to-medium radius throughout (not fully rounded, not sharp) — consistent with the "modern professional," not "playful," brief.

**Icons:** One consistent icon set throughout (e.g., a single outline-style icon library) — never mixing icon styles between modules.

**Elevation:** Flat design with minimal shadow use — a subtle shadow only on floating elements (dropdowns, modals, toasts), not on static cards, to avoid visual noise across data-dense screens.

**Animations:** Purposeful and brief only — table row updates on poll refresh, toast enter/exit, modal fade — no decorative animation. Given the "fast daily usage" priority, animation should never add perceived latency to a repeated action.

**Light Theme:** Fully specified (this is the v1 deliverable).

**Dark Theme:** Explicitly future — not designed in this document (per Master Prompt Section 12's own framing), but the color palette above should be chosen with dark-mode inversion in mind (i.e., avoid pure black/white and avoid colors that lose meaning when inverted) so it isn't a rebuild later.

---

## 13. Responsive Rules

| Breakpoint | Behavior |
|---|---|
| **Desktop (≥1280px)** | Primary target. Full sidebar always visible, tables show all columns, dashboards show full multi-column grid. This is the default design target per the Master Prompt's "desktop-first" priority. |
| **Laptop (1024–1279px)** | Sidebar visible but narrower; dashboard grid drops from 4 to 3 KPI cards per row; tables unaffected. |
| **Tablet (768–1023px)** | Sidebar collapses to icon-only rail by default (expandable on tap); tables switch to stacked card-per-row (Section 8); dashboard grid drops to 2 columns. |
| **Mobile (<768px)** | Sidebar becomes a slide-over drawer triggered by a hamburger icon; all tables are stacked cards; forms go full-width single-column; this is explicitly a secondary experience, not a primary target — CV Akuna's own stated context is staff and Owner working from the office (PRD 7: "dipakai staf gudang & owner di kantor"). |

**Desktop-first pages, explicitly:** every screen in this system is desktop-first — there is no module where mobile is the primary use case, consistent with warehouse-staff/office usage. Mobile support exists so the system isn't broken if someone checks it from a phone, not as a designed-for scenario.

---

## 14. Accessibility

| Area | Requirement |
|---|---|
| Keyboard Navigation | All interactive elements (buttons, form fields, table sort headers, modal actions) reachable and operable via Tab/Enter/Escape; modals trap focus while open and return focus to the trigger element on close |
| ARIA | Proper roles/labels on icon-only buttons (e.g., notification bell needs an aria-label, not just a visual icon), live regions for toast notifications and polling-updated critical-stock counts so screen readers announce changes |
| Color Contrast | All text/background pairs meet WCAG AA minimum; status badges/colors are never the sole indicator (paired with text label, per Section 11) |
| Focus States | Visible focus ring on every interactive element, consistent styling across the whole system — never removed for aesthetic reasons |
| Validation Accessibility | Error messages programmatically associated with their field (not just visually adjacent), form-level error summary is focus-managed so a screen-reader user lands on it after a failed submit |

---

## 15. UX Guidelines

| Pattern | Application |
|---|---|
| **Loading Skeleton** | Used for all table/card/chart initial loads (Section 6, per-module) — never a bare spinner on data-heavy views |
| **Optimistic Updates** | Deliberately **not** used for stock-affecting actions (Production, PO receipt, Stock Adjustment) — given the atomicity and negative-stock-blocking requirements (Domain Model addendum §4.2, §5), the UI must wait for server confirmation before showing success, to avoid ever displaying a state the backend didn't actually commit. Optimistic updates are acceptable for low-stakes UI state only (e.g., expanding a sidebar group, toggling a filter chip) |
| **Confirmation Dialogs** | Reserved for destructive or hard-to-reverse actions (Section 9) — not sprinkled on every save, matching the "minimal learning curve, fast daily usage" objective |
| **Undo Patterns** | Not implemented for stock mutations (every mutation is an immutable audit record by design — an "undo" would itself have to be a new, opposite mutation, which is exactly how a correction should be made: explicitly, not silently reversed) |
| **Error Recovery** | Failed submissions preserve all entered form data (never clear a form on error) and focus the first invalid field |
| **Empty States** | Every list screen has a specific, actionable empty state (Section 6, per-module) rather than a generic "No data" |
| **Success Feedback** | Toast + visible state change in the same view (e.g., updated stock number visible immediately after PO receipt) — never a silent success |

---

## 16. Navigation Rules

| Rule | Specification |
|---|---|
| **Back Navigation** | Browser back button always returns to the prior screen's actual prior state (filters/pagination preserved), since this is a server-rendered Livewire app (SAD 3) — no client-side router state to lose |
| **Breadcrumb** | Shown on all Level-2 screens (Detail/Editor/Simulation pages) per Section 11; omitted on Level-1 list screens where the sidebar's active state already communicates location |
| **Sidebar Collapse** | User-toggleable on Laptop and above (Section 13 auto-collapses it below that); collapsed/expanded state persists per-user across sessions |
| **Page Titles** | Every screen sets a descriptive browser tab title ("Bahan Baku — Sistem Inventori CV Akuna") for multi-tab usability, since staff frequently keep several modules open simultaneously |
| **Context Actions** | Primary action for a screen always lives top-right of the content area (e.g., "Add Supplier"), consistent placement across every module so users build muscle memory |

---

## 17. Page-by-Page Wireframe Description

No images — text descriptions detailed enough to reproduce layout. Shared shell described once; per-page sections describe only what differs.

### 17.1 Shared Application Shell

- **Header** (full width, fixed top, ~64px): left — collapse toggle + breadcrumb; right — notification bell (with badge), user avatar/name dropdown (Profile — not in scope, Logout).
- **Sidebar** (fixed left, ~240px expanded / ~64px collapsed): module groups per Section 1, active state highlighted with a left accent bar + background tint.
- **Content Area** (fills remaining space, scrollable independently of sidebar/header): page title + primary action button in a header row, then the page's specific content below.
- **Footer**: none — internal business tool, no marketing footer needed; content area extends to viewport bottom.

### 17.2 Owner / Employee Dashboard
Content area: 
- Row 1: KPI cards in a horizontal grid (4 for Owner, 3 for Employee), equal width, icon + label + big number + optional trend arrow.
- Row 2 (Owner only) / Quick Actions Bar (Employee only): Owner sees a two-column split — ABC Donut Chart (left, ~40% width) and Top-5 Cost Bar Chart (right, ~60% width) side by side; Employee sees three large tappable action buttons in a row instead.
- Row 3: Critical Stock Table, full width, with its own mini-header ("Live Stock Critical Alert" + last-updated timestamp reflecting the polling cadence).
- Row 4: two-column split — Recent Activity Feed (left, ~50%) and Upcoming Reorders Panel (Owner) or nothing/secondary content (Employee) (right, ~50%).

### 17.3 List Screens (Suppliers, Raw Materials, Finished Goods, Purchase Orders, Production Entries, etc.)
- Content header row: page title (left), primary action button (right, e.g. "+ Add Supplier").
- Toolbar row: search box (left), filter controls + export button (right).
- Active filter chips row (only rendered when filters are active).
- Data table, full width, sticky header row on scroll.
- Pagination controls, bottom-right of the table.

### 17.4 Detail/Create/Edit Screens (Raw Material Detail, Supplier Detail, etc.)
- Content header row: breadcrumb above title; title (left), Save/Cancel or Edit/Delete buttons (right).
- Two-column layout on desktop: main form/read panel (~65% width, left) and a contextual side panel (~35%, right) showing related data — e.g., Raw Material Detail's side panel holds the stock summary card and the "used in these BOMs" list.
- Below the two-column section, full-width: a small "recent activity" or "recent mutations" table relevant to this record.

### 17.5 BOM Editor
- Header: finished good name + Save button.
- Body: a repeatable line-item list, each line a horizontal row (material picker, qty input, unit, remove icon) — an "+ Add Line" button below the last row.
- Right-side or bottom summary panel: total ingredient count, last-edited timestamp.

### 17.6 Create Production Entry
- Top: form fields (finished good picker, quantity, date) in a compact horizontal row.
- Below: BOM Explosion Preview, a table that populates live as the finished good + quantity are chosen — columns: Bahan Baku, Dibutuhkan, Stok Tersedia, Sisa Setelah Produksi, Status (OK/Insufficient badge per row).
- Bottom: summary banner (either a green "Siap diproduksi" or a red "Stok tidak mencukupi untuk N bahan baku") + Submit button (disabled state tied directly to that banner's status) + Cancel.

### 17.7 EOQ/Safety Stock/Reorder Point Simulation
- Left column (~40%): input panel — editable fields pre-filled with current values, each with its help text (Section 7.8).
- Right column (~60%): comparison table — two columns (Nilai Saat Ini / Hasil Simulasi) with the differing figure highlighted, plus the underlying formula shown in small print beneath for transparency ("EOQ = √(2×D×S/H)").
- Below both columns, full width: Apply button (Employee) or disabled Apply with tooltip (Owner), plus Reset to Defaults link.

### 17.8 ABC Analysis Report
- Top: donut chart (left, ~35%) + a compact legend/summary card (right, ~65%) stating class counts and total value share, mirroring the workbook's own "Ringkasan Klasifikasi ABC" summary.
- Below: full data table, sorted by cumulative % ascending, class column shown as a colored badge.

### 17.9 Report Generator
- Three report-type cards in a row, each with an icon, name, and one-line description; selecting one highlights it.
- Below: date range picker (two date fields).
- Bottom: Generate button, full-width or right-aligned; progress indicator appears in place of the button during generation, replaced by a success state with the download link once complete.

### 17.10 Login
- Centered card (~400px wide) on a plain, uncluttered background — no sidebar, no header chrome.
- Card contains: system name/logo, email/username field, password field, submit button, error banner slot above the fields (only rendered on failure).

**Floating elements used system-wide:** toast notifications (top-right, stacking), the notification bell dropdown (anchored under the bell icon). No floating action buttons (FABs) — every primary action already has a fixed, predictable location per Section 16, so a FAB would duplicate rather than add value in this desktop-first, data-dense context.

---

## 18. Google Stitch Prompt

The following is the single, optimized prompt to hand to Google Stitch to generate the UI:

> Design a modern enterprise Inventory Management System UI for "CV Akuna," an internal web application (Laravel + Livewire backend, not needed for the design) used by warehouse staff and business owners of a small cosmetics/personal-care manufacturer. Style: clean SaaS dashboard, professional ERP aesthetic, minimalist interface, Tailwind CSS visual language (spacing, type scale, component shapes), desktop-first responsive layout (primary target 1280px+, graceful degradation to tablet/mobile). Accessible design: strong color contrast, visible focus states, never color-only status indicators.
>
> **Layout shell:** persistent left sidebar (collapsible), fixed top header with a notification bell and user menu, main content area with a page-title-and-primary-action header row on every screen.
>
> **Two user roles with different sidebar visibility and write permissions:** "Karyawan" (Employee) has full read/write access to Dashboard, Master Data (Suppliers, Raw Materials, Finished Goods, Bill of Materials), Purchasing, Production, Inventory (stock overviews, movement ledger, manual stock adjustment), and Inventory Optimization (EOQ, Safety Stock, Reorder Point, ABC Analysis). "Owner" sees every one of those same modules but read-only — every save/create/delete control is either hidden (list-level actions) or visibly disabled with an explanatory tooltip (simulation "Apply" buttons); Owner additionally has exclusive access to User Management and Settings, which are hidden entirely from Karyawan's sidebar.
>
> **Design a dashboard for each role:** the Owner dashboard shows 4 KPI cards, an ABC classification donut chart, a horizontal bar chart of the 5 most expensive raw materials, a live "critical stock" alert table (materials below their reorder point), a recent activity feed, and an upcoming-reorders panel — with zero create/edit buttons anywhere on the page. The Employee dashboard shows 3 KPI cards, the same critical-stock table but with an inline "Create Emergency Purchase Order" action per row, three large quick-action buttons (New Purchase Order, Record Production, Adjust Stock), and a recent-activity feed of the user's own actions.
>
> **Design these core modules as data tables with search, filters, sorting, and pagination:** Suppliers; Raw Materials (with an ABC-class badge column); Finished Goods; Purchase Orders (with status badges: Menunggu/Dalam Proses/Diterima, and order-type badges: Rutin/Darurat-with-urgent-styling); Production Entries; a unified Inventory Movements ledger (immutable, read-only, filterable by source: manual/purchase-receipt/production); EOQ, Safety Stock, and Reorder Point overview tables; an ABC Analysis report combining a donut chart with a ranked table.
>
> **Design these key forms/interactive screens:** a Bill of Materials editor (repeatable ingredient-line rows: material picker, quantity-per-unit, unit, remove icon); a "Create Production Entry" screen that shows a live BOM-explosion preview table (required quantity vs. available stock vs. resulting stock per raw material, with a red "insufficient" state that disables the submit button when any line is short — this hard validation is a core business rule, make it visually unmissable); an EOQ/Safety-Stock/Reorder-Point simulation screen with an editable input panel on the left and a side-by-side "current value vs. simulated result" comparison table on the right, plus the formula shown in small print for transparency; a Report Generator with 3 selectable report-type cards (Warehouse Asset Valuation, Supplier Performance, Monthly Movement), a date-range picker, and a generation-progress state.
>
> **Notification system:** a bell icon with a badge count in the header, opening a dropdown list of currently-critical materials; toast notifications (success=green, error=red, warning=amber) stacking top-right.
>
> **Visual style specifics:** deep-blue primary/action color, green/amber/red for success/warning/danger, generous but efficient spacing for data-dense tables, a single modern sans-serif typeface, tabular figures for numeric columns, small-to-medium border radius, minimal shadow/elevation (flat design with subtle shadows only on floating elements like modals and toasts), one consistent outline-style icon set throughout, purposeful and brief animations only.
>
> This is an internal business application — prioritize information density, scan-ability, and fast repeated daily use over decorative visual flourish. Include: Login screen (centered card, no chrome), the two role-specific dashboards, at least one full data-table module (Raw Materials or Purchase Orders), the Production Entry screen with its BOM explosion preview, and one EOQ/Safety-Stock/Reorder-Point simulation screen with the comparison view.

---

## 19. Summary of Open Items Carried Forward

None of these block starting design/build work — every one has a stated default in this document — but all should get explicit client/stakeholder sign-off before they harden into shipped behavior:

1. **Employee module access** (Section 0.1) — this document expands Employee's sidebar beyond the Master Prompt's original list to match the PRD/SAD RBAC tables. Confirm this wasn't an intentional later restriction.
2. **Owner's administrative write exception** (Section 0.2) — Owner manages Users/Settings despite being otherwise read-only; confirm no other party should hold this responsibility instead.
3. **ABC thresholds, Z-factor, historical window, order cost, holding cost %** (Section 0.3) — currently seeded from the historical Excel workbook's research assumptions, exposed as editable Settings rather than hardcoded. These are still formally "Pending Client Decision" (SAD Section 11, Q3–Q5) — the values are usable defaults, not confirmed final answers.
4. **Password reset / "forgot password" flow** — not specified in any source document; not designed in this document. Flag if it's needed before Login is finalized.
5. **PDF letterhead/branding content** (SAD Q8) — Settings screen exists as a placeholder; actual required fields (logo specs, letterhead text) need confirmation.
6. **WhatsApp/Email notifications** (SAD Q7) — Notification Preferences screen includes a disabled placeholder toggle only; no design work beyond acknowledging the toggle exists, pending that decision.
7. **Report History retention/deletion** — mentioned as a possible future need in Section 4.8 but not resolved; PDF storage costs/retention policy aren't addressed in any source document.

---

# Addendum — Implementation-Readiness Package (v1.1)

| Info | Keterangan |
|---|---|
| Trigger | Freeze prep: make the UI Specification directly usable by Google Stitch (visual generation) and Laravel (Policies/Gates, routes, migrations) without further translation |
| Scope | Adds Sections 20–22 below. Nothing in Sections 0–19 above is altered. |
| Basis | UI Specification Document Sections 0–19 (unchanged), SAD Section 6 (Authorization Architecture), Domain Model Revision (RBAC Addition, §4.3), schema.dbml |
| Status | Addendum — ready for freeze once reviewed |

This addendum is strictly additive. Where it restates a rule already established above (e.g., "Owner cannot create a Purchase Order"), it's repeating it in a more granular, implementation-mapped form — it isn't introducing a new decision. Two places required a small interpretive call that Sections 0–19 didn't explicitly settle; both are flagged inline rather than silently assumed:

- **Export, by default, is granted wherever View is granted.** Section 8 states CSV export is available on every list table without splitting it by role; since export is a read-derived action (it can't mutate data), this addendum treats it as bundled with View rather than with the write permissions. Flag if Owner should be excluded from exporting any specific dataset.
- **A handful of policy/capability/table names below are proposed, not sourced.** Where SAD Section 6 already names a policy class (`StockMutationPolicy`, `ProcurementPolicy`, `ParameterPolicy`) or the Domain Model Revision names a capability (`production.record`), those exact names are reused. Everywhere else (e.g., `SupplierPolicy`, `settings.update`), the name is a reasonable, consistent proposal for the Laravel team to adopt or rename — not a name pulled from an existing source document.

---

## 20. RBAC Permission Matrix

### 20.1 How to Read This Section

Two roles only, per every source document: **Owner** and **Employee** (Karyawan). For each module, six abilities are scored:

- **View** — can see the module's list/detail/report screens
- **Create** — can add a new record
- **Edit** — can modify an existing record
- **Delete** — can remove/deactivate a record
- **Export** — can download the module's data (CSV/PDF)
- **Approve** — a distinct commit/authorization step beyond ordinary Create/Edit (PO status advance, EOQ/SS/ROP Apply); marked **N/A** where a module has no such step

Legend: ✅ Allowed · ❌ Not allowed (control hidden, not disabled, per Section 1.2/5.1) · 🔶 Conditional (business-rule-gated) · N/A Not applicable to this module

### 20.2 Master Summary Grid

| Module | Role | View | Create | Edit | Delete | Export | Approve |
|---|---|---|---|---|---|---|---|
| Dashboard | Owner | ✅ | N/A | N/A | N/A | N/A | N/A |
| Dashboard | Employee | ✅ | N/A | N/A | N/A | N/A | N/A |
| Suppliers | Owner | ✅ | ❌ | ❌ | ❌ | ✅ | N/A |
| Suppliers | Employee | ✅ | ✅ | ✅ | 🔶 (blocked if a raw material still sources from this supplier) | ✅ | N/A |
| Raw Materials | Owner | ✅ | ❌ | ❌ | ❌ | ✅ | N/A |
| Raw Materials | Employee | ✅ | ✅ | 🔶 (stock field itself is never directly editable — only via mutation) | 🔶 (blocked if referenced by an active BOM) | ✅ | N/A |
| Finished Goods | Owner | ✅ | ❌ | ❌ | ❌ | ✅ | N/A |
| Finished Goods | Employee | ✅ | ✅ | 🔶 (stock field never directly editable) | 🔶 (blocked if referenced by any BOM or Production Entry) | ✅ | N/A |
| Bill of Materials (BOM) | Owner | ✅ | ❌ | ❌ | ❌ | ✅ | N/A |
| Bill of Materials (BOM) | Employee | ✅ | ✅ | ✅ | 🔶 (soft warning only if the material has production history under this recipe) | ✅ | N/A |
| Purchase Orders | Owner | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Purchase Orders | Employee | ✅ | ✅ | ❌ (no free-form edit — only status advance/cancel) | 🔶 (cancel allowed only while status = Menunggu) | ✅ | ✅ (advance status, incl. receipt) |
| Production Entries | Owner | ✅ | ❌ | N/A (immutable) | N/A (immutable) | ✅ | N/A |
| Production Entries | Employee | ✅ | 🔶 (hard-blocked if any BOM line is insufficient) | N/A (immutable) | N/A (immutable) | ✅ | N/A |
| Inventory Movements (ledger) | Owner | ✅ | N/A (system-generated only) | N/A (immutable) | N/A (immutable) | ✅ | N/A |
| Inventory Movements (ledger) | Employee | ✅ | N/A | N/A | N/A | ✅ | N/A |
| Stock Adjustment | Owner | ❌ (form not reachable) | ❌ | N/A | N/A | N/A | N/A |
| Stock Adjustment | Employee | ✅ | 🔶 (hard-blocked if "keluar" would push stock negative) | N/A | N/A | N/A | N/A |
| EOQ | Owner | ✅ | N/A | N/A | N/A | ✅ | ❌ |
| EOQ | Employee | ✅ | N/A | N/A | N/A | ✅ | ✅ (Apply) |
| Safety Stock | Owner | ✅ | N/A | N/A | N/A | ✅ | ❌ |
| Safety Stock | Employee | ✅ | N/A | N/A | N/A | ✅ | ✅ (Apply) |
| Reorder Point | Owner | ✅ | N/A | N/A | N/A | ✅ | ❌ |
| Reorder Point | Employee | ✅ | N/A | N/A | N/A | ✅ | ✅ (Apply) |
| ABC Analysis | Owner | ✅ | N/A | N/A | N/A | ✅ | N/A |
| ABC Analysis | Employee | ✅ | N/A | N/A | N/A | ✅ | N/A |
| Reports | Owner | ✅ | ✅ (Generate — shared, PRD 6.6) | N/A | 🔶 (open item, Section 19 #7 — not yet finalized) | ✅ (Download) | N/A |
| Reports | Employee | ✅ | ✅ (Generate) | N/A | 🔶 (same open item) | ✅ (Download) | N/A |
| User Management | Owner | ✅ | ✅ | ✅ | ✅ (deactivate, soft) | ✅ | N/A |
| User Management | Employee | ❌ (page not rendered) | ❌ | ❌ | ❌ | ❌ | N/A |
| Settings | Owner | ✅ | N/A | ✅ | N/A | N/A | N/A |
| Settings | Employee | ❌ (page not rendered) | N/A | ❌ | N/A | N/A | N/A |

### 20.3 Per-Module Capability Mapping (for `config/capabilities.php` and Policy classes)

Following SAD Section 6's design (capability-map + one Policy class per module, checked via `$user->hasCapability('key')` rather than inline role checks):

| Module | Suggested Policy Class | Capability Key | Owner | Employee | Notes |
|---|---|---|---|---|---|
| Suppliers | `SupplierPolicy` | `suppliers.view` | ✅ | ✅ | |
| | | `suppliers.create` | ❌ | ✅ | |
| | | `suppliers.update` | ❌ | ✅ | |
| | | `suppliers.delete` | ❌ | ✅ | Policy method should check for existing `bahan_baku.supplier_id` references and deny (return false, not just hide) even for Employee |
| | | `suppliers.export` | ✅ | ✅ | |
| Raw Materials | `RawMaterialPolicy` | `rawmaterials.view` | ✅ | ✅ | |
| | | `rawmaterials.create` | ❌ | ✅ | |
| | | `rawmaterials.update` | ❌ | ✅ | `stok_saat_ini` field excluded from the update payload at the policy/form-request level, not just hidden client-side |
| | | `rawmaterials.delete` | ❌ | ✅ | Deny at policy level if `bom` has a row with this `bahan_baku_id` |
| | | `rawmaterials.export` | ✅ | ✅ | |
| Finished Goods | `FinishedGoodPolicy` | `finishedgoods.view` | ✅ | ✅ | |
| | | `finishedgoods.create` | ❌ | ✅ | |
| | | `finishedgoods.update` | ❌ | ✅ | Same `stok_saat_ini` exclusion as Raw Materials |
| | | `finishedgoods.delete` | ❌ | ✅ | Deny if referenced by `bom` or `production_entries` |
| | | `finishedgoods.export` | ✅ | ✅ | |
| Bill of Materials | `BomPolicy` | `bom.view` | ✅ | ✅ | |
| | | `bom.create` | ❌ | ✅ | Includes uniqueness check: one `(finished_goods_id, bahan_baku_id)` pair per BOM |
| | | `bom.update` | ❌ | ✅ | |
| | | `bom.delete` | ❌ | ✅ | Line removal — informational warning only if used in a past Production Entry, per Decision 2 (immutable mutation history already protects historical accuracy) |
| | | `bom.export` | ✅ | ✅ | |
| Purchase Orders | `ProcurementPolicy` (named in SAD §6) | `procurement.view` | ✅ | ✅ | |
| | | `procurement.create` | ❌ | ✅ | Covers both Rutin and Darurat |
| | | `procurement.cancel` | ❌ | ✅ | Guard clause: only when `status = Menunggu` |
| | | `procurement.advance_status` | ❌ | ✅ | This is the "Approve" ability; on transition to `Diterima` this method must also trigger the paired `mutasi_stok` write — implement as one transactional service call, not a bare status update |
| | | `procurement.export` | ✅ | ✅ | |
| Production Entries | `ProductionPolicy` | `production.view` | ✅ | ✅ | |
| | | `production.record` (named in Domain Model Revision §4.3) | ❌ | ✅ | Policy should re-validate server-side that no BOM line goes negative even though the UI already blocks it — never trust client-side validation alone for a stock-integrity rule |
| | | `production.export` | ✅ | ✅ | |
| Inventory Movements | `StockMutationPolicy` (named in SAD §6) | `stock.view` | ✅ | ✅ | Applies to the ledger and both stock-overview screens |
| | | `stock.export` | ✅ | ✅ | |
| Stock Adjustment | `StockMutationPolicy` | `stock.mutate` | ❌ | ✅ | Same policy class as the ledger's `view` — the class already owns "who can change stock," per SAD's rationale for keeping this in one place |
| EOQ / Safety Stock / Reorder Point | `ParameterPolicy` (named in SAD §6) | `parameter.view` | ✅ | ✅ | Covers Overview + Simulation for all three |
| | | `parameter.simulate` | ✅ | ✅ | Simulation itself doesn't persist anything, so both roles can freely explore |
| | | `parameter.apply` | ❌ | ✅ | The only write in this policy; overwrites the single active parameter set (ADR-004) and must write an `audit_logs` row in the same transaction |
| | | `parameter.export` | ✅ | ✅ | |
| ABC Analysis | *(no dedicated policy — Gate-free, auth-only)* | `abc.view` | ✅ | ✅ | Fully system-computed (PRD 9); no write ability exists for either role, so a full Policy class would be overhead — a plain auth-middleware check is sufficient |
| | | `abc.export` | ✅ | ✅ | |
| Reports | `ReportPolicy` | `reports.view` | ✅ | ✅ | |
| | | `reports.generate` | ✅ | ✅ | The one fully shared write ability in the system (PRD 6.6) |
| | | `reports.download` | ✅ | ✅ | Always via a signed/temporary URL (SAD §7.1) — never expose the raw storage path |
| | | `reports.delete` | 🔶 | 🔶 | Not yet implemented; Section 19 open item #7 — leave the Gate defined but unused, or return `false` unconditionally, until retention policy is confirmed |
| User Management | `UserPolicy` | `users.view` | ✅ | ❌ | |
| | | `users.create` | ✅ | ❌ | |
| | | `users.update` | ✅ | ❌ | |
| | | `users.deactivate` | ✅ | ❌ | Soft delete only — preserves `dicatat_oleh` referential integrity across `mutasi_stok`, `pesanan_pembelian`, `production_entries` (Section 6.15) |
| Settings | `SettingsPolicy` | `settings.view` | ✅ | ❌ | Covers all three Settings sub-screens |
| | | `settings.update` | ✅ | ❌ | Calculation Parameters update should itself write an `audit_logs` row (old/new value), consistent with how parameter Apply is logged elsewhere |

### 20.4 Middleware / Route-Level Enforcement

Per SAD Section 6 point 4 ("disable in UI **and** block in backend" is non-negotiable): every route above must be wrapped in capability-based middleware, not just a Blade/Livewire `@can` check. Concretely:

- Routes under `/users/*` and `/settings/*` — `middleware(['auth', 'capability:users.view'])` / `capability:settings.view` respectively; an Employee hitting either directly (typed URL, bookmark, stale link) gets a genuine 403, not a redirect-and-hope.
- Mutating routes (`POST`/`PUT`/`PATCH`/`DELETE` for every module marked ❌ under a role above) must return 403 at the controller/Livewire-action level even if a request is somehow crafted to bypass the hidden button — this is the actual security boundary; the UI hiding is a UX nicety on top of it, per Section 1.2's own framing.

---

## 21. Screen Inventory Matrix

35 screens total (Section 4's grouping, restated here as a flat implementation checklist). "CRUD Capability" uses R/C/U/D/🔶 the same way Section 20 does; "Related Business Process" cites the Section 3 user-flow subsection it belongs to.

### 21.1 Authentication

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Login | Authentication | Public (pre-auth) | R (credential check only) | `/login` | High | `users`, `roles` | 3.1 Login & Role-Based Routing |

### 21.2 Dashboard

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Owner Dashboard | Dashboard | Owner | R | `/dashboard` | High | `bahan_baku`, `finished_goods`, `mutasi_stok`, `pesanan_pembelian`, `production_entries` (aggregated) | 3.6 Stock Alerts; PRD §2 |
| Employee Dashboard | Dashboard | Employee | R (+ launches C via quick actions) | `/dashboard` | High | same + `audit_logs` | 3.6 Stock Alerts |

### 21.3 Master Data

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Supplier List | Master Data → Suppliers | Employee, Owner | R (Owner) · R/C/U/🔶D (Employee) | `/suppliers` | Medium | `suppliers` | 3.2 Purchase (supplier as PO input) |
| Supplier Detail/Create/Edit | Master Data → Suppliers | Employee, Owner | R (Owner) · R/C/U/🔶D (Employee) | `/suppliers/create`, `/suppliers/{id}`, `/suppliers/{id}/edit` | Medium | `suppliers`, `bahan_baku` (linked) | 3.2 Purchase |
| Raw Material List | Master Data → Raw Materials | Employee, Owner | R (Owner) · R/C/🔶U/🔶D (Employee) | `/raw-materials` | High | `bahan_baku` | 3.2, 3.3, 3.5, 3.6 |
| Raw Material Detail/Create/Edit | Master Data → Raw Materials | Employee, Owner | R (Owner) · R/C/🔶U/🔶D (Employee) | `/raw-materials/create`, `/raw-materials/{id}`, `/raw-materials/{id}/edit` | High | `bahan_baku`, `bom`, `mutasi_stok` (recent), parameter fields | 3.5 EOQ/SS/ROP; 3.3 Production |
| Finished Goods List | Master Data → Finished Goods | Employee, Owner | R (Owner) · R/C/🔶U/🔶D (Employee) | `/finished-goods` | High | `finished_goods` | 3.3 Production |
| Finished Goods Detail/Create/Edit | Master Data → Finished Goods | Employee, Owner | R (Owner) · R/C/🔶U/🔶D (Employee) | `/finished-goods/create`, `/finished-goods/{id}`, `/finished-goods/{id}/edit` | High | `finished_goods`, `bom`, `production_entries` (recent) | 3.3 Production |
| BOM List | Master Data → BOM | Employee, Owner | R (Owner) · R/C (Employee) | `/bom` | High | `bom`, `finished_goods` | 3.3 Production |
| BOM Editor | Master Data → BOM | Employee, Owner | R (Owner) · R/C/U/🔶D (Employee) | `/bom/{finished_goods_id}/edit` | High | `bom`, `bahan_baku`, `finished_goods` | 3.3 Production |

### 21.4 Purchasing

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Purchase Order List | Purchasing | Employee, Owner | R (Owner) · R/C/U-status (Employee) | `/purchase-orders` | High | `pesanan_pembelian` | 3.2 Purchase |
| Purchase Order Detail | Purchasing | Employee, Owner | R (Owner) · R/U-status/🔶D-cancel (Employee) | `/purchase-orders/{id}` | High | `pesanan_pembelian`, `mutasi_stok` (linked) | 3.2 Purchase |
| Create Purchase Order (Rutin) | Purchasing | Employee only | C | `/purchase-orders/create?type=rutin` | High | `pesanan_pembelian`, `bahan_baku`, `suppliers` | 3.2 Purchase — Routine |
| Create Purchase Order (Darurat) | Purchasing | Employee only | C | `/purchase-orders/create?type=darurat` | High | `pesanan_pembelian`, `bahan_baku`, `suppliers` | 3.2 Purchase — Emergency; 3.6 Stock Alerts |

### 21.5 Production

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Production Entry List | Production | Employee, Owner | R (Owner) · R/C (Employee) | `/production-entries` | High | `production_entries` | 3.3 Production |
| Create Production Entry | Production | Employee only | 🔶C (hard-blocked on insufficient stock) | `/production-entries/create` | High | `production_entries`, `bom`, `bahan_baku`, `finished_goods`, `mutasi_stok` | 3.3 Production & Automatic BOM Deduction |

### 21.6 Inventory

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Raw Material Stock Overview | Inventory | Employee, Owner | R (both; Employee reaches C via linked actions) | `/inventory/raw-materials` | High | `bahan_baku` | 3.4 Manual Mutation; 3.6 Stock Alerts |
| Finished Goods Stock Overview | Inventory | Employee, Owner | R | `/inventory/finished-goods` | Medium | `finished_goods` | 3.3 Production; 3.4 Manual Mutation |
| Inventory Movements | Inventory | Employee, Owner | R only (immutable) | `/inventory/movements` | High | `mutasi_stok`, `pesanan_pembelian`, `production_entries` | 3.2, 3.3, 3.4 (single ledger, all origins) |
| Stock Adjustment | Inventory | Employee only | 🔶C (hard-blocked if resulting stock < 0) | `/inventory/adjustments/create` | High | `mutasi_stok`, `bahan_baku`, `finished_goods` | 3.4 Manual Mutation |

### 21.7 Inventory Optimization

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| EOQ Overview | Inventory Optimization | Employee, Owner | R | `/optimization/eoq` | Medium | `bahan_baku`, parameter fields | 3.5 EOQ/SS/ROP |
| EOQ Simulation | Inventory Optimization | Employee, Owner | R (Owner) · R/U-Apply (Employee) | `/optimization/eoq/{bahan_baku_id}/simulate` | Medium | `bahan_baku` (parameter fields), `audit_logs` | 3.5 EOQ/SS/ROP |
| Safety Stock Overview | Inventory Optimization | Employee, Owner | R | `/optimization/safety-stock` | Medium | `bahan_baku`, `mutasi_stok` (SD input) | 3.5 EOQ/SS/ROP |
| Safety Stock Simulation | Inventory Optimization | Employee, Owner | R (Owner) · R/U-Apply (Employee) | `/optimization/safety-stock/{bahan_baku_id}/simulate` | Medium | `bahan_baku`, `mutasi_stok`, `audit_logs` | 3.5 EOQ/SS/ROP |
| Reorder Point Overview | Inventory Optimization | Employee, Owner | R | `/optimization/reorder-point` | High (drives critical-stock alerting) | `bahan_baku` | 3.5 EOQ/SS/ROP; 3.6 Stock Alerts |
| Reorder Point Simulation | Inventory Optimization | Employee, Owner | R (Owner) · R/U-Apply (Employee) | `/optimization/reorder-point/{bahan_baku_id}/simulate` | Medium | `bahan_baku`, `audit_logs` | 3.5 EOQ/SS/ROP |
| ABC Analysis Report | Inventory Optimization | Employee, Owner | R only | `/optimization/abc-analysis` | Medium | `bahan_baku`, `mutasi_stok` (aggregated, cached) | PRD §9; SAD §7.2 |

### 21.8 Reports

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Report Generator | Reports | Employee, Owner | C (shared) | `/reports/generate` | Medium | Cross-cutting query over `bahan_baku`, `finished_goods`, `mutasi_stok`, `pesanan_pembelian`; output row in report-metadata table (assumed) | 3.7 PDF Report Generation |
| Report History | Reports | Employee, Owner | R + download (shared) | `/reports/history` | Medium | Report-metadata table (assumed, e.g. `laporan_dibuat`) | 3.7 PDF Report Generation |

### 21.9 User Management (Owner only)

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| User List | User Management | Owner only | R/C/U/🔶D (deactivate) | `/users` | Low | `users`, `roles` | Administrative (no Section 3 flow) |
| User Detail/Create/Edit | User Management | Owner only | R/C/U/🔶D (deactivate) | `/users/create`, `/users/{id}/edit` | Low | `users`, `roles`, `audit_logs` | Administrative |

### 21.10 Settings (Owner only)

| Screen Name | Parent Module | User Role(s) | CRUD Capability | Navigation Path | Priority | Related DB Tables | Related Business Process |
|---|---|---|---|---|---|---|---|
| Company Profile & PDF Letterhead | Settings | Owner only | R/U | `/settings/company-profile` | Low | Settings table (assumed — not specified in Database Design Document; flag for schema team) | SAD Q8 (pending) |
| Calculation Parameters | Settings | Owner only | R/U | `/settings/calculation-parameters` | Low | Settings table (assumed) | Section 0.3; feeds 3.5 EOQ/SS/ROP defaults |
| Notification Preferences | Settings | Owner only | R/U | `/settings/notification-preferences` | Low | Settings table (assumed) | SAD §7.4; SAD Q7 (pending) |

---

## 22. UI States

### 22.1 How to Read This Section

Rather than repeat ten states across 35 near-identical screens, states are defined once per **screen archetype** (22.2–22.8), then Section 22.9 maps every screen from Section 21 to its archetype. Where a screen's states genuinely differ from its archetype's default, that's called out in Section 22.9's Notes column rather than silently inherited. All states below extend, rather than repeat, the empty-state text (Section 6), modal specs (Section 9), and toast rules (Section 10) already defined above — those are cited by reference, not rewritten.

### 22.2 Archetype A — List/Table Screens

*Applies to: Supplier, Raw Material, Finished Goods, BOM, Purchase Order, Production Entry, Inventory Movements, EOQ/SS/ROP Overview, ABC Analysis, Report History, User lists.*

| State | Expected User Experience |
|---|---|
| Initial Loading | Header, sidebar, page title, and primary action button render immediately; only the table body area is pending. |
| Skeleton Loading | Table skeleton: 5–8 placeholder rows matching the real column widths (shimmer animation), so layout doesn't jump when real data arrives. |
| Empty State | Module-specific message + icon, exactly as defined per module in Section 6, plus the primary action inline (e.g., "+ Tambah Bahan Baku") so a first-run empty table isn't a dead end. |
| No Search Results | Distinct from Empty State: "Tidak ada hasil untuk '{query}'" + a "Hapus pencarian/filter" link; toolbar and headers stay visible — only the table body swaps, so the user doesn't lose their place. |
| Validation Error | N/A at the list level itself; row-level action failures (e.g., blocked delete) surface via the Delete Blocked modal (Section 9), not an inline table error. |
| Success State | Toast (Section 10) + the affected row updates in place (edit) or appears at its correct sorted position (create) — never a full table reload/flash. |
| Permission Denied | Owner: mutating controls (Add/Edit/Delete buttons) simply don't render — a reduced view, not a blocked page. A genuine 403 page (icon + "Anda tidak memiliki akses ke halaman ini" + link back to Dashboard) is reserved for whole-module boundary violations — e.g., Employee typing `/users` directly — and is logged as an unauthorized-access audit event. |
| Offline/Error State | Failed data fetch → inline error banner replaces the table body ("Gagal memuat data. [Coba Lagi]"), sidebar/header remain interactive; visually distinct (icon + gray/red tone) from Empty State so the two are never confused. |
| Delete Confirmation | Per Section 9's Delete Confirmation / Delete Blocked modals — not re-specified here. |
| Processing State | Row-level inline spinner replaces the action icon during a delete/status-change request; the row is temporarily disabled to prevent double-submission. |

### 22.3 Archetype B — Detail/Create/Edit Form Screens

*Applies to: Supplier, Raw Material, Finished Good Detail/Create/Edit, BOM Editor, Create Purchase Order (Rutin/Darurat), Stock Adjustment, User Create/Edit, Settings sub-screens.*

| State | Expected User Experience |
|---|---|
| Initial Loading | Create: form renders immediately at default values, no fetch needed. Edit/Detail: page shell renders while the record loads. |
| Skeleton Loading | Form-field skeleton (label + gray bar per field) mirroring the real layout, per Section 6.16's precedent. |
| Empty State | N/A for the form itself; applies to any embedded related-list panel (e.g., Raw Material Detail's "used in these BOMs" list) using a scaled-down version of Archetype A's empty state. |
| No Search Results | Applies only to embedded searchable pickers (material/supplier/finished good selects): "Tidak ditemukan '{query}'" inside the dropdown; no page-level change. |
| Validation Error | Inline field-level error on blur + red border, plus the failed-submit summary banner ("Periksa kembali {n} bidang yang bermasalah") per Section 7's shared conventions; focus moves to the first invalid field (Section 14). |
| Success State | Toast (module-specific message, Section 6) + redirect: Create → Detail (read mode); Edit → same Detail (read mode). Never leaves the user on a blank or reset form. |
| Permission Denied | Owner reaching an Employee-only Create/Edit URL directly → 403 page, same treatment as Archetype A. This makes the backend Gate/Policy enforcement visible, not just a hidden button (SAD §6: "disable in UI and block in backend"). |
| Offline/Error State | Failed save → error banner at the top of the form ("Gagal menyimpan. Periksa koneksi Anda dan coba lagi."), all entered data preserved (Section 15) — never a silent failure or cleared form. |
| Delete Confirmation | Section 9 modals, triggered from the Delete action on Detail/List. |
| Processing State | Save button shows a spinner and disables the instant it's clicked (prevents double-submit); all fields become read-only for the duration, consistent with Section 15's "no optimistic updates for stock-affecting forms." |

### 22.4 Archetype C — Create Production Entry (special case of B)

*Applies to: Create Production Entry only — separated out because its compound BOM-explosion-preview behavior doesn't map cleanly onto a plain form.*

| State | Expected User Experience |
|---|---|
| Initial Loading | Form shell renders immediately; the BOM explosion preview area stays empty/hidden until both Finished Good and quantity are entered. |
| Skeleton Loading | The preview area shows its own inline skeleton (a few placeholder rows) while computing — distinct from the outer form, since it re-triggers on every quantity change (Section 6.7). |
| Empty State | Preview area shows a neutral placeholder ("Pilih barang jadi dan jumlah untuk melihat kebutuhan bahan baku") before both inputs are filled. |
| No Search Results | Applies to the Finished Good picker only, same as Archetype B. |
| Validation Error | The system's most important compound state: any insufficient-stock line renders red with an "Insufficient" badge, the summary banner reads "Stok tidak mencukupi untuk {n} bahan baku," and Submit is disabled — not merely styled as disabled — for as long as any line fails (Domain Model Revision, approved hard-block decision). |
| Success State | One toast covering both stock effects (Section 3.3 point 5) + redirect to the new entry's detail or the Production Entry List with it visible at the top. |
| Permission Denied | Owner → 403 on direct navigation; this screen has no Owner-visible entry point at all (Section 4.5). |
| Offline/Error State | If only the explosion-preview computation fails (network/server error, not a validation failure) → inline error confined to the preview area ("Gagal menghitung kebutuhan bahan baku — coba lagi"), rest of the form stays usable. If the final submit fails after passing validation → form-level error banner, values preserved, per Archetype B. |
| Delete Confirmation | N/A — Production Entries are immutable; no delete action exists (Section 4.5). |
| Processing State | Submit button spinner + disabled; the explosion preview locks (read-only) during the atomic two-mutation write so quantity can't change mid-submit. |

### 22.5 Archetype D — EOQ / Safety Stock / Reorder Point Simulation

*Applies to: EOQ, Safety Stock, and Reorder Point Simulation screens (all three share one pattern, per Section 6.10–6.12).*

| State | Expected User Experience |
|---|---|
| Initial Loading | Input panel pre-fills instantly from current parameter values (small payload, near-instant); the comparison table area shows Skeleton until the first computation resolves. |
| Skeleton Loading | Comparison-table skeleton, covering only the network round-trip if computation is server-side (Section 6.10–6.12) — recompute-on-change should feel live, no manual "Calculate" click required. |
| Empty State | N/A — always populated once the material exists. |
| No Search Results | N/A on this screen (search lives one level up, at the Overview screen, per Archetype A). |
| Validation Error | Numeric-range errors (e.g., Z-Factor outside 0–3) show inline under the offending input; the comparison table freezes at its last valid result rather than showing a broken/NaN calculation. |
| Success State | On Apply: toast (Section 6.10–6.12) + the "Nilai Saat Ini" column updates in place to the just-applied value, so the before/after is visible for one beat before the screen settles. |
| Permission Denied | Owner sees the identical screen with Apply replaced by a disabled control + tooltip — a **within-page** disabled state (Section 4.7), not a 403, since Owner genuinely can view and simulate here. |
| Offline/Error State | Failed Apply submission → error toast; the comparison table stays in its pre-Apply state (nothing silently changes) so the user can retry Apply without re-entering simulation inputs. |
| Delete Confirmation | N/A (no delete action); Reset to Defaults uses the lighter confirmation in Section 9, not a destructive pattern. |
| Processing State | Apply button spinner + disabled during the write + audit-log call; input panel stays editable so a failed Apply doesn't lock the user out of retrying with the same inputs. |

### 22.6 Archetype E — Report Generator

| State | Expected User Experience |
|---|---|
| Initial Loading | Report-type cards and date picker render immediately — no server fetch needed to show the form. |
| Skeleton Loading | N/A; generation uses Processing State instead, since it represents real async server work, not a data fetch (Section 6.14). |
| Empty State | N/A (config screen, not a list) — Report History's empty state is covered under Archetype A. |
| No Search Results | N/A. |
| Validation Error | Generate stays disabled until a report type + valid date range (end ≥ start) are chosen; inline error under the date fields if violated on attempted submit. |
| Success State | Progress indicator is replaced by a success state showing the signed download link; the new entry appears at the top of Report History automatically, no manual refresh. |
| Permission Denied | N/A — Report Generator is the one fully shared write action in the system (Section 6.14); no denied path exists for either role here. |
| Offline/Error State | If generation fails server-side (dompdf/storage error) → the progress indicator is replaced by an error state ("Gagal membuat laporan. Coba lagi.") with a retry button, never a silent hang — generation can take several seconds, so the user needs positive confirmation either way. |
| Delete Confirmation | Applies to Report History's per-row delete, if/when Section 19 open item #7 is resolved — Section 9-style modal, not yet finalized. |
| Processing State | The screen's defining state: progress indicator replaces the Generate button in place; the rest of the page stays interactive so the user can navigate away and check History later (Section 3.7 point 3). |

### 22.7 Archetype F — Dashboards (Owner / Employee)

| State | Expected User Experience |
|---|---|
| Initial Loading | Header/sidebar/KPI-card shells render first; each zone (KPI cards, chart, table, feed) reveals independently rather than waiting for the slowest zone. |
| Skeleton Loading | Each zone has its own skeleton shape: KPI = number placeholder; chart = gray donut/bar outline; table = row skeleton; feed = list-item skeleton. |
| Empty State | Recent Activity Feed / Upcoming Reorders panel show a specific message ("Belum ada aktivitas terbaru") for a genuinely new install with zero data. |
| No Search Results | N/A — dashboards have no search box. |
| Validation Error | N/A on the dashboard itself; quick-create actions open their respective Archetype B/C screen, which owns its own validation states. |
| Success State | After a quick-create action completes elsewhere, returning to the dashboard shows the already-refreshed KPI/table value — no manual refresh needed (Section 15). |
| Permission Denied | N/A — both dashboards are role-specific by server-side resolution at login (Section 3.1); there's no wrong-dashboard state to deny. |
| Offline/Error State | If the polling call (Section 3.6) fails, the Critical Stock Table and bell badge hold their last-known values and show a small, non-alarming "Data mungkin belum terbaru" indicator rather than an error banner — a failed poll shouldn't look like a page crash, since the next interval retries automatically. |
| Delete Confirmation | N/A — no delete actions on either dashboard. |
| Processing State | Quick-action buttons (Employee) show a brief disabled/spinner state only while navigating to their target screen — a navigation transition, not a data-mutation state. |

### 22.8 Archetype G — Login

| State | Expected User Experience |
|---|---|
| Initial Loading | Form renders immediately; no fetch required. |
| Skeleton Loading | N/A. |
| Empty State | N/A. |
| No Search Results | N/A. |
| Validation Error | Inline under email/password for client-side format issues; the shared error banner above the fields (Section 17.10) for server-rejected credentials ("Email atau kata sandi salah") — deliberately generic, never confirms which field was wrong. |
| Success State | No toast — success is the redirect itself, straight to the role-resolved dashboard (Section 3.1). |
| Permission Denied | N/A pre-auth; a deactivated account attempting login shows the same generic credentials error rather than "this account is deactivated," to avoid confirming account existence. |
| Offline/Error State | Server/network failure on submit → error banner ("Tidak dapat terhubung ke server. Coba lagi."), worded distinctly from a bad-credentials error since these need different user reactions (retry vs. re-check password). |
| Delete Confirmation | N/A. |
| Processing State | Submit button spinner + disabled; form fields remain visible but non-interactive until the response returns. |

### 22.9 Screen → Archetype Mapping

| Screen | Archetype | Notes |
|---|---|---|
| Login | G | — |
| Owner Dashboard | F | — |
| Employee Dashboard | F | — |
| Supplier List | A | — |
| Supplier Detail/Create/Edit | B | — |
| Raw Material List | A | — |
| Raw Material Detail/Create/Edit | B | Embedded EOQ/SS/ROP mini-panel and "used in these BOMs" list follow Archetype B's embedded-panel empty-state rule |
| Finished Goods List | A | — |
| Finished Goods Detail/Create/Edit | B | Embedded BOM summary and recent-production mini-table follow the same embedded-panel rule |
| BOM List | A | — |
| BOM Editor | B | Line-item add/remove uses inline row states, not a full-page reload, per Section 17.5 |
| Purchase Order List | A | — |
| Purchase Order Detail | B | Status-timeline component adds a "transition confirmation" step (Section 9's Advance PO Status modal) layered on top of Archetype B's Success State |
| Create Purchase Order (Rutin) | B | — |
| Create Purchase Order (Darurat) | B | Urgent visual treatment (amber badge) persists through every state, including Processing |
| Production Entry List | A | — |
| Create Production Entry | C | — |
| Raw Material Stock Overview | A | — |
| Finished Goods Stock Overview | A | — |
| Inventory Movements | A | No Create/Edit/Delete states apply (immutable ledger) — only View-family states are relevant |
| Stock Adjustment | B | No list/detail states apply — this is a pure Create screen; "Permission Denied" here is unusual in that Owner can't even reach the form (View itself is denied, not just Create) |
| EOQ Overview | A | — |
| EOQ Simulation | D | — |
| Safety Stock Overview | A | — |
| Safety Stock Simulation | D | — |
| Reorder Point Overview | A | — |
| Reorder Point Simulation | D | — |
| ABC Analysis Report | A | No Create/Edit/Delete states apply — fully system-computed |
| Report Generator | E | — |
| Report History | A | Delete Confirmation state is currently unspecified (Section 19 open item #7) |
| User List | A | — |
| User Detail/Create/Edit | B | Deactivate uses the Deactivate User modal (Section 9), not the generic Delete Confirmation |
| Company Profile & PDF Letterhead | B | — |
| Calculation Parameters | B | Reset-to-Defaults uses the Reset Calculation Parameters modal (Section 9) |
| Notification Preferences | B | — |

---

## 23. What This Addendum Enables

- **Google Stitch:** Section 21's Navigation Path column gives Stitch a concrete route/screen list to scaffold; Section 22's archetypes give it every visual state (loading, empty, error, success) to design per screen family rather than leaving states as an afterthought.
- **Laravel Livewire:** Section 20.3's capability keys and policy-class names map directly onto `config/capabilities.php` entries, Policy class method stubs, and route middleware — the implementation team can generate boilerplate from this table rather than re-deriving permissions from prose.
- **QA:** Section 22 is a literal test-case checklist per screen (10 states × 35 screens, resolved through 7 archetypes) — QA can verify each archetype once per representative screen and spot-check the rest, rather than re-discovering expected behavior per screen.
- **Future maintenance:** adding a role later (SAD §6's stated extensibility goal — e.g., a future "Supervisor") means adding one column to Section 20's grids and one capability-map entry per module — the structure doesn't need to change.
