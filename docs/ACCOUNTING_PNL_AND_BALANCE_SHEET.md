# Profit & Loss, Balance Sheet, and the journals that feed them

This note describes how entity Profit & Loss and Balance Sheet reports are produced in Asset Tracker, and which transaction / bank-account behaviours create the underlying accounting entries. It is scoped to that pipeline only.

**Primary code**

- Posting: `app/Services/TransactionPostingService.php`, `app/Services/LoanOffsetTransactionGuard.php`, `app/Observers/TransactionObserver.php`
- Reports: `app/Services/FinancialReportService.php` (`generateProfitLoss`, `generateBalanceSheet`)
- Off-statement capital / director-loan capture: `app/Http/Controllers/BalanceSheetEntryController.php`
- Opening / manual journals: `app/Services/ManualJournalEntryService.php`
- Chart: `database/seeders/ChartOfAccountSeeder.php`, `config/financial.php`

---

## 1. Mental model

The product is **paid-transaction double-entry**, not full accrual.

1. A **transaction** is the operational record (type, amount, GST, bank, payment channel, paid-by).
2. When `payment_status = paid`, `TransactionPostingService` writes one or two **journal entries** (balanced debit/credit lines against the global chart of accounts).
3. **Unpaid** rows are obligations only: any existing journals are removed. They do **not** appear on P&L or the balance sheet until paid.
4. P&L and the balance sheet **do not sum transactions**. They sum **posted, balanced** `journal_lines` for the selected entity (or entities), with extra reconstruction for director / entity loan (account **2500**) and some bank-cash edge cases.

Invoices have a separate posting path (`InvoicePostingService`). Receipt of an invoice is a transaction of type `invoice_payment`, which clears Accounts Receivable (**1130**) against cash or director loan.

---

## 2. Ledger objects

| Object | Role |
| --- | --- |
| `chart_of_accounts` | **Global** chart (not per entity). Types: asset, liability, equity, income, expense. |
| `journal_entries` | Header per entity: date, reference (`TXN-########` or `TXN-########-PAY`), `source_type`/`source_id` pointing at the transaction, `is_posted`, `total_debit` / `total_credit`. |
| `journal_lines` | Debit/credit against one CoA code. |
| `transactions` | Source document. Journals date from `paid_at`, else `date`. |
| `bank_accounts` | Operational accounts. Purpose (`general`, `loan`, `offset`, rent, …) changes **which journals are allowed**, not which CoA cash account is used. **All cash-like banks post to the same GL 1100.** |

`chart_of_accounts.opening_balance` and `current_balance` are **not** used by P&L/BS. Opening positions belong in **manual opening-balance journals** (offset **3190** Opening Balance Equity). `current_balance` is unused stale storage.

Reports ignore journals where `is_posted` is false **or** `total_debit ≠ total_credit`. Because such an entry would silently vanish from both reports, `persistJournalEntry` **refuses to save an unbalanced set of lines** (logs an error and removes any existing entry instead). Posting also **creates** 1100, 2500, 4000, 2100, and 1140 when the chart is missing them, so a journal is never written one line short.

---

## 3. Chart of accounts that matter

Canonical codes (from `ChartOfAccountSeeder` / `config/financial.php`):

| Code | Name | Report home |
| --- | --- | --- |
| 1100 | Bank / Cash Account | BS current asset — **all** general, offset, and rent banks |
| 1130 | Accounts Receivable | BS; invoice unpaid / payment clearing |
| 1140 | GST Receivable | BS; GST on purchases |
| 1500 | Property & Assets (Capital) | BS fixed asset (`asset_purchase`, `capital_expenditure`) |
| 1590 | Accumulated Depreciation | BS contra-asset |
| 2100 | GST Clearing | BS; GST on sales and BAS payments |
| 2120 / 2130 | PAYG / Super payable | BS |
| 2500 | Director / Entity Loan | BS liability (or asset if the entity is owed) — **special report logic** |
| 3100 | Owner Drawings | BS equity (`directors_fees`, `other_personal_expenses`) — **not P&L** |
| 3190 | Opening Balance Equity | BS equity; opening journals only |
| 3200 | Share Capital | BS (`equity_contribution`) |
| 4000 | Long Term Loans | BS; bank mortgages / offset↔loan transfers |
| 4100–4900 | Income | P&L |
| 5100–5900, 7500 | Expenses | P&L (`loan_interest` → **7500**) |

P&L groups by `account_category` (operating vs other income/expense). The balance sheet groups assets / liabilities / equity the same way, then **injects**:

- Director loan **2500** from a reconstructed ledger (not raw 2500 journal sums).
- **Accumulated Earnings (computed)** = cumulative income + expense GL to the as-of date (QuickBooks-style; income/expense codes themselves are not listed on the BS).

Net profit on the P&L is `−income_total − expense_total` because GL totals are **debit minus credit** (income credits are negative).

---

## 4. How a transaction becomes a journal

`TransactionObserver` posts on create, on relevant updates, and unposts on delete. Bank-statement linking can re-post (`BankStatementApplyService::postAfterStatementLinked`). Paid journals can be rebuilt with:

`php artisan journals:repost-paid-transactions`

### 4.1 Same-entity, bank channel (typical P&L)

One booking journal `TXN-{id}` on the booking entity.

**Income** (rent, sales, …):

- Dr **1100** Bank/Cash (gross cash)
- Cr income account (net)
- Cr **2100** GST Clearing (GST, if any)

**Expense** (rates, water, …):

- Dr expense account (net)
- Dr **1140** GST Receivable (GST, if any)
- Cr **1100** Bank/Cash (gross)

Optional `chart_of_account_id` on the transaction **overrides** the type→CoA map (except explicit director-loan types, which always stay on **2500**).

### 4.2 Funding side (what replaces 1100)

`fundingSideLine` priority:

1. **Cross-entity** `paid_by = be:{other entity}` → **2500** on the booker, plus a second journal on the payer.
2. **Director funds / cash channel**, or `payment_channel = bank_account` with **null** `bank_account_id` → **2500** (not company cash).
3. **Loan interest / fees** on a **loan-purpose** bank account → **4000** (capitalised; no cash).
4. **Loan repayments** on a **loan-purpose** account → **no journal** (cash is expected to move via offset transfer).
5. Else **1100** cash.

`external_third_party` still uses **1100** (product has not redefined it).

### 4.3 Cross-entity pay (`paid_by = be:{payer}`)

Two journals, same transaction source:

| Journal | Entity | Typical expense |
| --- | --- | --- |
| `TXN-########` | Booking (who the cost/income belongs to) | Dr expense, Cr **2500** |
| `TXN-########-PAY` | Paying entity | Dr **2500**, Cr **1100** |

Income is mirrored (booker Dr **2500** / Cr income; payer Dr **1100** / Cr **2500**). Explicit director-loan types use **1130** AR instead of 1100 on the booker when the payer is another entity.

### 4.4 Dedicated “Add balance sheet entry”

`BalanceSheetEntryController` is **not** a second ledger. It creates a normal paid transaction with:

- `bank_account_id = null`
- `payment_channel` in `director_funds` / `cash` / `external_third_party` (never bank)
- Types limited to `Transaction::balanceSheetTypes()`: asset purchase / capex and director-loan types
- No GST, no vendor, `paid_at = date`

**Do not** use this form for `loan_*` or `internal_transfer` — those stay on bank / statement flows.

Intended asset example (director pays a deposit outside the bank):

- Dr **1500** Property & Assets  
- Cr **2500** Director / Entity Loan  

### 4.5 Splits

Header cash (or 2500) is posted **once** from the header amount and net income/expense direction. Each allocation line posts its income/expense (or 2500 if that line is a director-loan type). If any allocation type has no CoA mapping, **the whole journal is aborted** so cash is never posted without matching lines.

---

## 5. Bank account purposes vs GL

Bank **purpose** is an operational constraint. The cash GL is still **1100** for every non-loan account.

| Purpose | Behaviour |
| --- | --- |
| **General** (and rent receiving/paying) | Normal P&L/BS journals. `loan_repayments` here **do** post: Dr **4000**, Cr **1100**. `loan_interest` / `loan_fees` here are treated as **cash expenses**, not capitalised to 4000. |
| **Offset** | Treated as cash (1100). Guard **blocks** classifying offset lines as `loan_interest`, `loan_fees`, or `loan_repayments`. Move money with **Internal transfer**. Offset↔loan transfer posts on the **offset/cash side only**. |
| **Loan** (`account_purpose = loan`) | Not cash. Import types limited to loan activity. **Repayments do not post** (no 1100). Interest/fees: Dr P&L, Cr **4000**. |

### 5.1 Internal transfer

- **Cash ↔ cash** (general↔general, general↔offset): **no journal**. All of those accounts share 1100, so a transfer is a wash on the entity books.
- **Offset/cash ↔ loan**: journal **only on the cash/offset transaction**:
  - Money leaving offset to loan (statement negative / repayment): Dr **4000**, Cr **1100**
  - Redraw loan → offset: Dr **1100**, Cr **4000**
