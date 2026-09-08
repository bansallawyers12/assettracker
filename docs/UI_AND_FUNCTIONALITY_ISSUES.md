# UI and functionality issues — Asset Tracker web application

**Document type:** Audit / issue register (documentation only — no fixes applied)  
**Created:** 2026-09-02  
**Application:** Laravel asset tracker (`assettracker`)  
**Scope:** Complete web application — UI, UX, functionality, data integrity, security, performance

---

## Purpose 

This document records known UI and functionality issues across the Asset Tracker portal so they can be reviewed, prioritised, and addressed in a later change set. It is **not** a fix plan and **does not** modify application behaviour.

Sources used:

- Product README and architecture notes
- `.ai/rules/**` (settled product decisions and traps)
- `docs/ACCOUNTING_PNL_AND_BALANCE_SHEET.md`, `docs/TECH_UPDATE.md`, `docs/ATO_LODGEMENT_TRACKING.md`, `docs/PRODUCTION.md`
- Routes, controllers, Blade views, and `resources/js/**`
- Feature and unit tests (behaviour encoded as assertions)
- Recent production feedback on bank reconciliation (Aug 2026)

Severity legend:

| Level | Meaning |
| --- | --- |
| **High** | Wrong numbers, blocked workflows, security gap, or data loss risk |
| **Medium** | Confusing UX, partial feature, or easy user mistake with material impact |
| **Low** | Cosmetic, legacy URL, or edge case with limited impact |

Issue types: **UI**, **UX**, **Functionality**, **Data**, **Security**, **Performance**

---

## Executive summary

Asset Tracker is a mature Laravel portal (entities, assets, banking, accounting, documents, compliance, email). The stack is modern (Vite, Tailwind 4, Tom Select, Alpine), but several areas create user confusion or silent accounting drift:

1. **Bank reconciliation Change panel** — three different pickers (Match existing / create as type / chart account) are easy to confuse; Tom Select inside hidden panels can appear empty until **Change ▾** is opened and widgets activate.
2. **Loan vs offset vs cash** — loan-purpose accounts use a different import UI and posting rules; loan repayments on the loan ledger do not move GL until offset transfers exist.
3. **Accounting model** — paid-basis P&L, director-funds posting, and reconstructed 2500 balance sheet logic are correct by design but not obvious in the UI.
4. **Access control** — portfolio is firm-shared for reads; `app_role` gates mutations (`viewer` is read-only). Person-held bank accounts can still 403 on edit for other users.
5. **Frontend consolidation** — heavy workspace JS loads on every page; flash/confirm/toast patterns are inconsistent.

---

## 1. Authentication and access

| ID | Type | Sev | Summary | User impact | Evidence | Status |
| --- | --- | --- | --- | --- | --- | --- |
| auth-001 | Security | High | App RBAC was missing (all policies returned true for mutate) | Viewers could mutate firm data | `AppRole`, `users.app_role`, `BusinessEntityPolicy` | **Fixed** — firm-shared read; `staff`/`administrator` mutate; `viewer` read-only. Per-entity ACL still out of scope. |
| auth-002 | UX | Medium | 2FA / session expiry mid AJAX workspace flow | Workspace save failed or redirected without return path | `TwoFactorVerified`, `workspace-panel.js` `apiFetch` | **Fixed** — JSON 401 + `return` URL restored after challenge |
| auth-003 | Functionality | Low | No public registration | New staff must be created by administrator | Admin Users + login copy | **Accepted** — intentional; admin create-user is the supported path |

---

## 2. Business entities

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| entity-001 | UX | Medium | Tenancy / property-manager contacts (`exclude_from_financial_reports`) | P&L, bank, journals, officer roles hidden; users may not understand missing tabs | `EnsuresOperationalBusinessEntity`, `tests/Feature/EntityAccountingNavTest.php` |
| entity-002 | Functionality | Medium | Closed entities block mutations (403) | Bank links, transactions, edits fail on closed entities | `EnsuresOperationalBusinessEntity::ensureNotClosed` |
| entity-003 | UX | Low | No separate Bank Import tab | Import lives per bank account under Bank Accounts; `#tab_bank_import` aliased to bank accounts | `.ai/rules/business-entities.md` |
| entity-004 | Security | Medium | Profile workspace AJAX requires `update` policy | If RBAC added later, profile panel may 403 while show page loads | `EntityShowWorkspaceController.php` |
| entity-005 | UX | Low | Trust appointor vs `entity_person` roles | Appointor belongs on trust company profile, not as normal officer role | `README.md` |

