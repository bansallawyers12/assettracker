# Module, page speed, and navigation-depth audit

**Document type:** Documentation only — no code or config changes applied  
**Created:** 2026-09-18  
**Application:** Laravel Asset Tracker (`assettracker`)  
**Sources:** Top nav (`layouts/navigation.blade.php`), dashboard quick links, entity/asset show tabs, `routes/web.php`, `resources/js/app.js`, `vite.config.js`, built Vite assets under `public/build/`, controllers noted below, `docs/TECH_UPDATE.md`, `docs/UI_AND_FUNCTIONALITY_ISSUES.md`

---

## Purpose

Record, for later prioritisation:

1. **What modules / pages exist** and how they are reached  
2. **Page speed / optimisation status** (frontend bundle + known server-side hotspots)  
3. **Navigation click depth** — screens that need **two or three (or more) clicks** from the primary chrome

This is **not** a fix plan and **does not** change application behaviour.

### Click-depth rules used here

| Depth | Meaning |
| --- | --- |
| **1 click** | Direct top-nav item, or a single link from the current landing page |
| **2 clicks** | Top nav → hub/list → target **or** Dashboard → quick link → target page |
| **3+ clicks** | Extra step after open (entity/asset tab, bank account, then import / form / detail) |

**Assumptions:** Desktop primary nav; user starts from a typical authenticated page (often Dashboard). Global header search can shortcut depth when the user knows the name — noted separately, not counted as 1-click for discovery.

---

## Executive summary

| Area | Verdict |
| --- | --- |
| Module coverage | Broad: entities, assets, banking, accounting, invoices, documents, compliance, email, reports, admin |
| Top-nav discoverability | Only **5** primary links; most modules sit behind **Dashboard quick links** or **entity/asset tabs** |
| Frontend optimisation | **Not split** — single Vite JS entry (~349 KB) + CSS (~317 KB) on every page; workspace/reconciliation JS ships globally |
| Server-side hotspots | Entity show loads full transaction/invoice sets; Bills & tasks “Due” aggregates then paginates in memory |
| Deepest common flows | Bank statement import / reconciliation, asset leases/tenants, journal create, email reply — typically **3–4 clicks** |

---

## 1. Module inventory (by product area)

| Module | Primary entry routes | UI surface | Notes |
| --- | --- | --- | --- |
| **Dashboard** | `dashboard` | Home: reminders, due items, entity/asset/person lists, Add transaction, quick links | Hub for most portfolio CRUD |
| **Bills & tasks** | `bills-tasks.index` | Tabs: unpaid / income / reminders / due | Top nav — 1 click |
| **Emails** | `emails.*`, `email-templates.*` | Inbox, drafts, upload, reply, templates | Top nav; templates via emails area / workspace |
| **Reports hub** | `financial-reports.index` (+ child report routes) | Card grid → P&L, BS, cash flow, journals, portfolio, compliance, etc. | Top nav “Reports” |
| **Portfolio (property)** | `portfolio.index`, `assets.financials` | Property P&L / yield report | Top nav “Portfolio” (report, not module hub) |
| **Business entities** | `business-entities.*` | Index, create, show (multi-tab workspace) | Via Dashboard quick link / list |
| **Assets** | `assets.index`, `business-entities.assets.*` | All-assets index; entity-scoped show with many tabs | Via Dashboard or entity Assets tab |
| **Persons** | `persons.*`, entity persons workspace | Firm person register + person show (banks/roles) | Via Dashboard |
| **Bank accounts** | `bank-accounts.*`, entity bank tabs | Portfolio banks + entity operating accounts, import, statements | Via Dashboard or entity Bank Accounts tab |
| **Transactions** | `transactions.index`, entity `#tab_transactions` | Global list + entity tab + bank transaction panels | Via Dashboard |
| **Chart of accounts** | `chart-of-accounts.*` | Firm-wide CoA | Via Dashboard |
| **Vendors** | `vendors.*` | Vendor workspace index | Via Dashboard |
| **Invoices** | `invoices.index`, entity/asset invoice tabs | Global + entity + asset | Via Dashboard or tabs |
| **Commitments** | `commitments.index`, report route | Dashboard card + Reports register | Mixed entry |
| **Documents** | Entity/asset `#tab_documents` + workspace AJAX | Category/slot workspace | Tab only (no top nav) |
| **Compliance** | Entity/asset `#tab_compliance` + workspace | Checklist / lodgement tracking UI | Tab only |
| **Manual journals** | `financial-reports.journal-entries.*` | Hub under Reports | 2 clicks via Reports |
| **Rent invoices** | `business-entities.rent-invoices.*` | Entity-scoped rent flows | Via entity invoices / rent tools |
| **Admin users** | `admin.users.*` | Manage / create users | User menu (admin only) — 2 clicks |
| **Profile / 2FA** | `profile.edit`, `two-factor.*` | Account settings | User menu — 2 clicks |
| **Auth** | login, password, 2FA challenge | Guest | Outside app chrome |