- The matching line on the **loan** ledger is not posted again (avoids doubling 4000).

Direction uses `bankAccountSignedAmount()`: linked statement sign wins; otherwise type defaults to money leaving the account.

**4000** is created on demand (`ensureLongTermLoansAccount`), so a chart that never seeded it no longer silences these transfers.

---

## 6. Typical journals (cheatsheet)

Assume GST-free unless noted. Amounts are absolute cash.

| What the user did | Journal |
| --- | --- |
| Rent in on general/offset bank | Dr 1100 / Cr 4100 |
| Water paid from general bank | Dr 5100 / Cr 1100 |
| Same, GST inclusive | Dr 5100 (net), Dr 1140 (GST), Cr 1100 (gross) |
| Expense paid with director funds | Dr expense / Cr 2500 |
| Asset purchase via balance-sheet entry (director funds) | Dr 1500 / Cr 2500 |
| Mortgage interest on **loan** account | Dr 7500 / Cr 4000 |
| Mortgage fee on **loan** account | Dr 5900 / Cr 4000 |
| Repayment on **loan** account | *(none)* |
| Internal transfer offset → loan | Dr 4000 / Cr 1100 (on the offset row) |
| Loan repayment typed on **general** bank | Dr 4000 / Cr 1100 |
| Loan drawdown (import) | Dr 1100 / Cr 4000 |
| Equity contribution | Dr 1100 / Cr 3200 |
| `director_loan_in` (explicit type, bank channel) | Dr 1100 / Cr 2500 |
| `director_loan_out` / repayment (explicit, bank channel) | Dr 2500 / Cr 1100 |
| Either, funded outside the bank (director funds / cash / no bank) | Dr 2500 / Cr 2500 — recorded, nets to nil, no cash |
| Invoice raised (unpaid) | Invoice service: Dr 1130 / Cr income (+ GST) |
| `invoice_payment` | Dr 1100 (or 2500) / Cr 1130 |
| Personal drawings / directors fees | Dr 3100 / Cr 1100 or 2500 — **equity, not P&L** |
| BAS payment | Dr 2100 / Cr 1100 |
| ASIC fee (`asic_payment`) | Dr 5900 (or “ASIC Fees” if that account exists) / Cr 1100 |
| Unpaid bill | No journal |

---

## 7. How Profit & Loss is built

`generateProfitLoss($entityIds, $start, $end)`:

1. Load active **income** and **expense** CoA rows.
2. For each, `getAccountBalance` = sum(debits) − sum(credits) on journal lines whose entry is in the date range, for those entities, posted and balanced.
3. Hide near-zero rows unless “show zeros”.
4. `net_profit = −income_total − expense_total`.

**What hits P&L:** rent and other income types; operating expenses; **loan interest** (7500) and **loan fees** (5900); GST is **not** in P&L (it sits on 1140/2100).

**What does not hit P&L:** asset purchase (1500), loan principal (4000), internal transfers, invoice receipts (1130), director-loan principal (2500), drawings (3100), equity (3200), unpaid transactions, unbalanced journals.

Period is journal `entry_date` (`paid_at` / `date` of the transaction), not the bank-statement period independently.

---

## 8. How the Balance Sheet is built

`generateBalanceSheet($entityIds, $asOfDate)`:

1. **Assets** (except special-cased 2500): cumulative GL to as-of date. Bank/cash (**1100**, and any current asset whose name contains “bank” or “cash”) **drops** cash lines that were posted on the **booking** entity when `paid_by` is another entity (cash actually sits on the payer’s `TXN-…-PAY` journal). If that PAY journal is missing, a **synthetic** payer cash amount is added so the BS still moves.
2. **Liabilities** skip raw **2500**, then **2500 is appended** from `buildDirectorEntityLoanAccountBlock` (see below).
3. **Equity** from GL, plus **Accumulated Earnings** = all-time income + expense GL through as-of (same basis as lifetime P&L).
4. Display check: `total_assets` vs `−(liabilities + equity)` (credit balances are negative in D−C). The Blade view flags an imbalance if those differ by more than 1 cent.

Director loan on the BS:

- Closing “amount owed” comes from **synthetic 2500 activity** (cross-entity `be:` flows, income received into another entity’s bank, same-entity director_funds/cash operating posts) **plus** real GL for **explicit** `director_loan_*` types and **manual** journals.
- Auto-posted operating 2500 lines are **not** double-counted with synthetics.
- If the reconstructed balance is a **receivable** (entity is owed) **and** bank GL net happens to equal that amount, the receivable line is **omitted** (`directorLoanLenderPositionSettledInBankGl`) to avoid double-counting cash. That heuristic can hide a real 2500 asset when cash just happens to match.