**Recent history:** Entity workspace **403** errors occurred when booking entity context or policy checks did not align with the selected entity (Aug 2026).

---

## 3. Assets

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| asset-001 | Functionality | Medium | Loan and offset accounts must be linked and used correctly | `loan_*` types on offset account rejected; loan economics belong on loan-purpose account | `.ai/rules/loan-offset-transfers.md`, `LoanOffsetTransactionGuard` |
| asset-002 | UX | Medium | Move-to-trust blocked for closed or contact-only entities | Validation error without clear inline guidance | `AssetMoveToTrustService`, tests |
| asset-003 | UX | Low | Tom Select in tenant/lease modals needs reinit | Real-estate company picker may not search until modal opens | `docs/TECH_UPDATE.md` Track 4 |

---

## 4. Bank accounts and statement reconciliation

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| bank-001 | UX | High | Change panel has three mutually exclusive pickers | Users confuse **Match existing** (booked transactions) with **Or create from chart account** (GL) | `reconciliation-panel.blade.php`, `.ai/rules/partials.md`, `bank-reconciliation.js` |
| bank-002 | UX | Medium | Change panel hidden by default; Tom Select deferred | Dropdowns look empty/broken until **Change ▾** opens and `forceActivateTomSelectsIn` runs | `tomselect-init.js`, `bank-reconciliation.js` |
| bank-003 | Functionality | High | Loan-purpose CSV = loan activity, not cash reconciliation | Limited types, no chart-account create, different labels/counts | `.ai/rules/partials.md`, `.ai/rules/loan-offset-transfers.md` |
| bank-004 | Data | High | Loan repayments on loan account post no GL alone | Cash movement expected via offset `internal_transfer`; silent under-reporting if missing | `docs/ACCOUNTING_PNL_AND_BALANCE_SHEET.md` §9, `AuditUnmatchedLoanRepayments` |
| bank-005 | Functionality | Medium | CSV/TXT only — Excel rejected | Users uploading `.xlsx` exports get validation error | `BankAccountImportController`, tests |
| bank-006 | UX | Medium | Column mapping step after upload | Non-Macquarie banks may need manual Date/Description/Amount remap | `bank-reconciliation.js`, CSV mapping UI (Aug 2026) |
| bank-007 | Security | Medium | Person-held bank accounts 403 for non-owner users | Account visible in portfolio list but edit returns Unauthorized | `BankAccount::isAccessibleBy`, `PersonShowWorkspaceController` |
| bank-008 | UX | Medium | Balance sheet entry CTA ≠ Add transaction | Capital/deposits use minimal balance-sheet form; full P&L flow is separate | `.ai/rules/balance-sheet-entries.md` |
| bank-009 | Functionality | Medium | Chart-account create maps to transaction types invisibly | e.g. 2500 → director loan in/out; liability → loan_drawdown | `.ai/rules/services.md`, `BankStatementApplyService` |
| bank-010 | UX | Low | Clear matched/unmatched uses native `confirm()` | Inconsistent with workspace styled confirm dialogs | `bank-reconciliation.js`, `docs/TECH_UPDATE.md` |

### Reconciliation Change panel — intended layout (Aug 2026)

Order (cash/offset accounts only):

1. **Match existing** — unmatched booked `transactions` for the entity (not chart of accounts)
2. **Or create as type** — e.g. Internal Transfer, Rent, Rates
3. **Or create from chart account** — active GL accounts (hidden on loan activity imports)
4. **Create markers** — BAS, flagged, comments

**Observed user confusion (Aug 2026):**

