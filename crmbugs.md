# CRM / Asset Tracker — Potential Bugs

**Generated:** 2026-07-26  
**Last re-verified against codebase:** 2026-07-26  
**Scope:** Full application review, area by area. Findings only — no fixes applied in this document.  
**Context:** Laravel app; authenticated users largely share one portfolio. Many policies intentionally return `true` (authentication-only authz). That design is noted where it amplifies impact; items below focus on broken logic, missing guards, data integrity, and security gaps beyond “everyone can see everything by design.”

### Re-verification changelog

| Item | Status |
|------|--------|
| Area 1 — backup-code regen without proof | **FIXED** — requires TOTP/backup code + `2fa.enrolled` + `2fa.verified` |
| Area 1 — 2FA challenge unthrottled | **FIXED** — `RateLimiter` (5 attempts) in `verifyChallenge` |
| Area 1 — `/phpinfo` always available | **PARTIALLY FIXED** — now requires `APP_ENV=local` + token; prefers header |
| Area 2 — admin delete with no `canBeDeleted` | **FIXED** — uses `canBeDeleted()` + primary-admin / self guards |
| Area 2 — no password confirm on destructive admin actions | **PARTIALLY FIXED** — mutations use `password.confirm`; still no 2FA on admin |
| Area 2 — cascade wipe on user delete | **MITIGATED** — normal path blocked when entities/journals exist; FK cascades remain if guards bypassed |
| Area 3 — profile email → admin privilege escalation | **FIXED** — email not editable on profile |
| Remaining High items in Areas 4–16 | Still open unless marked FIXED below |

---

## Area 1 — Auth & security

### High

1. **Admin routes skip 2FA**  
   `/admin/users*` uses `auth` + `super.admin` (and `password.confirm` on some mutations), but **not** `2fa.enrolled` / `2fa.verified`. A primary admin who is logged in without a verified TOTP session this request cycle (remember-me restore, lost `2fa_verified` flag, or still in grace period) can open the admin UI and, after password confirm, manage users without completing 2FA.  
   **Where:** `routes/web.php` admin groups (~439–453); contrast with main app group that requires both 2FA middlewares.

2. ~~**Backup codes can be regenerated with no proof**~~ — **FIXED**  
   Now behind `auth` + `2fa.enrolled` + `2fa.verified`, and `regenerateBackupCodes` requires a valid TOTP or backup code.

3. ~~**2FA challenge is unthrottled**~~ — **FIXED**  
   `TwoFactorController::verifyChallenge` rate-limits via `RateLimiter` (5 attempts per pending user / IP key).

4. **Default admin credentials in config**  
   `config/admin.php` ships a default `ADMIN_EMAIL` and a bcrypt `ADMIN_PASSWORD_HASH`. If env vars are missing in any environment, that hardcoded bootstrap login still works.

### Medium

5. **Env admin password ignores DB password changes**  
   `LoginRequest` accepts the configured admin email + `ADMIN_PASSWORD_HASH` even if the user’s DB password was changed in the UI. Updating the admin password in-app does not revoke the env-hash login path.  
   **Where:** `app/Http/Requests/Auth/LoginRequest.php`.

6. **Password history configured but unused**  
   `security.passwords.history_count = 5` exists in `config/security.php`, but nothing enforces reuse prevention on password update/reset.

7. **Null `password_changed_at` skips expiry**  
   `PasswordSecurity` only enforces max age when `password_changed_at` is set. Bootstrap admin creation in `LoginRequest` never sets it, so that account may never hit the expiry redirect.  
   **Where:** `app/Http/Middleware/PasswordSecurity.php`.

8. **Grace-period off-by-one**  
   Middleware blocks when `logins_without_two_factor_count > grace` (default 3), so users get **4** free logins. Flash copy (“completed X of 3”) does not match that behavior.  
   **Where:** `EnsureTwoFactorEnrolled`; `AuthenticatedSessionController::store`.

### Low

9. **`last_login_at` / `last_login_ip` never written**  
   Profile and admin UI show them, but nothing in the login flow updates them — always empty.

