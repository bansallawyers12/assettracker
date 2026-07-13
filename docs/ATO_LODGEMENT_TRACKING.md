# ATO Lodgement Tracking — Findings & Proposal

**Date:** 12 July 2026  
**Last reviewed against codebase:** 12 July 2026  
**Context:** Review of Australian Taxation Office (ATO) filing timelines, ASIC annual statement/fees tracking, existing Asset Tracker compliance features, and a proposed portfolio-wide lodgement status report — including support for pending items from past financial years.

---

## Executive Summary

Asset Tracker already has a **compliance workspace** that tracks ITR, BAS, annual accounts, and related documents per entity and financial year, with lodgement statuses (`not_started` → `uploaded` → `lodged` → `paid`).

What is missing is a **portfolio-wide report** that surfaces overdue, missing, and incomplete lodgements across **multiple past years** and obligation types (not only ITR file upload for one FY).

Given potential gaps from prior financial years, the highest-value next step is an **ATO / ASIC Lodgement Status** report that aggregates existing `compliance_document_files` data across entities and FYs — covering ITR, BAS, annual accounts, and **ASIC annual statement / fees** — classifies each obligation (overdue / due soon / lodged / paid / missing), and exports to CSV.

---

## Part 1 — ATO Filing Timelines (Reference)

Australia's financial year typically runs **1 July to 30 June**. Exact due dates are shown on each BAS/notice and may shift when they fall on a weekend or public holiday. Registered tax/BAS agents often receive extended lodgement dates.

> Asset Tracker's FY helpers default to 1 July (`App\Support\FinancialYear`), configurable via `financial.year_start_month` / `financial.year_start_day`.

### GST & BAS (Business Activity Statement)

GST is reported on the BAS, along with PAYG withholding, PAYG instalments, and sometimes other taxes.

#### Quarterly BAS (most common — turnover under $20m)

| Quarter | Period  | Standard due date |
|---------|---------|-------------------|
| Q1      | Jul–Sep | **28 October**    |
| Q2      | Oct–Dec | **28 February**   |
| Q3      | Jan–Mar | **28 April**      |
| Q4      | Apr–Jun | **28 July**       |

**Extensions:**

- **Self-lodge online:** Extra 2 weeks for Q1, Q3, and Q4 (not Q2)
- **Registered BAS/tax agent:** Extended dates for Q1, Q3, Q4 (e.g. 25 Nov, 26 May, 25 Aug for 2025–26)

#### Monthly BAS (turnover $20m+, or elected monthly)

- Due **21st of the following month** (e.g. July BAS → 21 August)

#### Annual GST (voluntary registration, turnover under $75k / $150k NFP)

- Due on the **same date as the taxpayer's income tax return**, when a tax return must be lodged
- **28 February** following the annual tax period, if there is no tax return lodgement obligation