Rough route count: **~339** application routes (including POST/PATCH/DELETE and workspace JSON).

---

## 2. Primary navigation map

### 2.1 Top bar (desktop / mobile)

| Label | Route | Depth from any page |
| --- | --- | --- |
| Dashboard | `dashboard` | **1** |
| Bills & tasks | `bills-tasks.index` | **1** |
| Emails | `emails.index` | **1** |
| Reports | `financial-reports.index` | **1** |
| Portfolio | `portfolio.index` | **1** |

**Not in top nav:** Entities, Assets, Persons, Bank accounts, Transactions, Chart of accounts, Vendors, Invoices, Commitments, Documents, Compliance, Admin.

### 2.2 Dashboard as secondary hub

Dashboard quick links (each is **2 clicks** if starting from another top-nav page: e.g. Reports → Dashboard → link; **1 click** if already on Dashboard):

- New business entity / New asset / New person  
- Chart of accounts  
- Bank accounts  
- All transactions  
- Vendors  
- Invoices  
- Plus list “View all” for entities / assets / persons  

### 2.3 Entity show tabs (after entity is open)

Opened from Dashboard list / global search / entity index — then **+1 tab click**:

| Tab | Hash / area |
| --- | --- |
| Assets | `#tab_assets` |
| Persons | `#tab_persons` |
| Documents | `#tab_documents` |
| Compliance | `#tab_compliance` |
| Notes | `#tab_notes` |
| Contact lists | `#tab_contact_lists` |
| Compose email / Emails | `#tab_compose_email`, `#tab_emails` |
| Bank accounts | `#tab_bank_accounts` (alias `#tab_bank_import` → bank accounts) |
| Transactions | `#tab_transactions` |
| Invoices | `#tab_invoices` |
| Financial reports (partial) | `#tab_financial_reports` |

### 2.4 Asset show tabs (after asset is open)

| Tab | Notes |
| --- | --- |
| Details / Registration / Insurance / Service | Vehicle-oriented |
| Tenants / Leases / Financials / Invoices | Property-oriented |
| Transactions / Documents / Compliance / Notes / Reminders / Emails | Shared |

---

## 3. Click-depth catalogue (discovery path)

Paths assume **start = Dashboard** unless noted. “Via Reports hub” means start from Reports top nav.

### 3.1 One click (top nav or already on Dashboard)

| Destination | Path |
| --- | --- |
| Dashboard | Top nav |
| Bills & tasks | Top nav |
| Emails inbox | Top nav |
| Reports hub | Top nav |
| Property portfolio report | Top nav “Portfolio” |
| Entity / asset / person / CoA / banks / txns / vendors / invoices **indexes** | Dashboard quick link (when already on Dashboard) |

### 3.2 Two clicks