10. **Backup-code case handling inconsistent on disable**  
    Challenge and regenerate uppercase the code before backup-code verify; `disable` passes the raw trimmed code into `disableTwoFactor` without uppercasing. Lowercase backup codes can fail on disable.  
    **Where:** `TwoFactorController::disable` vs `verifyChallenge` / `regenerateBackupCodes`.

11. **`/phpinfo` still accepts query-string token (local only)** — **PARTIALLY FIXED**  
    Now gated by `APP_ENV=local` and token; prefers `X-Phpinfo-Token` header. `?token=` still works and can leak via logs/history if used.  
    **Where:** `routes/web.php`.

12. **Dead temporary-2FA token helpers**  
    `storeTemporaryVerification` / `verifyTemporaryVerification` in `TwoFactorService` are unused — dead surface, not a live bug.

---

## Area 2 — Admin user management

### High

1. **DB cascades still wipe portfolio/journals if a user is deleted** — **MITIGATED for normal admin UI**  
   `business_entities.user_id` and `journal_entries.created_by` remain `onDelete('cascade')`. Admin `destroy()` now calls `canBeDeleted()` / `deleteBlockedReason()` (blocks when entities, journals, mail, etc. exist) and also blocks primary admin / self-delete. **Live risk:** schema-level cascade if delete is forced elsewhere (tinker, future code path, incomplete blocker list). Prefer `nullOnDelete` / reassign over cascade.  
   **Where:** migrations; `User::deleteBlockedReason`; `UserManagementController::destroy`.

2. ~~**(Former) Deleting a user always wiped journals via unguarded destroy**~~ — folded into #1; unguarded path **FIXED**.

3. ~~**No guard before delete**~~ — **FIXED**  
   Admin `destroy()` now uses `canBeDeleted()`, primary-admin check, self-delete check, and catches `QueryException`.

### Medium

4. **Admin UI still skips 2FA**  
   Same as Area 1 #1. Mutations additionally require `password.confirm`, but not a verified TOTP session.

5. **Grace-period “admin can create users” exception is stale**  
   `EnsureTwoFactorEnrolled` allows `admin.users.create` / `admin.users.store`, but the live UI uses `admin.users.index`, `admin.users.form.create`, and `admin.users.workspace` — none allowed. After the grace limit, the primary admin cannot use the admin SPA; the exception does not match current routes.

6. **Password reset for primary admin is misleading**  
   UI can reset the primary admin’s DB password. The env `ADMIN_PASSWORD_HASH` login path still works, so that “reset” may not lock them out of the bootstrap login.

7. **Session flush only works for `database` sessions**  
   `flushPersistedSessionsForUser` only deletes DB session rows. File/redis drivers rely on `remember_token` clear + next-request checks (`EnsureAccountActive` / `AuthenticateSession`). Documented in controller comments; still means non-DB sessions are not immediately purged server-side.

### Low

8. **Delete can leave orphan mail rows**  
   `mail_messages.user_id` has no FK. If a user with no other blockers but leftover mail is deleted (or mail check is incomplete), orphaned inbox rows can remain. (`deleteBlockedReason` does check `mailMessages` — orphans mainly if mail table unchecked / legacy rows.)

9. **Confirm text vs schema reality**  
   UI copy is safer now that `canBeDeleted` blocks ownership, but FK cascades remain a latent data-loss hazard.

10. ~~**No re-auth for destructive admin actions**~~ — **PARTIALLY FIXED**  
    `store` / `deactivate` / `password` / `destroy` require `password.confirm`. Still no 2FA on admin routes; `activate` and read/workspace routes do not use `password.confirm`.

11. **`last_login_at` always empty in the list**  
    Same as Area 1.

12. **Hardcoded paths in JS**  
    `admin-users-workspace.js` builds `/admin/users/{id}/...` instead of data attributes for all actions — brittle if routes change.

---

## Area 3 — Dashboard & profile

### High

1. ~~**Privilege escalation via profile email**~~ — **FIXED** (commit `42d3653`)  
   Email is no longer editable on profile; only name is validated/saved. Previously any user could set email to `ADMIN_EMAIL` and become primary admin. Kept here for history.

