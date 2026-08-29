# CRM / Asset Tracker — Bug Inventory

**Generated:** 2026-07-26  
**Last re-verified against codebase:** 2026-08-29  
**Database in use:** `pgsql` (`config('database.default')`) — noted where a finding is MySQL-only.  
**Context:** Laravel app; authenticated users largely share one portfolio. Many policies still `return true` (authentication-only authz). That design is noted where it amplifies impact.

Pending items are listed first, fixed items are archived at the bottom.

---

# PENDING

## High

- **Primary admin can use the admin UI without ever enabling 2FA** — admin routes do have `auth` + `2fa.enrolled` + `2fa.verified` + `password.confirm`, but `TwoFactorVerified` is a no-op until 2FA is enrolled, and `EnsureTwoFactorEnrolled` allows the primary administrator through **all** `admin.users.*` routes (including `store` / `destroy`) after grace is exhausted. They get locked out of the rest of the app but keep managing users without TOTP. The middleware comment claiming "admin routes omit `2fa.enrolled`" is stale. — `EnsureTwoFactorEnrolled`, `TwoFactorVerified`, `routes/web.php`
- **Policies are broadly permissive (systemic)** — `BusinessEntityPolicy` nearly all `return true`; `AssetPolicy` / `CommitmentPolicy` / document / compliance policies only do weak entity-null checks. Authorization is effectively authentication-only, so every "open entity authz" item below (documents, compliance, banking, reports) inherits it. — `app/Policies/*`
- **Send mail allows arbitrary `from_email`** — validated as `email` only, then passed straight into `ContactEmail` with no check the address belongs to the user or their Gmail account. Spoofing / relay abuse. — `MailMessageController::sendEmail` (~507–528)
- **MSG attachment filenames used unsanitized in storage paths** — `emails/{id}/attachments/{filename}` built from parser output with no `basename`/sanitize; `../` can escape the intended prefix depending on disk. — `MsgParserService` (~154)
- **Full account numbers returned in bank-account JSON** — `getBankAccounts` selects the decrypted `account_number`; `revealBankAccountNumber` is audited and gated but only by allow-all policies. List UI itself is masked. — `BusinessEntityController::getBankAccounts`, `revealBankAccountNumber`
- **`finalizeNote` / `extendNote` skip authorization when the note has no entity** — if both `$note->businessEntity` and `$note->asset?->businessEntity` are null, no `authorize()` runs at all. — `AssetController::finalizeNote` / `extendNote`
- **Unscoped reminders are globally readable / completable** — `ReminderPolicy::view` / `update` fall through to `return true` when the row has no `business_entity_id` and is not owned by the caller; `store` never authorizes `create`. `bulkComplete` now filters per row, but unscoped rows still pass. — `ReminderPolicy`, `ReminderController`
- **Cash flow report is still not cash** — operating sign now matches P&L, but investing/financing are period GL movements, not bank cash. Calling it "cash flow" remains misleading. — `FinancialReportService::generateCashFlow`
- **Extension-first upload validation; SVG still an allowed type** — `isAllowed()` requires an allowlisted extension but falls back to extension when MIME is empty or `application/octet-stream`; no magic-byte check. SVG remains allowed in config and MIME list (stream now forces download + CSP, so inline XSS is mitigated, not removed). — `DocumentUploadValidation`, `config/documents.php`

## Medium