- Empty **Match existing** when no unmatched booked transactions exists (expected for new imports).
- Chart account list empty when Tom Select not activated inside hidden Change panel (mitigated by `forceActivateTomSelectsIn` + `loadChartAccounts()` on open).
- Label changes during Aug 2026 redesign caused mismatch with user mental model; layout reverted to pre-redesign order.

---

## 5. Transactions

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| txn-001 | Functionality | Medium | Statement-linked edit uses reduced form | Date/amount locked; full edit path not obvious | `.ai/rules/transactions.md` |
| txn-002 | UX | Medium | Director funds / cash channel labelling | “Director funds” label; GL posts to 2500 — not obvious | `.ai/rules/director-funds-posting.md` |
| txn-003 | Data | High | Paid-basis P&L — unpaid bills excluded | Expenses understated until marked paid | `docs/ACCOUNTING_PNL_AND_BALANCE_SHEET.md` §9 |
| txn-004 | Data | Medium | `chart_of_account_id` override bypasses type map | Misclassification on P&L/BS except director-loan types | Accounting doc §9 |
| txn-005 | Functionality | Medium | Mixed-GST invoices need manual GST | Auto 10% wrong for mixed-rate lines | `.ai/rules/support.md` |
| txn-006 | UX | Low | Global transactions index vs entity tab | Two entry points with slightly different filters | `routes/web.php` |

---

## 6. Chart of accounts

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| coa-001 | Data | Medium | List `current_balance` / opening fields unused in reports | CoA screen balances ≠ P&L/BS | Accounting doc §2 |
| coa-002 | UX | Medium | 1150 Deposits Paid not in transaction pickers | Property deposits use `asset_purchase` → 1500 | `.ai/rules/balance-sheet-entries.md` |
| coa-003 | Data | Low | Many expense types map to 5900 Other Expenses | Coarse P&L granularity | Accounting doc §9 |
| coa-004 | Functionality | Low | Per-entity CoA CRUD routes removed | Global chart only | `RemovedLegacyRoutesTest` |

---

## 7. Financial reports

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| report-001 | Data | High | Director loan BS line reconstructed, not raw 2500 GL | Account transactions vs BS may differ | `FinancialReportService`, accounting doc §9 |
| report-002 | Data | Medium | Bank/Cash 1100 memo + Unallocated line | Manual journals appear as reconciliation difference | `.ai/rules/services.md`, balance sheet view |
| report-003 | Data | Medium | Heuristic may hide 2500 receivable when bank GL coincidentally matches | Sheet looks balanced but incomplete | Accounting doc §9 Medium #6 |
| report-004 | Data | Medium | Cash flow derived from GL; loan/offset flows non-obvious | Loan interest capitalises without cash movement | Cash flow view, loan-offset rules |
| report-005 | Functionality | Low | Consolidated reports do not eliminate intercompany | Multi-entity consolidation may double-count | Accounting doc §9 |
| report-006 | UX | Low | Report entity scope Tom Select clipping workaround | Dropdown needs body parent positioning | `tomselect-init.js`, `financial-reports-hub.js` |

---

## 8. Invoices

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| inv-001 | Functionality | Medium | `invoice_payment` type hidden from manual pickers | Must use invoice payment UI | `InvoicePaymentPostingTest` |
| inv-002 | UX | Low | Rent reminder uses native `confirm()` | Inconsistent dialog pattern | `assets/partials/invoices-tab.blade.php` |

---

## 9. Documents

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| doc-001 | Performance | Medium | `documents-workspace.js` loaded globally (~690 lines) | Parsed on every authenticated page | `app.js`, `docs/TECH_UPDATE.md` |
| doc-002 | UX | Medium | Bulk upload slot/category mapping is complex | Users must understand categories, slots, replace flags | `DocumentWorkspaceController`, `documents-workspace.js` |
| doc-003 | Functionality | Low | Cannot move documents between entity and asset workspaces | Move returns error when crossing scope | `DocumentWorkspaceController` |

---