2. ~~**Email uniqueness broken with encryption on profile update**~~ — **MITIGATED** by removing email from profile update.  
   Note: any future email-edit path must use `email_hash`, not `Rule::unique` on the encrypted `email` column.

### Medium

3. **Dashboard reminder sort is reversed**  
   Combined reminders use `sortByDesc('next_due_date')`, so furthest-future items float to the top and overdue items sink. Widget copy says overdue / next 15 days — should be ascending.  
   **Where:** `BusinessEntityController::dashboard`.

4. **Dashboard due lists ignore “operational” scope inconsistently**  
   Entities/assets for main lists use `operationalEntities()`, but Reminder / Note / unpaid Transaction due widgets do not. Closed or report-excluded entities can still appear. Asset due rows call `Asset::upcomingDueDateRows()` with no operational entity IDs.

5. **Self-delete does not protect the primary admin**  
   Profile `destroy()` uses `canBeDeleted()` but not `isPrimaryAdministrator()`. A primary admin with no owned rows can delete themselves and remove the only admin gate.  
   **Where:** `ProfileController::destroy`.

6. **`password-expired` flash is not handled**  
   Middleware redirects to profile with `status = password-expired`, but the profile banner has no case for it — users see the raw string `password-expired`.  
   **Where:** `resources/views/profile/edit.blade.php`.

### Low

7. **Bills & Tasks “Due” tab loads everything then paginates in PHP**  
   All due item types loaded into memory, sorted, then sliced. Will get slow as data grows.  
   **Where:** `BillsTasksController::paginatedDueItems`.

8. **Dashboard vs Bills & Tasks window mismatch**  
   Dashboard uses a 15-day window; Bills & Tasks “Due” shows all open due dates (except ASIC renewal, still 15 days). Same concepts, different rules.

9. **Heavy unscoped person load on dashboard**  
   `EntityPerson::with(...)->get()` loads every role row only to show three people.