- **Closed entities: reopen path and close→Sold are still blunt** — `update()` lets `status = Active` clear `closed_date`; `close()` still force-updates every asset to `Sold`, so a mistaken close is hard to undo. — `BusinessEntityController::close` / `update`
- **ABN/ACN hashes keyed off `APP_KEY`** — `config('app.hash_pepper', config('app.key'))`, and there is no `hash_pepper` entry in `config/app.php`, so key rotation still breaks uniqueness lookups. — `BusinessEntity::computeAbnHash` / `computeAcnHash`
- **Remaining user FK cascades** — `business_entities.user_id` and `journal_entries.created_by` are now `nullOnDelete`, but `notes.user_id`, `reminders.user_id`, `email_templates.user_id`, `email_drafts.user_id` are still `onDelete('cascade')`, and `mail_messages.user_id` has no FK (orphans). Only `canBeDeleted()` stands between a delete and data loss. — user FK migrations, `User::deleteBlockedReason`
- **Password history configured but unused** — `security.passwords.history_count = 5` exists; nothing records or rejects password reuse. — `config/security.php`
- **Session flush only covers the `database` driver** — `flushPersistedSessionsForUser` deletes DB session rows; file/redis rely on `remember_token` + `AuthenticateSession` / `EnsureAccountActive` on the next request. — `UserManagementController`
- **Person email uniqueness is racy** — no DB unique on `persons.email` (encrypted), so concurrent `create_new_person` / `storePerson` can duplicate people. — `EntityPersonController`, `Person::findByEmail`
- **`storePerson` is weaker than `store`** — no share-percentage cap, no trustee-vs-role check, and it accepts the full `EntityPerson::ROLES` list (`store` excludes Appointor). — `EntityPersonController::storePerson`
- **`asic_due_date` uses `after_or_equal:today` on update** — editing a role that legitimately has a past ASIC date fails validation. — `EntityPersonController::update`
- **`allocateTransaction` allows conflicting bank links** — when the transaction is already linked to a different bank account it only writes a `Log::warning` and proceeds. — `BusinessEntityController::allocateTransaction` (~2766)
- **Ambiguous AU vs US date parsing on CSV import** — `d/m/Y` is tried before `m/d/Y`, so `01/02/2026` can still land on the wrong day for US-formatted files. — `BankCsvStatementParser`
- **Weak bank duplicate detection** — date + amount + description only; no unique DB constraint on statement entries.
- **Global CoA lookup by code for cross-entity payer journals** — a misconfigured code posts to the wrong ledger or nothing.
- **Observer re-posts journals on every transaction update** — concurrent updates can interleave journal rebuilds. — `TransactionObserver`
- **`Transaction::isSplit()` is true whenever a non-empty `lines` relation is loaded** — orphan lines force the split path.
- **Payer options expose all entities and directors** — `TransactionPayerResolver::payerOptions()`
- **Vendor uniqueness is case-sensitive on Postgres, linking is not** — `unique('name')` on `vendors` plus `LOWER(TRIM(...))` matching in `VendorSyncService` means `Acme` and `acme` can both exist and then be merged incorrectly. — `2026_07_01_100000_create_vendors_table.php`, `VendorSyncService`
- **COA `store()` does not run the cycle check** — `update()` calls `parentWouldCreateCycle()`; `store()` / `validateNewAccount()` only check that the parent exists. — `ChartOfAccountController::store`
- **No real authorization on COA / vendor mutations** — any authenticated user.
- **Entity-scoped COA write routes ignore the entity parameter** — the chart is global, so the URL implies isolation that does not exist.
- **Rent vs manual invoice GST convention differs** — rent puts GST-inclusive rent in `unit_price` / `line_total` with `gst_rate` 0.10 while manual invoices treat unit price as ex-GST; display/PDF and mixed invoices are easy to misread.
- **No unique index on `invoice_number`** — `lockForUpdate()` narrows the race but does not close it. — `RentInvoiceService::generateInvoiceNumber`
- **Rounding can now fail a post instead of unbalancing it** — the debit/credit equality check throws, so cent-level drift becomes a hard error for the user. — `InvoicePostingService`
- **`createLeaseFromTenantIfMissing` can create a $0 lease** — when both tenant rent and `asset->rental_income` are empty, giving zero-amount rent invoices. — `AssetController` (~886)
- **Bills & Tasks "Due" loads every due type into memory, then paginates in PHP** — also a 15-day dashboard window vs all-open dues on the Bills & Tasks tab. — `BillsTasksController::paginatedDueItems`
- **Commitment policy is effectively allow-all** — any user can settle or delete any commitment. — `CommitmentPolicy`
- **Report scope has no per-user filter** — policies `return true`; `ResolvesReportEntityScope` only narrows to `forFinancialReports()` IDs. Cross-entity financial disclosure in a multi-user setup.
- **Email templates are shared globally** — listings use `EmailTemplate::query()` with no `user_id` filter and `EmailTemplatePolicy` returns `true`, contradicting the "own templates" comments. — `EmailTemplatesWorkspaceController`, `EmailTemplateController::getTemplates`
- **Sent vs Inbox labelling uses hardcoded `@yourdomain.com`** — real Gmail addresses never match, so nearly everything is labelled Inbox. — `MsgParserService` (~216), `GmailFetcher` (~85)
- **Re-parsing a message duplicates attachments** — always `MailAttachment::create` with no cleanup of prior rows/objects. — `MsgParserService`
- **Allocate-to-asset does not authorize the asset** — IDOR-shaped, currently masked by allow-all policies. — `MailMessageController`
- **Documents stored plaintext on S3** — an encrypted filesystem adapter exists but is not used for documents.
- **Compliance / document bulk upload and auto-match races** — empty-slot lookup and attach are not one transaction, and `ChecklistFilenameMatcher::findBest` returns the first fuzzy match over 85% rather than the best overall.
- **Compliance status can be `lodged` / `paid` with no file** — false completeness, and the reminder sync skips those rows while evidence is missing.
- **Compliance move collision uses `checklist_label` only** — ignores template / effective labels.
- **BAS frequency change can wipe empty template slots** — switching annual ↔ quarterly deletes non-progress BAS rows.
- **`destroyFile` / inactive BAS cleanup can leave S3 orphans.**
- **`EnsuresOperationalBusinessEntity` is narrower than the full set of entity mutations** — tenancy contacts are blocked for accounting actions, but not every document / compliance / profile path uses the trait.
- **`asset_id` on a reminder is only checked when `business_entity_id` is also sent** — omit the entity and a cross-entity asset link still passes. — `ReminderController::store`