## 10. Compliance

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| comp-001 | Performance | Medium | `compliance-workspace.js` loaded globally (~1046 lines) | Same global bundle issue as documents | `app.js`, `docs/TECH_UPDATE.md` |
| comp-002 | Functionality | High | Checklist only — not lodgement engine | Upload ≠ lodged; no ATO integration | `docs/ATO_LODGEMENT_TRACKING.md` |
| comp-003 | Functionality | Medium | Limited compliance reporting | No overdue BAS/ASIC multi-year hub report | ATO lodgement doc |
| comp-004 | Data | Medium | Global BAS mode (annual vs quarterly) | Mixed BAS cycles across entities not modeled per entity | `config/compliance.php` |
| comp-005 | Functionality | Low | Past FY slots absent until workspace opened | Reports must treat missing years as gaps | ATO lodgement doc |

---

## 11. Email

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| email-001 | Functionality | Medium | Gmail sync requires credentials | Email features inert without `.env` setup | `README.md` |
| email-002 | UX | Low | Legacy `alert()` on some compose/reply flows | ~86 native alerts per TECH_UPDATE audit | `docs/TECH_UPDATE.md` |
| email-003 | Functionality | Low | Old email-template CRUD URLs 404 | Workspace replacement routes only | `RemovedLegacyRoutesTest` |

---

## 12. Admin and users

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| admin-001 | Security | Medium | User management behind `super.admin` + password confirm | Non-admins cannot manage users | `routes/web.php` |
| admin-002 | Functionality | Low | Primary admin password not resettable via UI | Must use secure console / hash update | `AdminUsersWorkspaceController` |

---

## 13. Persons

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| person-001 | Security | Medium | Person bank account edit 403 when not accessible to user | Multi-user firms: created-by-other-user accounts fail edit | `PersonShowWorkspaceController`, `BankAccountAccessTest` |
| person-002 | UX | Low | Officer roles rejected for tenancy-only entities | JSON error, not inline explanation | `PersonsWorkspaceController` |
| person-003 | UX | Low | Person form Tom Select clips without `dropdownParent=body` | Dropdown clipped in slide-over | `PersonsWorkspaceFormTest`, `tomselect-init.js` |

---

## 14. Vendors

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| vendor-001 | UI | Low | Duplicate session error flash on vendors index | Same error banner rendered twice | `resources/views/vendors/index.blade.php` lines 22–31 |

---

## 15. Leases and rent

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| lease-001 | Functionality | Medium | Rent collection accounts need linked leasable assets | Misconfigured rent bank breaks invoice allocation | `BankAccountsWorkspaceController`, `BankAccountAssetLinkService` |
| lease-002 | UX | Low | Lease create in asset workspace needs Tom Select reinit | Picker broken until panel reinits | `docs/TECH_UPDATE.md` |

---

## 16. Reminders and bills/tasks

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| reminder-001 | Functionality | Low | Standalone reminders CRUD routes removed | Old URLs 404; use dashboard / bills-tasks | `RemovedLegacyRoutesTest` |
| reminder-002 | UX | Low | Dashboard vs bills-tasks due windows differ | ASIC renewal limited to 15 days on Due tab; others unbounded | `BillsTasksController`, `bills-tasks/index.blade.php` |
| bills-001 | Performance | Medium | Due (all) tab loads full set then paginates in memory | Slow for large portfolios | `BillsTasksController::paginatedDueItems` |
| bills-002 | UX | Low | Overdue styling depends on `sort_date` being Carbon | Mixed date types could skip red border | `bills-tasks/index.blade.php`, controller |
| bills-003 | UX | Low | Unpaid income rows under “Unpaid bills” tab | Income badge under bills heading confuses | `bills-tasks/index.blade.php` |

**Recent history:** Bills/tasks **Carbon** date handling caused display/runtime issues (Aug 2026).

---

## 17. Dashboard

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| dash-001 | UX | Medium | Add transaction modal Tom Select reinit on entity/asset change | Paid-by / asset pickers break if reinit fails | `dashboard.blade.php`, `transaction-paid-by-bank-account.js` |
| dash-002 | Functionality | Medium | Bank account only required for bank_account + paid | Director funds posts to 2500 without bank picker | Dashboard transaction tests, director-funds rules |

---