10. ~~**Profile delete is safer than admin delete**~~ — **RESOLVED**  
    Both profile and admin destroy now use `canBeDeleted()`. Remaining gap: profile still does not block `isPrimaryAdministrator()` self-delete (Area 3 #5).

---

## Area 4 — Business entities

### High

1. **BusinessEntity encryption bypassed by `setAttribute` override**  
   `BusinessEntity::setAttribute()` computes ABN/ACN hashes then calls `parent::setAttribute()` (Eloquent `Model`), which **shadows** `EncryptsAttributes::setAttribute()`. Unlike `User` (which aliases the trait method), entity `tfn` / `abn` / `acn` / `corporate_key` may be stored as **plaintext**. `getAttribute()` from the trait still tries decrypt-on-read and falls back to plaintext.  
   **Where:** `app/Models/BusinessEntity.php`; `app/Traits/EncryptsAttributes.php`.  
   **Repro:** Create/update entity with ABN/TFN; inspect DB column (expect ciphertext if encryption worked).

2. **Closed entities remain fully mutable**  
   `close()` sets `closed_date` and marks assets Sold, but `update()`, notes, profile workspace, etc. do not check `isClosed()`. Status can be set back to `Active` while `closed_date` remains → inconsistent Active+closed state.  
   **Where:** `BusinessEntityController::close` / `update`; entity show UI.

### Medium

3. **Close workflow force-marks all assets Sold**  
   `$businessEntity->assets()->update(['status' => 'Sold'])`. Combined with asset update ignoring `status` (Area 6), mistaken close is hard to undo.

4. **`importPersons` is a no-op**  
   Authorizes then does nothing. Route: `POST business-entities/{businessEntity}/import-persons`. Silent feature failure.

5. **ABN/ACN uniqueness tied to `APP_KEY`; weak digit validation**  
   Hashes use `config('app.key')` — key rotation breaks uniqueness lookups. Validation is `max:11` / `max:9` on raw string, not normalised digit length. Formatted ABNs with spaces can fail; short digit strings can pass.

6. **`abn_hash` / `acn_hash` are `$fillable`**  
   Mass-assignment risk if any path uses broad request fill.

7. **Tenancy-contact list ignores closed filter**  
   Index loads tenancy contacts with only `exclude_from_financial_reports = true` (no `whereNull('closed_date')`). Closed contacts still appear.

8. **`EnsuresOperationalBusinessEntity` is narrow**  
   Blocks tenancy contacts for accounting-style actions, but entity CRUD/notes/assets are not gated the same way.

### Low

9. **`user_id` set on create but unused for access control**  
   Misleading ownership model while policies allow all authenticated users.

10. **Note destroy/store authorize entity update but no closed check** (see #2).

---

## Area 5 — Persons & roles

### High

1. **`EntityPersonController` has virtually no authorization / closed checks**  
   `store` / `update` / `show` / `edit` / `finalizeDueDate` / `extendDueDate` / `storePerson` / `showPerson` rarely call `$this->authorize(...)`. Finalize/extend work on any `EntityPerson` id; closed-entity role UIs can still open. Writes that use `ruleExistsOperational()` are safer than due-date endpoints.  
   **Where:** `app/Http/Controllers/EntityPersonController.php`.

### Medium

2. **Update allows `Appointor` role; create does not**  
   Appointor can be reintroduced via role edit, bypassing “manage via company profile.”

3. **Corporate trustee may be a closed / tenancy entity**  
   `ruleExistsNonTrustCompany()` excludes Trusts only — not closed or `exclude_from_financial_reports`.

4. **Encrypted email uniqueness is fragile**  
   On `create_new_person`, uniqueness uses `Person::all()->contains(...)` (full table load + race). `storePerson()` has no email uniqueness check. DB unique on `persons.email` was dropped for encryption. Duplicate people and races possible.

5. **PII logged in application logs**  
   `EntityPersonController::store` logs `$request->all()`, created person arrays, and entity-person payloads (names, email, TFN, etc.).

6. **Role vs company-trustee mismatch**  
   `entity_trustee_id` can be set with any role (e.g. Director + company), not only Trustee.

7. **`storePerson` always creates a new Person + role**  
   No “link existing person”; weaker validation than `store` → duplicates.

8. **ASIC due date rules inconsistent**  
   `store`: `after:today`. `update` / `storePerson`: past allowed. `extendDueDate` +30 days with weak/no authz.

### Low

9. No aggregate cap on `shares_percentage` (can exceed 100% across shareholders).  
10. `role_status = Resigned` without requiring `resignation_date`.  
11. Persons workspace createForm allows closed entities (only tenancy blocked); POST fails operational exists rule — confusing UX.

---

## Area 6 — Assets CRUD & lifecycle

### High

1. **Asset workspace update wipes bank-account links**  
   `AssetController::update()` always calls `extractBankAccountLinks()` + `syncBankAccountLinks()`. Missing IDs become `null` → detach loan/offset/rent roles. Workspace form (`business-entities/partials/assets/form.blade.php`) has **no** loan/offset/rent bank fields (full `assets/edit.blade.php` does). Editing via entity Assets workspace clears links.  
   **Repro:** Link loan/offset on asset → Edit via entity workspace → save name → links gone.

### Medium

2. **Asset `status` shown in forms but ignored on update**  
   Forms send `status`; `update()` validation has no `status` rule → not persisted. Create accepts status. Users think they changed Active/Sold; DB unchanged. Combines badly with close→Sold.

3. **No closed / tenancy guards on asset create/update**  
   Can create Active assets on closed or contact-only entities.

4. **`indexAll` includes assets on closed entities**  
   No operational/open filter on global asset list / dashboard “View All.”

5. **Hard delete cascades / orphans financial history**  
   Destroy deletes asset; tenants/leases cascade; `invoices.asset_id` / `transactions.asset_id` nullOnDelete. No check for open leases/invoices. Historical rows lose asset association.

6. **Note finalize/extend have no scope check**  
   `finalizeNote` / `extendNote` take `Note $note` only — any authenticated user can clear/extend any reminder note by id.  
   **Routes:** `notes.finalize`, `notes.extend`.

7. **Asset reminder notes: weak validation**  
   `storeNote`: no `required_if:is_reminder,1` on `reminder_date` (entity notes have it).

8. **`createLeaseFromTenantIfMissing` defaults rent to `0`**  
   Missing `rent_amount` → $0 lease → zero-amount rent invoices possible.

9. **Invoice summary “outstanding” logic on asset show**  
   Sums `status = approved` as outstanding without unpaid/balance concept — misstated totals.

10. **`AssetShowWorkspaceController::updateLoanBanking` authorizes `view` not `update`.**  
11. **`extendDueDate` uses in-place `Carbon::addDays(3)`** — mutates cast; fixed +3 for all types.

### Low

12. `store` authorizes `view` instead of create/update.  
13. Changing asset type to non-property nulls finance fields without confirm.  
14. Tenant real-estate company address fallback may use property address oddly.  
15. No clear lease destroy path — stale leases may linger.

---

## Area 7 — Documents

### High

1. **Open entity authz (documents)**  
   `BusinessEntityPolicy` / `DocumentPolicy` effectively allow any authenticated user to upload, stream, delete, or rearrange documents for any entity. Amplifies every document route.

2. **Extension-first file validation (content not verified)**  
   `DocumentUploadValidation::isAllowed()` trusts client extension allowlist without magic-byte/MIME verification. Renamed malware/polyglots accepted. SVG allowed in `config/documents.php`.

3. **Inline SVG XSS via document stream**  
   `DocumentController::streamDocument()` can serve `image/svg+xml` inline. Malicious SVG can execute script under the app origin.  
   **Repro:** Upload SVG with `<script>` → open content URL without `?download=1`.

### Medium

4. **Asset document stream does not require `asset_id`**  
   Omitting `asset_id` still streams; mismatched `asset_id` aborts. Comment vs code diverge.

5. **Replace upload deletes old S3 object before new write succeeds**  
   `DocumentUploadService::attachFileToDocument` — failure after delete leaves empty slot and lost prior file.

6. **Documents stored plaintext on S3**  
   Encrypted filesystem adapter exists but is unused for docs; sensitive files readable at rest on S3.

7. **`destroySlot` bypasses `DocumentStorage`**  
   Raw `Storage::disk('s3')` — races with replace can orphan keys or fail deletes silently.

8. **Bulk upload race on empty checklist slots**  
   Empty-slot lookup → attach not transactional; concurrent uploads can clash.

9. **Weak / order-dependent auto-match**  
   `ChecklistFilenameMatcher::findBest` returns first fuzzy match > 85%, not best overall — wrong bulk replaces.

### Low

10. Trusted stored MIME for Content-Type.  
11. No operational-entity gate on document routes (tenancy contacts can mutate docs).

---

## Area 8 — Compliance

### High

1. **Open entity authz (compliance)**  
   Same pattern as documents: any auth user can upload/clear/stream another entity’s compliance files.

2. **Scheduled reminders may assign to wrong (first) user**  
   `ComplianceReminderService::sync()` when run unauthenticated without `--user` uses `$entity->user_id ?? User::query()->value('id')` — **first user in the table**. Wrong owner across entities.  
   **Where:** `compliance:sync-reminders` in `routes/console.php`.

3. **MySQL unique index allows duplicate entity-scoped year records**  
   Unique `(business_entity_id, asset_id, fy_start_date)` — on MySQL/MariaDB, `NULL asset_id` values do not conflict. Concurrent workspace loads can create duplicate FY records, split checklists/reminders/uploads.  
   **Where:** migration `2026_06_21_000001_*`; `ComplianceYearService::findOrCreateYearRecord()`.

### Medium

4. Same extension-only validation + SVG XSS risk on compliance stream.  
5. Replace upload delete-then-write (data loss on failure) — `ComplianceUploadService::attachFile`.  
6. `destroyFile` / inactive BAS cleanup leave S3 orphans or skip consistent storage path.  
7. **Lodged/paid status allowed with no file** — false completeness; reminders skip lodged/paid while evidence missing.  
8. **Move collision ignores template/effective labels** — uses `checklist_label` column only.  
9. **BAS frequency change can wipe empty template slots** — switching annual ↔ quarterly deletes non-progress BAS rows.  
10. Bulk/auto-match races and weak fuzzy matching (same class of issues as documents).

### Low

11. GET workspace auto-provisions years/slots when `compliance.auto_provision_on_view` — read endpoints mutate DB.  
12. Cross-entity URL mismatch on upload returns 422 rather than clean 404 (relies on service assert).

---

## Area 9 — Banking & transactions

### High

1. **Open entity authz + bank account number reveal**  
   `GET /bank-accounts/{id}/reveal-account-number` → `BankAccount::isAccessibleBy()` → `$user->can('view', $entity)` (always true). Any authenticated user who knows an id can fetch the full decrypted account number.

2. **Full account numbers rendered in bank list UI**  
   Masking/reveal pattern exists elsewhere, but `bank-accounts/partials/account-banking-details-cells.blade.php` prints `{{ $account->account_number }}` in cleartext.

3. **Bank import `saveMatches` ignores selected chart account**  
   Validates `chart_account_id` but never stores/posts to it. Type derived crudely from CoA `account_type` + amount sign; observer posts journals to wrong/global accounts.  
   **Where:** `BankImportController::saveMatches`; `mapTransactionType`; `TransactionObserver`.

4. **Bank import double-match / double-post race**  
   No check that `bank_statement_entries.transaction_id` is still null; no lock around create+link. Concurrent saves create two transactions for one statement line; both get posted.

5. **Python parser treats “balance” as amount**  
   `AMOUNT_COLUMNS` in `python/python_bank_parser.py` includes `'balance'`. Running balances imported as transaction amounts → broken reconciliation and inflated journals.

### Medium

6. Bank import endpoints largely lack authorize + incomplete operational checks (`process` / `entries` / `saveMatches`).  
7. **Statement upload uses client filename** — `storeAs(..., 'bank_statement_'.time().'_'.$file->getClientOriginalName())` — path traversal risk on local disk.  
8. Weak duplicate detection (date+amount+description only; no unique DB constraint).  
9. Ambiguous date parsing AU vs US (`%d/%m/%Y` then `%m/%d/%Y`) — `01/02/2026` can be wrong day.  
10. Cross-entity payer journals use global CoA lookup (`findAccount` by code) — misconfiguration → wrong ledgers or empty post.  
11. Inclusive GST > amount → negative net can be posted (`TransactionCashParts::resolve`).  
12. `allocateTransaction` allows conflicting bank links (warns only).  
13. `Transaction::isSplit()` true if any lines relation loaded non-empty — orphan lines force split path.  
14. Observer re-posts on every update — concurrent updates can interleave journal rebuilds.  
15. Payer options expose all entities/directors (`TransactionPayerResolver`).

### Low

16. Hard-coded `python3` — Windows/XAMPP often only has `python` → import fails.  
17. Bank statement files briefly on local disk; crash leaves sensitive files.  
18. CSV formula-injection payloads in descriptions if later exported to Excel.  
19. Masking design inconsistent with plaintext list cells (#2).

---

## Area 10 — Chart of accounts & vendors

### Medium

1. **Vendor name uniqueness is case-sensitive; linking is not**  
   DB `unique('vendors','name')` vs `VendorSyncService` case-insensitive match → `Acme` and `acme` both creatable; auto-link merges them incorrectly.  
   **Where:** `VendorController`; `VendorSyncService`.

2. **Creating COA parent does not block cycles as thoroughly as update**  
   `update()` has `parentWouldCreateCycle()`; `store()` / `validateNewAccount()` mainly check exists.

### Low

3. No authorization on COA / vendor mutations (any authenticated user).  
4. Entity-scoped COA write routes ignore the entity parameter (global COA) — suggests isolation that no longer exists.

---

## Area 11 — Invoices & rent

### High

1. **Open DB transaction on “invoice already exists”**  
   `RentInvoiceService::generateRentInvoiceForLease` calls `DB::beginTransaction()`, then on existing invoice returns failure **without** `commit`/`rollBack`. Leaves the connection in an open transaction (locks, nested work, connection pollution).  
   **Where:** `app/Services/RentInvoiceService.php` (~lines 92–100).

2. **Posting can create unbalanced journals if GST account missing**  
   `InvoicePostingService::buildLines` debits AR for full `total_amount`, but GST credit lines are skipped when `$gstPayable` is null. Debits ≠ credits; no balance check before save.

### Medium

3. **Rent invoice GST math vs manual invoices**  
   Manual invoices treat unit price as ex-GST; rent sets inclusive rent as `unit_price`/`line_total` with `gst_rate` 0.10. Posting may be OK under inclusive convention, but display/PDF/mixed types are inconsistent and easy to misread.

4. **Invoice number race / duplicates**  
   `generateInvoiceNumber` increments last `RENT{YYYYMM}%` outside a lock — concurrent generation can collide.

5. **Rounding can unbalance multi-line posts**  
   Header vs posting recompute can leave AR ≠ revenue+GST by cents.

6. **Upcoming rent lease filter drops mid-window leases**  
   `getUpcomingRentInvoices` requires `end_date >= $endDate` (end of whole horizon) — lease ending mid-forecast excluded for all months including active ones.

7. **“Record payment” is status-only**  
   Sets `paid` / `paid_at` but creates no cash/bank journal — AR stays open in ledger while UI shows paid.  
   **Where:** `InvoiceController::recordPayment`.

### Low

8. Invoice create/update not wrapped in a transaction — partial lines on failure.  
9. Duplicate “Rent” detection via `reference like '%Rent%'` is brittle.

---

## Area 12 — Commitments & tracking

### High

1. **Tracking `is_active` cannot be turned off**  
   Checkbox `value="1"`; unchecked field omitted. Controllers use `$request->boolean('is_active', true)` → always **true** when unchecked. Deactivate never sticks.  
   **Where:** `TrackingCategoryController` / `TrackingSubCategoryController` store & update.

### Medium

2. **Deleting a category cascades subcategories without usage checks on subs**  
   Destroy checks category-level usage only; unused parent with used children can cascade-delete and null tracking FKs.

3. **Commitment policy is effectively allow-all**  
   Only checks `$commitment->businessEntity !== null`. Any user can settle/delete any commitment given open entity policies.

### Low

4. Payments can exceed contract price (`balance_due` floors at 0).  
5. Settle + create asset weak validation — no reconcile to `contract_price`.

---

## Area 13 — Financial & property reports

### High

1. **Cash flow report uses wrong sign / not cash**  
   `FinancialReportService::generateCashFlow` builds “cash flow” from period GL movements. Operating net uses `$operatingIncome['total'] - $operatingExpenses['total']` while P&L correctly uses `-$income['total'] - $expenses['total']` (credit-negative income). Operating cash flow sign is inverted/wrong vs P&L; investing/financing are accrual GL deltas, not bank cash.

### Medium

2. **Missing-ITR report false positives if type missing**  
   If `ComplianceDocumentType` code `itr` is absent, every applicable entity listed as missing ITR.  
   **Where:** `ComplianceReportService::missingItrReport`.

3. **Report auth is “any authenticated user sees all entities”**  
   Policies return `true`; `ResolvesReportEntityScope` only filters to `forFinancialReports()` IDs — no per-user scope. Cross-entity financial disclosure if multi-user.

### Low

4. Portfolio empty-scope vs hub inconsistency (`[]` vs reject).  
5. Yield annualization: expenses day-factored while rent may use static `rental_income` — mixed bases.

---

## Area 14 — Email & templates

### High

1. **Send mail allows arbitrary `from_email`**  
   `MailMessageController::sendEmail` validates `from_email` as any email and passes it into `ContactEmail` — no check it belongs to the user/Gmail account. Spoofing / relay abuse.

2. **Attachment filenames used unsafely in storage paths**  
   `MsgParserService::updateMessageFromParsedData` builds `emails/{id}/attachments/{filename}` from parser output with no basename/sanitize. `../` can escape intended prefix depending on disk.

### Medium

3. Re-parse duplicates attachments (always `MailAttachment::create`, no cleanup).  
4. **Email templates shared globally** — comments say “own templates” but lists/policies do not filter `user_id`; `EmailTemplatePolicy` allows all.  
5. **Sent vs Inbox label uses hardcoded `@yourdomain.com`** — real Gmail users never match → almost everything labeled Inbox.  
6. Allocate to asset without asset authorization (IDOR-shaped; policies allow anyway).

### Low

7. Allocate attachments fail silently (user still sees success).  
8. Python parse FastAPI `/email/parse` has no auth (mitigated if bound to 127.0.0.1 only).

---

## Area 15 — Reminders / contact lists

### High

1. **`bulkComplete` skips authorization**  
   Completes any reminder IDs in the payload with no ownership filter. `ReminderPolicy` also returns `true` for all abilities.

2. **Reminder index/create/store unscoped**  
   `index` lists all reminders; `store` never authorizes; no `user_id` filter. Any user sees/creates/completes everyone’s reminders.

### Medium

3. **Repeating reminders can exceed `repeat_end_date`**  
   `Reminder::createNextReminder` compares current due date to end, then advances — last occurrence before end still spawns a date past the end. Also mutates Carbon in place via `addMonth()`.

4. **Asset can be linked across entities**  
   `asset_id` is `exists:assets,id` only — no rule that asset belongs to `business_entity_id`.

5. **Contact list ID compare without cast (CRUD)**  
   `$contactList->business_entity_id !== $businessEntity->id` (loose types). Workspace helper casts to `(int)`. String/int mismatch can 404 valid contacts.

### Low

6. `ContactListPolicy` unused — controller authorizes `BusinessEntity` only; easy to assume ownership is enforced.

---

## Area 16 — Cross-cutting / API / policies

### High

1. **`routes/api.php` bank-accounts route is outside `auth:sanctum`**  
   `GET /api/business-entities/{businessEntity}/bank-accounts` is registered **without** Sanctum (only `/user` is protected). Same action exists on authenticated web routes. Handler calls `$this->authorize('view')` which likely 403s guests today, but the route should not be public; when authenticated under permissive policies it returns BSB/account numbers. Dual registration is confusing and dangerous if auth handling changes.  
   **Where:** `routes/api.php`; `BusinessEntityController::getBankAccounts`.

2. **Policies are broadly permissive**  
   `BusinessEntityPolicy`, `ReminderPolicy`, `EmailTemplatePolicy`: nearly all methods `return true`. `AssetPolicy` / `CommitmentPolicy` / document/compliance policies: weak entity-null checks only. Authorization is effectively authentication-only across most of the app. If multi-tenant/multi-family use is ever intended, this is systemic data exposure.

### Medium

3. Sanctum SPA stateful middleware typically not applied to `api` group — cookie SPA vs token confusion; most JSON lives on web routes instead.  
4. Custom `RateLimitMiddleware` only on `api` group — heavy web JSON (send email, templates, CoA) not covered.  
5. Web vs API path duplication for bank accounts / CoA — clients can hit the wrong base URL.

### Low

6. `RateLimitMiddleware` always returns JSON 429 — poor fit if later applied to form posts.

---

## Suggested fix priority (cross-area) — open items only

| Priority | Cluster |
|----------|---------|
| 1 | Auth: admin routes still skip 2FA; API bank-accounts outside Sanctum; account-number reveal + plaintext list UI |
| 2 | Data integrity: BusinessEntity encryption shadowing; asset workspace bank-link wipe; rent invoice open transaction; FK cascades if delete guards bypassed |
| 3 | Accounting: bank import ignores CoA + double-match + balance-as-amount; invoice GST unbalanced post; cash-flow sign; record payment status-only |
| 4 | Upload security: extension-only validation + SVG inline XSS; path traversal on bank/email attachments |
| 5 | Closed/operational consistency: mutate closed entities; asset status ignored; tracking `is_active` stuck on |
| 6 | Compliance: wrong reminder owner fallback; MySQL NULL unique year records |
| 7 | Email / reminders: arbitrary `from_email`; template sharing; `bulkComplete` / unscoped reminder index |

---

## Notes

- This document is a **bug inventory**, not a commitment that every item is exploitable in production as configured.
- Shared-portfolio / allow-all policies may be intentional for a single-family office; they are still listed where they turn local bugs into cross-user issues.
- Do not reintroduce editable profile email without `email_hash` uniqueness and admin-email reservation.
- Re-verify before fixing: several Area 1–2 High items were fixed after the first draft of this file; the changelog at the top is the source of truth for those.
`)