## Low

- **`/phpinfo` still dumps full `phpinfo()`** to anyone holding the local token (gated to `local` + `X-Phpinfo-Token`).
- **Delete-confirmation copy vs remaining cascading user FKs** (notes, reminders, templates, drafts).
- **Dashboard hydrates every operational `EntityPerson` row** to render three unique people.
- **`user_id` on business entities is set but unused for access control** — misleading ownership model.
- **Changing asset type to non-property nulls finance fields with no confirmation.**
- **Note / due-date extend is a fixed +3 days for every type.**
- **Trusted stored MIME used as `Content-Type` on document streams.**
- **No operational-entity gate on all document routes** — tenancy contacts can mutate documents.
- **Compliance workspace GET auto-provisions years and slots** when `compliance.auto_provision_on_view` — a read endpoint mutating the DB.
- **Cross-entity compliance upload URL mismatch returns 422 rather than a clean 404.**
- **MySQL-only:** the compliance year unique index `(business_entity_id, asset_id, fy_start_date)` permits duplicates when `asset_id IS NULL`. Postgres (the configured driver) has correct partial unique indexes, and `findOrCreateYearRecord()` also locks, so this is dormant here. — `2026_06_21_000001_create_compliance_document_tables.php`
- **Bank statement PDFs sit briefly on local disk during parsing.**
- **CSV formula-injection payloads in descriptions** if later exported to Excel.
- **Invoice create/update is not always wrapped in a transaction** — partial lines on failure.
- **Duplicate "Rent" detection via `reference like '%Rent%'`** is brittle.
- **Commitment payments can exceed contract price** (`balance_due` floors at 0); settle-and-create-asset does not reconcile to `contract_price`.
- **Portfolio empty-scope vs hub inconsistency** in reports (`[]` vs reject).
- **Yield annualisation mixes bases** — expenses day-factored while rent may use a static `rental_income`.
- **Allocate-attachment failures are silent** — the user still sees success.
- **Python `/email/parse` FastAPI endpoint has no auth** (default bind `127.0.0.1`).
- **`RateLimitMiddleware` always returns JSON 429** — poor fit if ever applied to form posts.
- **`RateLimitMiddleware` only runs on the `api` group** — most JSON lives on **web** routes (send email, templates, CoA, bank apply), so it is effectively unused. Sanctum is deliberately not installed.
- **Web routes serving `/api/...` paths** — duplicate-looking URLs for bank accounts and CoA; clients can hit the wrong one.
- **`ContactListPolicy` is unused** — controllers authorize `BusinessEntity` only, so ownership looks enforced but is not.
- **Persons workspace create form still lists confusing entity options**; the POST is guarded by operational exists rules.

## Suggested fix order

1. Remove the primary-admin 2FA exception (or require enrollment before admin access).
2. Email integrity: bind `from_email` to the account, sanitize MSG attachment paths, fix `@yourdomain.com` Sent/Inbox.
3. Stop returning full account numbers in JSON; make CSV date parsing AU-only.
4. Upload hardening: magic-byte verification, drop or isolate SVG.
5. Closed-entity semantics (reopen, close→Sold) and remaining user FK cascades.
6. Reminders: unscoped rows plus the `ReminderPolicy` fallback `true`.
7. Accounting honesty: cash-flow labelling, COA create cycles, vendor case uniqueness.
8. Tighten policies if more than one household will ever use this.