Account 2500 on the **Account transactions** report uses the same reconstructed block, not a raw 2500 GL listing.

---

## 9. Issues and risks

Severity: **High** = wrong P&L/BS numbers or silent missing journals; **Medium** = easy user/data mistakes or misleading presentation; **Low** = hygiene / incomplete CoA.

### Fixed

These were live defects and have been closed in code (covered by `tests/Feature/JournalPostingIntegrityTest.php`):

- **Missing GST / long-term-loan accounts silently broke journals.** GST lines were skipped when 1140 / GST payable did not exist, leaving debit ≠ credit — and reports **exclude** unbalanced entries, so the whole transaction disappeared. Offset↔loan transfers wrote nothing without 4000, and `loan_repayments` / `loan_drawdown` had no counter account at all. Posting now creates 2100, 1140, and 4000 on demand, the same way it already created 1100, 1130, and 2500.
- **Unbalanced journals could be persisted.** `persistJournalEntry` now logs an error and saves nothing (deleting any existing entry) instead of writing a row that both statements ignore.
- **`asic_payment` never posted** — it had no entry in `counterAccountMapping()`. It now maps to “ASIC Fees” (**5125**, added to the canonical seeder) and falls back to Other Expenses / 5900 where that account is absent. A test asserts every postable type has a counter account, so the next new type cannot repeat this.
- **GST payable no longer falls back to code 2200**, which is an unrelated asset account under the optional `xero:chart-of-accounts` chart.
- **Explicit director-loan types ignored the payment channel.** `director_loan_in` / `director_loan_out` always debited or credited **1100**, so a balance-sheet entry with the default `director_funds` channel inflated Bank/Cash for money that never went through the bank. The funding side now follows the same rule as operating types: outside the bank, both legs are **2500**, so the entry is recorded but moves neither cash nor the loan balance. The balance sheet already excludes these types from its synthetic 2500 lines and reads their real GL, so nothing is double-counted.
- **Chart of accounts repaired in this environment** (`db:seed --class=ChartOfAccountSeeder`): 1140, 1590, 2120, 2130, 3120, 3190, 3200, 4000, 5170, 5180, 5195, and 7500 were missing, so wages, super, PAYG, equity, and loan-principal types could not post at all.

### High

1. **A director loan funded outside the bank now records nothing.** By design (see Fixed above), `director_loan_in` / `director_loan_out` with the `director_funds` / `cash` channel posts Dr 2500 / Cr 2500 — visible on the 2500 account listing, but the balance does not move. If a director genuinely lent money **into** the bank, the entry must be made from the bank account so it posts Dr 1100 / Cr 2500. If they paid a cost on the entity’s behalf, use the actual expense or `asset_purchase` type with `director_funds`, which credits 2500 properly.

2. **Loan repayment on the loan account is a no-op.** Correct only if the cash movement is also booked as an **internal transfer on offset/general**. If staff only tick `loan_repayments` on the loan statement, **neither cash nor 4000 changes**. Easy to understate repayments.

   Posting is deliberately left alone: blocking the row fights bank imports (one side routinely arrives before the other), and auto-posting the cash side has to un-post itself when the offset row appears, which double-counts the repayment whenever matching is imperfect. Instead `php artisan loans:audit-unmatched-repayments` lists loan-ledger repayments with no matching outgoing cash-side transfer. Matching is one-to-one within the same entity, amount, resolved loan counterpart, and `--days` window; the counterpart may be explicit or inferred from the offset account's asset-linked loan when an import omitted it. It writes nothing and exits non-zero when it finds gaps, so it can be scheduled as a check.

3. **Paid-basis P&L.** Unpaid supplier bills never hit expenses. That is consistent with the observer, but it is **not** accrual accounting. Period P&L can be wrong for management/tax if bills are left unpaid.

### Medium