**ATO reference:** [Due dates for lodging and paying your BAS](https://www.ato.gov.au/businesses-and-organisations/preparing-lodging-and-paying/business-activity-statements-bas/due-dates-for-lodging-and-paying-your-bas)

---

### Income Tax Returns (30 June balance date)

#### Individuals & sole traders

| Who lodges           | Due date                                  |
|----------------------|-------------------------------------------|
| Self-lodge           | **31 October**                            |
| Registered tax agent | Often **15 May** (following calendar year) |

Must be on the agent's client list by **31 October** to qualify for extended dates.

#### Companies

| Situation                      | Due date              |
|--------------------------------|-----------------------|
| Self-lodge, good history       | Often **28 February** |
| Prior-year returns outstanding | **31 October**        |
| Via tax agent (most remaining) | Often **15 May**      |

#### Trusts

| Situation                              | Due date        |
|----------------------------------------|-----------------|
| Self-lodge                             | **31 October**  |
| Large/medium trusts (taxable prior yr) | **15 January**  |
| Large/medium trusts (non-taxable)      | **28 February** |
| Most others via tax agent              | **15 May**      |

#### Self-Managed Super Funds (SMSFs)

| Situation                         | Due date         |
|-----------------------------------|------------------|
| Self-prepare                      | **28 February**  |
| Via tax agent                     | **15 May**       |
| New fund or overdue prior returns | **31 October**   |

**ATO reference:** [Income tax due dates](https://www.ato.gov.au/businesses-and-organisations/preparing-lodging-and-paying/reports-and-returns/due-dates-for-lodging-and-paying/due-dates-by-topic/income-tax)

---

### PAYG Withholding

Usually reported on the BAS on the same cycle as GST (monthly or quarterly). Tax must be withheld from wages and contractor payments and remitted by the BAS due date.

---

### Superannuation Guarantee (SG)

#### Before 1 July 2026 (quarterly system)

| Quarter | Payment due    |
|---------|----------------|
| Jul–Sep | 28 October     |
| Oct–Dec | **28 January** |
| Jan–Mar | 28 April       |
| Apr–Jun | **28 July**    |

Missed quarterly payments require an **SGC statement** by the 28th of the second month after the quarter (e.g. missed Oct–Dec → due 28 February).

#### From 1 July 2026 — Payday Super

Employers must pay super **within 7 business days of each payday**. The 28 July 2026 date is the last quarterly super payment (for Apr–Jun 2026).

**ATO reference:** [Key dates for employers in 2026](https://www.ato.gov.au/businesses-and-organisations/small-business-newsroom/key-dates-for-employers-to-remember-in-2026)

---

### Fringe Benefits Tax (FBT)

FBT year: **1 April to 31 March**

| Who lodges                   | Due date    |
|------------------------------|-------------|
| Self-lodge                   | **21 May**  |
| Tax agent (listed by 21 May) | **25 June** |

**ATO reference:** [Lodging your FBT return and paying](https://www.ato.gov.au/businesses-and-organisations/hiring-and-paying-your-workers/fringe-benefits-tax/fbt-registration-lodgment-payment-and-reporting/lodging-your-fbt-return-and-paying)

---

### Other Common ATO Filings

| Obligation                     | When                                     |
|--------------------------------|------------------------------------------|
| **TPAR**                       | **28 August** each year                  |
| **PAYG instalments**           | Quarterly with BAS, or as advised by ATO |
| **STP (Single Touch Payroll)** | Each pay run (ongoing)                   |
| **GST registration**           | Within 21 days of reaching $75k turnover |

---

### Key Upcoming Dates (from July 2026)

| Date             | Obligation                                        |
|------------------|---------------------------------------------------|
| 28 July 2026     | Q4 BAS (Apr–Jun) + final quarterly super          |
| 11 Aug 2026      | Extended online BAS lodgement (Q4)                |
| 25 Aug 2026      | Tax agent BAS concession (Q4)                     |
| 28 August 2026   | TPAR due (2025–26)                                |
| 31 October 2026  | Individual/sole trader tax returns (self-lodge)   |
| 28 February 2027 | Q2 BAS (Oct–Dec 2026); some company/trust returns |

---

## Part 2 — Current State in Asset Tracker

### Project context

Asset Tracker is a Laravel asset-management and accounting platform for Australian business entities. It already includes:

- Multi-type assets (property, vehicles, etc.)
- Business entity management (Company, Trust, Sole Trader, Partnership)
- Double-entry accounting with GST on transactions (`gst_basis`, `gst_amount`; chart account **GST Clearing** / code `2100`; posting category `bas_payments`)
- FY compliance document workspace per entity and asset

### Existing compliance features

The compliance workspace is the primary ATO-adjacent feature:

| Capability | Detail |
|------------|--------|
| FY checklist | Per entity and per asset |
| Document types | ITR, Annual Accounts, BAS (annual **or** Q1–Q4), ASIC, land tax, council rates, water rates, insurance, depreciation |
| Categories | Grouped tabs per year (`Tax & ATO`, `ASIC & Company`, property levies, etc.) via `compliance_categories` |
| Custom slots | Users can add custom categories/slots (`custom_label`, nullable `compliance_document_type_id`) and copy custom rows from a prior year |
| Lodgement fields | `status`, `due_date`, `lodged_date`, `paid_date`, notes, uploaded file |
| Status workflow | `not_started` → `uploaded` → `lodged` → `paid` (API: `entities.compliance-files.status`) |
| Upload behaviour | Upload sets `status = uploaded`; clear file resets to `not_started` and clears `lodged_date` |
| Completeness UI | Workspace completeness counts **uploaded files** for required types — **not** whether status is `lodged`/`paid` |
| Document upload | Storage + bulk upload + auto-filename matching |
| BAS mode | Global config: `annual` or `quarterly` (`config('compliance.bas_mode')` / `COMPLIANCE_BAS_MODE`) — mutually exclusive per year |
| Auto-provision | Opening a workspace FY calls `findOrCreateYearRecord()` and, when enabled, provisions categories/slots |

### Seeded ATO-related document types

From `database/seeders/ComplianceDocumentTypeSeeder.php`:

| Code              | Label                | Scope  | Frequency | Category group   |
|-------------------|----------------------|--------|-----------|------------------|
| `itr`             | Income Tax Return    | entity | annual    | Tax & ATO        |
| `annual_accounts` | Annual Accounts      | entity | annual    | Tax & ATO        |
| `bas_annual`      | BAS (Annual summary) | entity | annual    | Tax & ATO        |
| `bas_q1`          | BAS Q1 (Jul–Sep)     | entity | quarterly | Tax & ATO        |
| `bas_q2`          | BAS Q2 (Oct–Dec)     | entity | quarterly | Tax & ATO        |
| `bas_q3`          | BAS Q3 (Jan–Mar)     | entity | quarterly | Tax & ATO        |
| `bas_q4`          | BAS Q4 (Apr–Jun)     | entity | quarterly | Tax & ATO        |
| `asic_statement`  | ASIC Annual Statement| entity | annual    | ASIC & Company   |

Asset-scoped types (not ATO lodgements, but in the same workspace): `land_tax`, `council_rates`, `water_rates`, `insurance_certificate`, `depreciation_schedule`.

`ComplianceYearService::basTypeEnabled()` shows either annual **or** quarterly BAS slots depending on `compliance.bas_mode` — never both.

### How due dates work today

`ComplianceYearService::dueDateForType()`:

| Scope | Auto `due_date`? |
|-------|------------------|
| **Entity** (ITR, BAS, ASIC, annual accounts) | **No** — always `null` unless set manually in the UI |
| **Asset** `land_tax` | Copies `assets.land_tax_due_date` if present |
| **Asset** `council_rates` | Copies `assets.council_rates_due_date` if present |
| **Asset** `insurance_certificate` | Copies `assets.insurance_due_date` if present |
| **Asset** `water_rates` / others | **No** auto due date |

This means any overdue/due-soon classification for ATO obligations requires either manual due dates or a new due-date calculator (Phase 2).

### Database tables supporting lodgement tracking

| Table | Role |
|-------|------|
| `business_entities` | Entity identity: ABN, ACN, TFN, `asic_renewal_date`, entity type; `exclude_from_financial_reports` excludes from report scope (`forFinancialReports()`) |
| `entity_person` | Directors/roles, `asic_due_date` |
| `compliance_document_types` | Seeded obligation types (ITR, BAS, etc.) |
| `compliance_year_records` | FY scope per entity or asset (`fy_start_date`, `fy_end_date`, optional lock) |
| `compliance_categories` | Grouped checklist tabs per year record |
| `compliance_document_files` | **Core lodgement row:** category, type (nullable for custom), checklist label, status, due/lodged/paid dates, file path, notes |
| `transactions` / `invoices` | GST data for BAS preparation (not lodgement state) |
| `reminders` | Generic deadline reminders (not wired to compliance due dates) |

**Schema notes (post `2026_06_22_000001_add_compliance_categories`):**

- Files belong to a category; uniqueness is by `(compliance_category_id, checklist_label)` (not year+type)
- `compliance_document_type_id` may be **null** for custom slots
- Unique year indexes differ for PostgreSQL (partial) vs MySQL/MariaDB

### Existing reporting

One compliance report exists today:

| Item | Detail |
|------|--------|
| **Route** | `/financial-reports/compliance-gaps` |
| **Route name** | `financial-reports.compliance-gaps` |
| **Hub card** | Financial reports → **Registers & compliance** → “Compliance gaps” |
| **Title** | Compliance gaps — missing ITR |
| **Scope** | One selected FY at a time (FY picker; defaults to current FY; years from `ComplianceYearService::listAvailableYears()`, typically last 10) |
| **Entity scope** | All reporting entities or selected IDs (`ResolvesReportEntityScope`) |
| **Definition of “missing”** | No `compliance_year_record` for that entity/FY, **or** no ITR file with a non-null `path` |
| **Does not check** | `status` (`lodged`/`paid`), `due_date`, BAS, annual accounts, ASIC |
| **Export** | CSV (`Entity`, `Financial year`, `Status`) |

**Key files:**

| File | Purpose |
|------|---------|
| `app/Services/ComplianceReportService.php` | `missingItrReport()` |
| `app/Http/Controllers/ComplianceReportController.php` | Report controller + CSV export |
| `resources/views/compliance-reports/missing-itr.blade.php` | Report view |
| `resources/views/financial-reports/index.blade.php` | Hub card under “Registers & compliance” |
| `app/Services/ComplianceYearService.php` | FY list, find/create, provision slots, due dates, BAS mode filter, completeness |
| `app/Http/Controllers/ComplianceWorkspaceController.php` | Per-entity/asset compliance UI API |
| `app/Http/Controllers/ComplianceController.php` | Upload / clear / bulk / auto-match / stream |
| `app/Services/ComplianceUploadService.php` | Upload/clear status transitions |
| `resources/views/business-entities/partials/compliance-workspace.blade.php` | Workspace UI |
| `resources/js/compliance-workspace.js` | Workspace front end |
| `config/compliance.php` | Years shown, BAS mode, auto-provision, year lock, upload limits |
| `database/migrations/2026_06_21_000001_create_compliance_document_tables.php` | Core tables + type seeder |
| `database/migrations/2026_06_22_000001_add_compliance_categories.php` | Categories, labels, custom slots |

### Report generation pattern (to reuse)

1. Controller resolves entity scope via `ResolvesReportEntityScope` trait
2. Service builds a data array
3. Blade view uses `<x-report-shell>` + `<x-report-entity-scope-picker>`
4. Hub route under `/financial-reports/*` + card in `financial-reports/index.blade.php` (“Registers & compliance”)
5. CSV export where implemented
6. FY handling via `app/Support/FinancialYear.php` (default 1 July start)

### Provisioning behaviour (important for past-year reports)

- Slots are created when a compliance workspace FY is opened (`auto_provision_on_view`, currently hardcoded `true` in config)
- Entities/years never opened have **no** `compliance_year_records` / files
- The missing-ITR report already treats “no year record” as missing
- A multi-year lodgement report should treat absent FY records as gaps **without** auto-creating rows on every report load (unless an explicit “ensure years” action/command is run)

---

## Part 3 — Gaps

| Gap | Impact |
|-----|--------|
| **No ATO integration** | No API, prefill, or validation against ATO systems |
| **Document checklist, not lodgement engine** | No lodgement reference numbers, amendments, or payment amounts |
| **No ATO deadline automation** | Entity-level ITR/BAS/ASIC get no auto `due_date` (`dueDateForType()` returns null for entity scope) |
| **Global BAS mode** | Annual vs quarterly is app-wide (`config/compliance.php`), not per entity |
| **Thin reporting** | Only “missing ITR file” for one FY; no overdue BAS, ASIC fee status, multi-year scan, or lodgement-status view |
| **Upload ≠ lodged** | Existing report and workspace completeness care about file `path`; lodgement/payment status is separate and underused in reporting |
| **No ATO reminders** | `reminders` table exists but is not wired to BAS/ITR due dates |
| **GST ≠ lodgement** | GST clearing supports BAS prep in GL; lodgement state lives only in compliance files |
| **Missing tax types** | No FBT, TPAR, PAYG instalment, STP, or Payday Super obligation types |
| **Past FY records may not exist** | Years never opened in the workspace are invisible until a report treats absence as a gap |
| **No dedicated lodgements table** | Everything maps to `compliance_document_files` (adequate for Phase 1) |
| **Custom slots** | Custom Tax & ATO rows may not map to seeded codes — report should decide whether to include them |

---

## Part 4 — Proposed Report: ATO / ASIC Lodgement Status

### Purpose

Answer portfolio-wide questions that the per-entity compliance tab (and single-FY missing-ITR report) cannot easily surface:

1. **What's overdue?** (past due date, not lodged/paid)
2. **What's missing?** (no FY record, no slot, or still `not_started` / no file)
3. **What's lodged but unpaid?** (`lodged` but no `paid_date`) — including **ASIC annual review fees**
4. **Which entities are at risk?** (e.g. ITR outstanding for FY22–FY24; ASIC statement unpaid)
5. **What's coming up?** (next 30/60/90 days — once due dates exist)

### Suggested route

- Path: `/financial-reports/ato-lodgements`
- Name: `financial-reports.ato-lodgements`
- Hub title: **ATO / ASIC lodgements** (card next to “Compliance gaps” under **Registers & compliance**)

### Obligation types in scope (Phase 1)

| Code | Label | Category | Notes |
|------|-------|----------|-------|
| `itr` | Income Tax Return | Tax & ATO | Always included |
| `bas_annual` **or** `bas_q1`–`bas_q4` | BAS | Tax & ATO | Respects `compliance.bas_mode` (never both) |
| `annual_accounts` | Annual Accounts | Tax & ATO | Included by default; filterable |
| `asic_statement` | ASIC Annual Statement | ASIC & Company | **Included** — covers annual statement lodgement and fee payment via `lodged` / `paid` status |

Custom Tax & ATO / ASIC slots (nullable `compliance_document_type_id`) are **excluded** by default.

Entity applicability: ASIC applies mainly to companies (and other ASIC-registered entities). Phase 1 still surfaces the seeded `asic_statement` slot for every reporting entity that has (or should have) a year record; Phase 3 may add an `asic_registered` / entity-type filter so trusts without ASIC obligations are not flagged.

### Filters

| Filter | Options |
|--------|---------|
| Entity | Single / multi / all reporting entities (`forFinancialReports()`) |
| Financial year range | e.g. FY2020–FY2026 (critical for past-year gaps; contrast with current one-FY picker) |
| Obligation type | ITR, BAS (respect `bas_mode`), annual accounts, **ASIC** |
| Status | Overdue, due soon, lodged unpaid, complete, missing, uploaded |

### Report columns

| Entity | FY | Obligation | Due date | Lodged | Paid | Status | Document | Action |
|--------|-----|------------|----------|--------|------|--------|----------|--------|
| ABC Pty Ltd | 2023-2024 | BAS Q2 | 28 Feb 2024 | — | — | **Overdue** | — | Open workspace |
| XYZ Trust | 2024-2025 | ITR | 31 Oct 2025 | 15 Nov 2025 | — | Lodged, unpaid | ✓ | Open workspace |
| ABC Pty Ltd | 2024-2025 | ASIC Annual Statement | — | 12 Sep 2025 | — | Lodged, unpaid | ✓ | Open workspace |

Use `FinancialYear::label()` format (e.g. `2025-2026`) for consistency with existing reports.

### Summary tiles

- Missing
- Uploaded
- Lodged, unpaid (especially useful for ASIC fees)
- Complete
- Overdue / due soon (only when `due_date` is set — Phase 2 for ATO rules; ASIC may later use `business_entities.asic_renewal_date`)

### Views

1. **Table** — sortable, filterable (matches existing financial report pattern)
2. **Timeline / calendar** — optional later phase
3. **CSV export** — for accountant or internal review

### Past-year gap detection logic

```
For each reporting entity + FY in range:
  For each required obligation (ITR + BAS per compliance.bas_mode + annual_accounts + asic_statement):
    If no compliance_year_record for entity/FY:
      → flag as missing (do not auto-create unless user runs ensure-years)
    Else if no matching compliance_document_file for type code:
      → flag as missing
    Else classify by status / due_date / path (see below)
```

### Status classification (locked for Phase 1)

Apply **first matching** rule in this order:

| Priority | Status | Condition |
|----------|--------|-----------|
| 1 | **Complete** | `status` = `paid`, or (`status` = `lodged` and `paid_date` set) |
| 2 | **Lodged, unpaid** | `status` = `lodged`, no `paid_date` |
| 3 | **Overdue** | effective due date &lt; today and not complete/lodged |
| 4 | **Due soon** | effective due date within next 30 days, not complete/lodged |
| 5 | **Uploaded** | File present (`path`), not lodged/paid |
| 6 | **Missing** | No FY record, no slot, `not_started`, or no file (and not overdue/due soon) |

**Effective due date:** stored `compliance_document_files.due_date` if set; otherwise Phase 2 estimate from `AtoDueDateService` (so overdue still surfaces for never-opened FYs).

**Product note:** Today’s missing-ITR report treats “has file” as OK. This report tracks **lodgement/payment**, with Uploaded kept distinct from Missing. ASIC annual review fees are expected to move Missing → Uploaded → Lodged, unpaid → Complete once the fee is paid.

---

## Part 5 — Implementation Plan

### Phase 1 — Lodgement status report (high value, low risk) ✅

- Extend `ComplianceReportService` with `lodgementStatusReport()`
- Add route `financial-reports.ato-lodgements` and hub card (**ATO / ASIC lodgements**)
- Reuse `<x-report-shell>` and entity scope picker
- Scan **multiple FYs** (range filter; default e.g. last N years from `compliance.years_shown`)
- Include entity-scope seeded types: `itr`, BAS per `bas_mode`, `annual_accounts`, and **`asic_statement`**
- Classify: missing, uploaded, lodged-unpaid, complete; overdue only when `due_date` present
- CSV export
- Link each row to entity compliance workspace (`business-entities.show` + `fy_start` + `#tab_compliance`)
- **Do not** auto-provision year records merely by opening the report

**Estimated touch points:**

- `app/Services/ComplianceReportService.php` — new report method
- `app/Http/Controllers/ComplianceReportController.php` — new action
- `resources/views/compliance-reports/ato-lodgements.blade.php` — new view
- `routes/web.php` — new route
- `resources/views/financial-reports/index.blade.php` — hub card

### Phase 2 — Smarter due dates ✅

- Added `App\Services\AtoDueDateService` with self-lodge defaults:
  - Quarterly BAS: 28 Oct, 28 Feb, 28 Apr, 28 Jul (relative to FY)
  - ITR / annual accounts: 31 October after FY end
  - Annual BAS/GST: same as tax-return due date (31 Oct); 28 February path reserved for when no return is required (Phase 3 flag)
  - ASIC annual statement: anniversary from `business_entities.asic_renewal_date` falling within the FY (null if unset)
- `ComplianceYearService::dueDateForType()` uses the service for **entity** scope; asset slots unchanged
- New slots and existing slots with null `due_date` get dates on provision
- Artisan: `php artisan compliance:backfill-due-dates` (`--dry-run`, `--force`)
- Tax-agent extended dates deferred to Phase 3 (`uses_tax_agent`)

### Phase 3 — Per-entity config & reminders ✅

- Per-entity fields on `business_entities`:
  - `bas_reporting_frequency` (`annual` / `quarterly` / `monthly`; null = app `COMPLIANCE_BAS_MODE`)
  - `uses_tax_agent` — extended ITR (15 May) and BAS agent concession dates
  - `gst_registered` — when false, BAS slots/report rows are omitted
  - `entity_tax_return_required` — when false, ITR slots/report rows are omitted; annual BAS uses 28 Feb path
- ASIC statement only for companies (or entities with ACN / ASIC renewal date)
- `ComplianceReminderService` + `php artisan compliance:sync-reminders` (scheduled daily 06:30) creates 30/14/7-day reminders
- `php artisan compliance:ensure-years` provisions year records/slots for a FY range without opening the UI
- Entity create/edit forms expose the new settings (profile workspace update leaves them unchanged)

### Phase 4 — Additional obligation types (optional)

- FBT return (21 May / 25 June)
- TPAR (28 August)
- PAYG instalment tracking
- Superannuation (quarterly pre-July 2026; Payday Super post-July 2026)

---

## Part 6 — Configuration Reference

### Current compliance config (`config/compliance.php`)

| Config key | Env | Default | Purpose |
|------------|-----|---------|---------|
| `years_shown` | `COMPLIANCE_YEARS_SHOWN` | `10` | FY tabs / year list depth |
| `bas_mode` | `COMPLIANCE_BAS_MODE` | `annual` | Fallback when entity `bas_reporting_frequency` is null |
| `auto_provision_on_view` | *(none — hardcoded)* | `true` | Create categories/slots when workspace FY opened |
| `enable_year_lock` | *(none — hardcoded)* | `false` | Allow locking a FY record |
| `max_kilobytes` / `mimes` / `file_accept` | `DOCUMENTS_MAX_KB` (+ hardcodes) | mirrors documents config | Upload limits |

See `.env.example` for `COMPLIANCE_*` keys.

### Per-entity fields (Phase 3) ✅

| Field | Type | Purpose |
|-------|------|---------|
| `bas_reporting_frequency` | enum nullable | `annual`, `quarterly`, `monthly` (monthly → quarterly slots); null = app default |
| `uses_tax_agent` | boolean | Extended lodgement dates |
| `gst_registered` | boolean | Whether BAS obligations apply |
| `entity_tax_return_required` | boolean | Whether ITR obligation applies |
| *(existing)* `asic_renewal_date` | date | ASIC annual review anniversary — feed due dates for `asic_statement` |

---

## Part 7 — Recommendation

**Build the report.** The compliance workspace is already a lodgement tracker at the entity level. The proposed report mainly:

1. Aggregates existing `compliance_document_files` across entities and FYs (ATO **and** ASIC)
2. Classifies by lodgement status (and due date once Phase 2 exists)
3. Surfaces past-year gaps — including unpaid ASIC annual review fees — that are hard to see entity-by-entity

For entities with pending items from past years, **multi-year range filtering** should be first-class. That is the main upgrade over the existing missing-ITR report, which:

- Covers **one FY at a time** (selectable, defaults to current)
- Only checks **ITR file upload** (`path`), not BAS, ASIC, or lodgement/payment status

---

## Disclaimer

ATO due dates in Part 1 are general reference information, not tax advice. Exact obligations depend on entity type, turnover, lodgement history, and whether a registered tax agent is used. Always verify against [ato.gov.au](https://www.ato.gov.au) and your BAS/notice, or consult a registered tax agent.

---

## Related documentation

- [TECH_UPDATE.md](./TECH_UPDATE.md) — broader technical notes (includes compliance workspace front-end loading notes)