---

# FIXED (archive)

Verified fixed as of 2026-08-29. Kept for history so old reports are not re-filed.

## Auth & 2FA

- Backup codes can be regenerated with no proof — now behind `2fa.enrolled` + `2fa.verified` and requires a valid TOTP or backup code.
- 2FA challenge unthrottled — `RateLimiter`, 5 attempts per pending user / IP.
- `/phpinfo` accepted a query-string token — now `local` only, `PHPINFO_ACCESS_TOKEN` via `X-Phpinfo-Token` header, `hash_equals`.
- Default admin credentials shipped in config — `config/admin.php` is env-only and null when unset.
- Env admin hash was a permanent login path — `LoginRequest` uses it only to **create** the missing primary admin; afterwards `Auth::attempt` (DB password) is the only path.
- Null `password_changed_at` skipped expiry — bootstrap sets it; `PasswordSecurity` falls back to `created_at`.
- Grace-period off-by-one — blocks at `count >= grace`, and the login flash copy matches.
- `last_login_at` / `last_login_ip` never written — `User::recordLogin()` on password login and after the 2FA challenge.
- Backup-code case mismatch on disable — `disableTwoFactor` uppercases before comparing.
- Dead temporary-2FA token helpers — removed from `TwoFactorService`.
- Admin routes skipped 2FA middleware entirely — groups now include `2fa.enrolled` + `2fa.verified` (one exception remains, see Pending High).

## Admin user management

- Delete with no `canBeDeleted()` check — `destroy()` uses `canBeDeleted()` / `deleteBlockedReason()`, blocks primary admin and self-delete, catches `QueryException`.
- No re-auth on destructive admin actions — `store` / `activate` / `deactivate` / `password` / `destroy` all require `password.confirm`.
- Cascade wipe of portfolio and journals on user delete — `business_entities.user_id` and `journal_entries.created_by` are `nullOnDelete` (migration `2026_08_01_000001`).
- Grace-period route exception listed stale route names — now matches the live admin SPA (only the explanatory comment is stale).
- Primary-admin password reset was misleading — UI refuses it and explains the env hash is bootstrap-only.
- Hardcoded `/admin/users/{id}/...` URLs in JS — now `data-user-url` attributes.

## Dashboard & profile

- Privilege escalation via profile email — email is no longer editable on profile.
- Email uniqueness broken with encryption on profile update — resolved by removing email from profile update.
- Dashboard reminder sort reversed — `sortBy` ascending on due date.
- Dashboard due lists ignored operational scope — reminders, notes, unpaid bills, ASIC rows and asset due dates are all scoped to `operationalEntities()`.
- Profile self-delete did not protect the primary admin — `ProfileController::destroy` checks `isPrimaryAdministrator()`.
- `password-expired` flash was unhandled — the profile banner has a case for it.

## Business entities & persons

- `BusinessEntity::setAttribute()` shadowed the encryption trait — trait method is aliased as `setEncryptedAttribute` and called.
- `importPersons` was a no-op — creates the missing `EntityPerson` rows.
- ABN/ACN validated as `max:11` / `max:9` on the raw string — digit-length callbacks plus `UniqueAbnHash` / `UniqueAcnHash`.
- `abn_hash` / `acn_hash` were fillable — removed from `$fillable`, added to `$hidden`.
- Tenancy-contact list included closed entities — `whereNull('closed_date')`.
- `EntityPersonController` had almost no authorization or closed-entity checks — writes authorize the entity and call `ensureNotClosed`.
- Appointor role reintroducible via update — only allowed when the existing row is already Appointor.
- Corporate trustee could be any entity — `ruleExistsNonTrustCompany()` excludes trusts and closed entities.
- PII logged in application logs — person/entity-person logs now record IDs only.
- Shareholding could exceed 100% and Resigned needed no date — aggregate cap and `required_if` are enforced.

## Assets