4. **Single cash GL (1100).** Offset, operating, and rent accounts share one GL account, so the ledger itself cannot distinguish them and transfers between them are invisible. Splitting the GL per purpose was rejected: offset money **is** cash, so it changes presentation only, at the cost of a migration, a full repost, reallocating existing manual journals and opening balances, and widening the `isBankOrCashChartAccount` name heuristic that both the balance sheet and the 2500 reconstruction depend on.

   The balance sheet instead shows a **memo breakdown** under Bank/Cash: one line per bank account, from `BankAccountBalanceSnapshotService::entityBankBalancesAsOf()`. It is scoped by **bank-account ownership** (so cross-entity payments appear under the entity whose bank actually moved), dated the same way journals are (`paid_at` falling back to `date`), and uses `Transaction::cashParts()['cash']` so GST-exclusive rows use the same amount as posting. Loan-purpose accounts are excluded because they belong to 4000; cash-to-cash transfers allocate both the source and counterpart while leaving 1100 at nil. Because `journal_lines` records a chart account and **not** a bank account, manual journals and rows whose bank cannot be resolved remain under **Unallocated / reconciliation difference**. The breakdown appears in single-period, comparative, and CSV reports; entity-summary generation skips it to avoid repeated all-history scans.

5. **Director loan BS is reconstructed, not raw 2500.** If synthetics disagree with journals (legacy data, missing PAY journal, unpaid_by patterns), 2500 on the BS can differ from 2500 on a generic trial balance. The Account transactions screen for 2500 follows the reconstruction, which hides the discrepancy. The synthetic cash scale now comes from the same `Transaction::cashParts()` helper the posting service funds journals with, so at least GST-exclusive rows cannot drift.

6. **`directorLoanLenderPositionSettledInBankGl`.** Omits a 2500 **asset** when bank net ≈ that receivable. Coincidence of cash and loan receivable can drop a real asset and make the sheet look balanced while 2500 is incomplete.

7. **Interest/fees on a non-loan bank** post as cash P&L (Dr expense Cr 1100), not capitalise to 4000. Recording interest on the offset/general account (if the guard is bypassed or type is used on general) **understates the loan** and **overstates cash outflow**.

8. **`chart_of_account_id` overrides** can send a P&L type to a BS account (or the reverse), except director-loan types. Imports that stamp a chart account can bypass the type map.

9. **Drawings vs P&L.** `directors_fees` and `other_personal_expenses` post to **3100** equity. They never appear on P&L. If users classify personal costs as “other expenses” (5900) instead, P&L is overstated.

10. **CoA `current_balance` is unused**; opening CoA field is unused. Anyone reading those columns will not match reports.

11. **1150 Deposits Paid** is intentionally unreachable from the transaction forms. `.ai/rules/balance-sheet-entries.md` settles deposits on `asset_purchase` → **1500** (a deposit and a settlement payment are the same gesture here — capital spent on an asset — and the asset link identifies the property), and `tests/Feature/BalanceSheetEntryFlowTest.php` asserts the posting service references neither `TYPE_DEPOSIT_PAID` nor `findByName('Deposits Paid')`. The account stays **active** for the occasional manual journal (Dr 1150 / Cr 2500 on exchange, Dr 1500 / Cr 1150 at settlement) when committed-but-unsettled funds need to be visible. Automating it was rejected: it needs a deposit marker the form rule bans, and it adds a settlement reclass that silently understates the property whenever someone forgets it. Not a defect.

### Low

12. Many miscellaneous expense types collapse to **5900**. P&L granularity is coarse for those.

13. `wages_superannuation` maps entirely to **5170**, not 5180.

14. Consolidated P&L/BS simply **sums entity journals**. Intercompany 2500 legs can remain on a consolidated sheet unless they net out; they are not auto-eliminated as a group.

15. Most other tests in this area are string/reflection assertions rather than post-then-report assertions. `tests/Feature/JournalPostingIntegrityTest.php` is now a real database test (sqlite in-memory), so further report cases can be added the same way.

---

## 10. Practical checks

After posting, for one entity:

- P&L net profit for “all time” should explain the **Accumulated Earnings** line on the BS (same income+expense GL; P&L is period-bounded, earnings are cumulative).
- BS Assets should equal Liabilities + Equity (UI already tests this to 1 cent).
- Offset → loan transfer: 1100 down, 4000 down (liability credit reduced = less owed).
- Loan interest on loan account: P&L interest up, 4000 liability up; 1100 unchanged.
- Director-funded opex: P&L expense up, 2500 liability up; 1100 unchanged.
- If a paid transaction has no `TXN-########` journal, posting returned empty lines (unmapped type, loan-ledger repayment, cash↔cash transfer) or the lines were refused as unbalanced — the latter is logged as `refusing to persist an unbalanced journal`.

Repair after rule changes:

```text
php artisan db:seed --class=ChartOfAccountSeeder
php artisan journals:repost-paid-transactions
```

Use `--types=` / `--channels=` / `--entity=` when only a slice should be rebuilt.