| Destination | Typical path |
| --- | --- |
| Entities / assets / persons / CoA / banks / txns / vendors / invoices indexes | Other page → **Dashboard** → quick link |
| Specific P&L / Balance sheet / Cash flow / Account transactions / Entity summary | **Reports** → report card |
| Journal entries register | **Reports** → Journal entries |
| Tracking categories report | **Reports** → Tracking categories |
| Asset summary / Car register / Commitments report / Compliance gaps / ATO lodgements | **Reports** → card |
| Property portfolio (alternate) | **Reports** → Property portfolio (also 1-click via Portfolio nav) |
| Email templates workspace | **Emails** → templates UI |
| Email drafts / upload | **Emails** → sub-nav |
| Admin manage users / create user | Avatar menu → link |
| Profile | Avatar menu → Profile |
| Closed entities list | Entities index → Closed (or equivalent list filter) |
| Entity show (default Assets tab) | Dashboard → entity row |
| Asset show (default Details) | Dashboard → asset row **or** Entities → open entity → Assets tab → asset (**3** if via entity) |
| Person show | Dashboard → person row |
| Commitments index | Dashboard commitments card |

### 3.3 Three clicks (common)

| Destination | Typical path | Why deep |
| --- | --- | --- |
| Entity **Bank accounts** tab | Dashboard → entity → `#tab_bank_accounts` | Tab not top-level |
| Entity **Transactions / Invoices / Documents / Compliance / Notes / Contact lists / Emails** | Dashboard → entity → tab | Tab |
| Entity bank **Import / reconciliation panel** | Dashboard → entity → Bank accounts → open account / import UI | Nested under bank |
| Entity **create invoice** | Dashboard → entity → Invoices → Create | Form after tab |
| Asset **Tenants / Leases / Financials / Invoices / Documents / Compliance** | Dashboard → asset → tab | Tab |
| Asset lease create / tenant create | Dashboard → asset → Leases/Tenants → Create | Form after tab |
| Asset financials detail report | Dashboard → asset → Financials (or Reports path) | Tab or report |
| Bank **statement list / download** | Banks → account → Statements | Nested |
| Bank **transactions page** | Banks → account → Transactions | Nested |
| Manual journal **create** | Reports → Journal entries → Create | Hub then form |
| Email **reply** | Emails → message → Reply | Detail then action |
| Record invoice payment / unpost | Invoices list → invoice show → action | Detail |
| Person **bank account** form | Dashboard → person → bank workspace | Nested |
| Vendor edit (workspace) | Dashboard → Vendors → edit panel | Index then panel |

### 3.4 Four+ clicks (deepest frequent workflows)

| Destination | Typical path |
| --- | --- |
| Apply / match bank statement lines | Dashboard → entity → Bank accounts → Import/Change panel → match/create |
| Rent invoice suite / preview | Entity → invoices/rent tools → preview/suite steps |
| Entity person officer edit | Entity → Persons tab → person detail → edit |
| Tracking sub-category edit | Entity tracking (or report) → category → sub-category edit |
| Contact list edit | Entity → Contact lists → list → edit |
| Asset move-to-trust | Asset → action → form |
| Compliance file upload for a year slot | Entity/Asset → Compliance → slot → upload |

### 3.5 Mobile note

On `< sm` viewports, opening the hamburger is an **extra click** before any top-nav destination (effectively +1 to all top-nav depths).

---

## 4. Page speed and optimisation status

### 4.1 Frontend optimisation status

| Item | Status | Evidence |
| --- | --- | --- |
| Vite pipeline | **Done** | Single `@vite` — `app.css` + `app.js` |
| Tailwind 4 / Alpine / Tom Select / Flatpickr | **Done** | Bundled via Vite |
| Tiptap rich text | **Partial optimised** | Dynamic `import()` only when `[data-rich-text]` present |
| **Vite page splitting** | **Not started** | `vite.config.js` has only two inputs; TECH_UPDATE Track 1 open |
| Workspace JS scoped to pages | **Not done** | `app.js` always imports documents, compliance, entity/asset/person/vendor/admin workspaces, bank modal, etc. |
| Bank reconciliation JS | **Loaded via bank modal on every page** | `bank-account-modal.js` → `bank-reconciliation.js` (~74 KB source) imported from global boot path |
| Inline Blade scripts | **Still present** | TECH_UPDATE: many views with `<script>` (dashboard, entity/asset show historically large) |
| Flash / toast / confirm consolidation | **Partial** | Some `showWorkspaceConfirm` / `showToast`; not fully migrated |

### 4.2 Measured production build sizes (current `public/build`)