- Workspace update wiped loan/offset/rent bank links — sync only runs when bank fields (or `has_bank_account_fields`) are present.
- Asset `status` was shown in forms but ignored on update — validated and persisted.
- No closed / tenancy guards on asset create and update — `ensureNotClosed` + `ensureOperationalForAccounting`.
- `indexAll` included assets on closed entities — filtered through `operationalEntities()`.
- `storeNote` missing `required_if:is_reminder,1` on `reminder_date` — added.
- `updateLoanBanking` authorized `view` instead of `update` — now `update` plus closed and property checks.
- Hard delete orphaned financial history — `destroy()` blocks when tenants, leases or invoices exist.
- Invoice summary counted all `approved` invoices as outstanding — now `approved` **and** `whereNull('paid_at')`.
- No lease destroy path — `destroyLease` exists.

## Documents & compliance

- Inline SVG XSS via the document stream — SVG is forced to download with a restrictive CSP.
- Replace-upload deleted the old object before the new write — new path is written first, old deleted after.
- Asset document stream did not require `asset_id` — asset-scoped rows require a matching `?asset_id=`.
- Compliance replace upload had the same delete-first ordering — `ComplianceUploadService::attachFile` writes then deletes.
- `compliance:sync-reminders` assigned reminders to the first user in the table — it now skips when there is no entity owner and no `--user` / `Auth::id()`.
- Compliance year records raced on create — wrapped in a transaction with `lockForUpdate` and a create-conflict retry.

## Banking & accounting

- Full account numbers rendered in the bank list UI — uses `masked_account_number`.
- Bank import ignored the selected chart account — `BankStatementApplyService` stores `chart_of_account_id` on the created transaction.
- Bank import double-match / double-post race — statement rows are taken `lockForUpdate` and rejected if already matched; duplicate transaction selections raise a validation error.
- Python parser treated "balance" as an amount — `python_bank_parser.py` is gone; `BankCsvStatementParser` keeps `AMOUNT_COLUMNS` and `BALANCE_COLUMNS` separate.
- Statement upload used the client filename in the storage path — path is built from the statement period plus a random suffix; the original name is metadata only, and the object is deleted if the DB insert fails.
- Hardcoded `python3` — PDF parsing uses `python` on Windows and `MsgParserService::detectPython()` probes `py -3` / `py` / `python3` / `python`.
- Inclusive GST greater than the amount produced a negative net — `TransactionCashParts` clamps GST to `abs(amount)`.
- Bank import endpoints lacked authorization — `apply` / `destroyEntries` authorize the entity and require `ruleExistsOperational`.

## Invoices & rent

- Open DB transaction left behind on "invoice already exists" — `rollBack()` before returning.
- Posting could create unbalanced journals when the GST account was missing — `ensureDefaultGstAccount()` plus a debit/credit equality check that throws.
- Invoice number race — `lockForUpdate()` on the last number for the period.
- Upcoming rent forecast dropped mid-window leases — compares `end_date >= $startDate` with a per-month skip.
- "Record payment" was status-only — `InvoicePaymentService` posts the cash/AR clearing entry.

## Tracking, reports, reminders, API

- Tracking `is_active` could never be turned off — `$request->boolean('is_active')` so unchecked means false.
- Deleting a tracking category ignored subcategory usage — usage is checked across subcategories before delete.
- Cash flow operating figure had an inverted sign versus P&L — same convention now (`-$income - $expenses`).
- Missing-ITR report false positives when the type was absent — `firstOrCreate` for the `itr` type.
- `bulkComplete` skipped authorization entirely — each row is checked with `can('update', $reminder)`.
- Reminder `asset_id` could cross entities — validated against `business_entity_id` when both are supplied.
- Unscoped reminder index route — no `index` route exists; Bills & Tasks scopes to operational entities.
- Repeating reminders mutated Carbon in place and could overshoot `repeat_end_date` — uses `copy()` and skips when the next date is past the end.
- Contact list ID comparison without a cast — `(int)` on both sides.
- `routes/api.php` exposed bank accounts outside `auth:sanctum` — the file has no routes; JSON endpoints live on web routes inside `auth` + 2FA.

---

## Notes

- This is a **bug inventory**, not a claim that every item is exploitable in production as configured.
- Shared-portfolio / allow-all policies may be intentional for a single-family office; they are listed where they turn a local bug into a cross-user one.
- Do not reintroduce an editable profile email without `email_hash` uniqueness and admin-email reservation.
- Findings marked MySQL-only are dormant on the configured `pgsql` connection but would return on a MySQL/MariaDB deployment.