## 18. Manual journals

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| journal-001 | Functionality | Medium | Void = reversing entry + `voided_at`, not delete | Register shows original and reversal | `ManualJournalRegisterTest`, services rules |

---

## 19. Commitments

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| commit-001 | Functionality | Low | Commitment destroy route removed | Must use settle workflow | `RemovedLegacyRoutesTest` |

---

## 20. Cross-cutting issues

| ID | Type | Sev | Summary | User impact | Evidence |
| --- | --- | --- | --- | --- | --- |
| x-001 | Performance | Medium | Global `app.js` bundles all workspace modules | ~1700+ lines of entity/asset/document/compliance JS on every page | `app.js`, `docs/TECH_UPDATE.md` Track 1 |
| x-002 | UX | Medium | Inconsistent flash / toast / confirm | ~27 duplicated flash blocks, ~34 native `confirm()`, partial `showToast` | `docs/TECH_UPDATE.md` Track 2 |
| x-003 | UX | Medium | Tom Select QA matrix not formally completed | Regression risk on hidden panels, modals, reconciliation | `docs/TECH_UPDATE.md` Track 4 |
| x-004 | Data | High | Accounting traps (loan, director funds, paid basis) | Silent GL/report drift if workflows misunderstood | `.ai/rules/services.md`, accounting doc §9 |
| x-005 | Security | Medium | PII encrypted but portfolio shared | Any login user reads all entity/person data via UI | `README.md`, `EncryptsAttributes` |
| x-006 | Performance | Low | Production rsync without `--delete` | Stale server files (e.g. old `postcss.config.js`) break builds | `docs/PRODUCTION.md` |
| x-007 | Functionality | Medium | Python required for PDF statements and `.msg` email | CSV bank import is PHP; PDF/msg fail without Python | `README.md` |
| x-008 | UX | Low | Reconciliation mutual-exclusion clears native value without always refreshing Tom Select labels | Stale visible label until dropdown reopened | `bank-reconciliation.js` `bindEntrySelectGuards` |
| x-009 | Data | Medium | Explicit director loan types via director_funds channel = zero net 2500 | Listing visible but balance unchanged by design | Accounting doc §9 High #1 |

---

## 21. Production and deployment (operational UX)

| Item | Impact |
| --- | --- |
| `npm run build` required after every deploy | UI changes (JS/CSS) invisible until build on server |
| Hard browser refresh sometimes needed | Cached Vite assets after deploy |
| Migrations `--force` on production | Schema drift breaks pages silently |
| Git remote redirect | `amitsaini2025/assettracker` → `bansallawyers12/assettracker` |

---

## 22. Priority clusters (for future fix planning)

| Theme | Issue IDs | Notes |
| --- | --- | --- |
| Bank reconciliation UX | bank-001, bank-002, x-003, x-008 | Match vs chart vs type; Tom Select in Change panel |
| Loan / offset accounting | bank-003, bank-004, asset-001, x-004, x-009 | Training + UI labelling; audit command |
| Access / 403 | bank-007, person-001, entity-002 | Multi-user firms, closed entities |
| Report accuracy expectations | report-001–004, txn-003, x-004 | User education vs code fixes |
| Frontend performance | x-001, doc-001, comp-001 | Vite page splitting (TECH_UPDATE Track 1) |
| UX consistency | x-002, bank-010, inv-002 | Toast/confirm migration |
| Compliance product gap | comp-002, comp-003, comp-004 | Roadmap vs bug |
| Legacy URLs | entity-003, email-003, reminder-001, coa-004 | Bookmarks / training |

---

## 23. Out of scope for this register

- Infrastructure hosting (beyond deploy notes in §21)
- Mobile responsiveness audit (not systematically reviewed)
- Automated browser/E2E test gaps
- Specific data fixes for production entities (requires case-by-case investigation)

---

## 24. Change log

| Date | Change |
| --- | --- |
| 2026-09-02 | Initial register created from codebase audit, rules, docs, tests, and Aug 2026 reconciliation feedback |

---

*End of document. To apply fixes, use separate change requests per priority cluster — do not treat this file as an implementation spec.*