| Asset | Approx size | Loaded when |
| --- | --- | --- |
| `app-*.js` (main entry) | **~349 KB** | Every authenticated layout page |
| `app-*.css` | **~317 KB** | Every page |
| `tiptap-init-*.js` | **~368 KB** | Only when rich-text path loads (dynamic) |

**Implication:** Lightweight screens (e.g. Chart of accounts, Vendors index, Profile) still download and parse the same large JS as Entity show / bank reconciliation.

### 4.3 Largest source modules (always in the main graph unless split later)

| Source file | Approx size | Needed primarily on |
| --- | --- | --- |
| `bank-reconciliation.js` | ~74 KB | Bank import / reconcile |
| `compliance-workspace.js` | ~72 KB | Entity/asset compliance tab |
| `documents-workspace.js` | ~57 KB | Entity/asset documents tab |
| `bank-account-modal.js` | ~44 KB | Bank modals / transactions UI |

(Plus many smaller workspace/init modules always imported from `app.js`.)

### 4.4 Server-side / perceived page speed (by module)

Relative ratings are **code-structure based** (not Lighthouse timings). Scale: **Fast** / **Moderate** / **Slow risk** for large portfolios.

| Page / module | Relative speed | Drivers | Optimisation status |
| --- | --- | --- | --- |
| Login / profile / simple forms | Fast | Small Blade, light queries | OK |
| Chart of accounts index | Fast–Moderate | Firm CoA list | OK structurally |
| Vendors / persons indexes | Moderate | Lists + workspace JS overhead | Frontend split pending |
| Dashboard | Moderate–Slow risk | Multiple reminder/due/entity/asset queries; Add-transaction modal JS | No page-entry split; heavy dashboard Blade |
| Bills & tasks (Due tab) | **Slow risk** | Loads all due reminders/notes/bills/ASIC/commitments then sorts and paginates **in memory** (`BillsTasksController::paginatedDueItems`) | Known issue `bills-001` — not fixed for scale |
| Emails index | Moderate | Gmail sync / message list; depends on mailbox size | Credential-dependent |
| Reports hub | Fast | Mostly static cards | OK |
| P&L / BS / Cash flow / Account transactions | Moderate–Slow risk | GL aggregation across entities/period | Service-heavy; acceptable for reporting |
| Portfolio / asset summary / car register | Moderate–Slow risk | Multi-asset aggregations | Report-scoped |
| **Entity show** | **Slow risk** | Loads **all** entity transactions (with relations), all invoices, all operating banks **with statement entries**, portfolio banks, documents, and unmatched txn queries **per bank** in a loop (`BusinessEntityController@show`) | No tab-level lazy load of transaction lists on first paint |
| Asset show | Moderate–Slow risk | Large Blade + many tabs; workspaces AJAX for docs/compliance | Better than entity for some data, still heavy JS |
| Bank import / reconciliation UI | Moderate–Slow risk | Large JS + statement entry volume | Panel UX improved; payload size remains |
| Invoice show | Moderate | Single invoice + actions | OK |
| Journal create/edit | Moderate | Form + CoA picks | OK |
| Admin users | Fast–Moderate | Small dataset | OK |

### 4.5 Cross-cutting performance issues (documentation register)

| ID | Severity | Summary | Status |
| --- | --- | --- | --- |
| perf-nav-001 | Medium | Most modules require Dashboard hop (2 clicks) — not a byte issue, but slows task switching | Open (IA) |
| perf-fe-001 | Medium | Global `app.js` ships all workspaces (~349 KB built) | Open — TECH_UPDATE Track 1 |
| perf-fe-002 | Medium | Documents + compliance + bank reconciliation JS unused on most pages | Open |
| perf-be-001 | High (large data) | Entity show unbound transaction/invoice loads | Open |
| perf-be-002 | Medium | Bills & tasks Due: full collect then in-memory paginate | Open — `bills-001` |
| perf-be-003 | Low–Medium | Entity show unmatched-transactions loop repeats similar queries per bank | Open |
| perf-ops-001 | Low | Production needs `npm run build` after deploy or UI looks “old/slow” | Operational — PRODUCTION.md |

---

## 5. Module × speed × click depth (summary matrix)

| Module | Top-nav? | Typical clicks to open | Speed risk | Optimisation status |
| --- | --- | --- | --- | --- |
| Dashboard | Yes | 1 | Moderate–Slow | Bundle + multi-query |
| Bills & tasks | Yes | 1 | Slow on Due | In-memory due merge |
| Emails | Yes | 1–2 (reply 3) | Moderate | Global JS |
| Reports hub | Yes | 1 | Fast | OK |
| Individual GL reports | Via hub | 2 | Moderate–Slow | Report services |
| Portfolio report | Yes | 1 | Moderate | OK structurally |
| Entities index | No | 1–2 | Moderate | Global JS |
| Entity show + tab | No | 2–3 | **Slow risk** | Unpaginated loads + global JS |
| Assets index | No | 1–2 | Moderate | Global JS |
| Asset show + tab | No | 2–3 | Moderate–Slow | Tab + JS |
| Persons | No | 1–2 | Moderate | Global JS |
| Bank accounts | No | 1–2 | Moderate | + reconciliation JS |
| Bank import/reconcile | No | **3–4** | Moderate–Slow | Heavy JS |
| Transactions | No | 1–2 | Moderate | Filters |
| Chart of accounts | No | 1–2 | Fast–Moderate | Over-bundled JS |
| Vendors | No | 1–2 | Moderate | Over-bundled JS |
| Invoices | No | 1–2 (show 2–3) | Moderate | OK |
| Documents | Tab only | **3** | Moderate | Workspace JS global |
| Compliance | Tab only | **3** | Moderate | Workspace JS global |
| Journals | Via Reports | 2–3 | Moderate | OK |
| Commitments | Mixed | 1–2 | Moderate | OK |
| Admin users | User menu | 2 | Fast | OK |

---

## 6. Pages that most need fewer clicks (IA candidates — no fix applied)

Highest friction for daily work (combine depth + frequency):

1. **Bank statement import / reconciliation** — usually 3–4 clicks; not in top nav  
2. **Entity accounting tabs** (Bank / Transactions / Invoices) — always +1 tab after entity  
3. **Documents & Compliance** — tab-only; no top-level entry  
4. **Chart of accounts / Vendors / All transactions** — hidden behind Dashboard (2 clicks from Reports/Emails/Bills)  
5. **Journal create** — Reports → Journals → Create  
6. **Asset leases / tenants** — Asset → tab → create  

Optional IA ideas for a future change set (recorded only): add Portfolio/Accounting submenu; pin “Import bank” / “Entities” to top nav; deep-link dashboard widgets to entity tabs with hash.

---

## 7. Pages that most need speed work (candidates — no fix applied)

1. **Entity show** — unbound collections + per-bank unmatched queries  
2. **Global JS bundle** — Vite page entries for documents/compliance/bank/dashboard  
3. **Bills & tasks Due tab** — DB-level pagination / union instead of full memory merge  
4. **Dashboard** — slim queries + extract page JS  

Aligned with existing TECH_UPDATE Track 1 and UI issues `x-001`, `doc-001`, `comp-001`, `bills-001`.

---

## 8. Out of scope / not measured in this pass

- Live browser Lighthouse / Web Vitals numbers (no timed lab run in this audit)  
- Database EXPLAIN / index review  
- Network latency to Gmail / external APIs  
- Mobile layout density beyond hamburger +1 click  
- Applying any Vite split, controller pagination, or nav IA changes  

---

## 9. Related documents

| Doc | Relation |
| --- | --- |
| `docs/TECH_UPDATE.md` | Frontend split / UX helper plan (Track 1–5) |
| `docs/UI_AND_FUNCTIONALITY_ISSUES.md` | Broader issue register (incl. perf IDs) |
| `docs/PRODUCTION.md` | Deploy / `npm run build` operational notes |
| `docs/ACCOUNTING_PNL_AND_BALANCE_SHEET.md` | Report correctness (not speed) |

---

## 10. Change log

| Date | Change |
| --- | --- |
| 2026-09-18 | Initial module inventory, optimisation status, and click-depth audit (documentation only) |

---

*End of document. Do not treat this file as an implementation checklist to execute without a separate change request.*
