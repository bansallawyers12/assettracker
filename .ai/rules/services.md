---
paths:
  - app/Services/TransactionPostingService.php
  - app/Services/BankStatementApplyService.php
  - 'app/Services/**'
  - 'app/Services/{FinancialReportService,BankAccountBalanceSnapshotService}.php'
  - app/Services/ManualJournalEntryService.php
  - app/Services/FinancialReportService.php
---

# Services

## Loan ledger never posts to Bank/Cash
loan_repayments on a loan-purpose account unpost (no 1100). Offset↔loan internal_transfer posts 1100↔4000 only on the cash/offset side. loan_interest/fees still capitalise to 4000. After deploy, run journals:repost-paid-transactions --types=loan_repayments,internal_transfer.

## Explicit director loans always post to 2500
All explicit director-loan transaction types (`director_loan_in`, out, repayment, and legacy aliases) post to Director / Entity Loan account 2500. Ignore stale `chart_of_account_id` overrides for these types; re-post affected paid rows after deployment.

## Keep imported funding types distinct
When creating transactions from chart-account reconciliation: account 2500 maps to director_loan_in/out; other liability inflows map to loan_drawdown; equity inflows map to equity_contribution. Do not use director_loan_in as a generic positive liability/equity type.

## Journal posting invariants: every type maps, every GL account exists, no unbalanced entries
P&L and the balance sheet only read journals where is_posted is true AND total_debit = total_credit, so a journal that is short one line disappears from both reports instead of failing loudly.

- Any new `Transaction` type added to `$incomeTypes` / `$expenseTypes` MUST get an entry in `TransactionPostingService::counterAccountMapping()`, otherwise paid rows never post. `tests/Feature/JournalPostingIntegrityTest.php` fails if a type is unmapped.
- The GL accounts posting depends on (1100 cash, 1130 AR, 2500 director loan, 4000 long-term loans, 2100 GST payable, 1140 GST receivable) are created on demand by `ensure*Account()` helpers. Do not reintroduce nullable lookups for them or `if ($accounts['x'])` guards — the array shape is non-nullable.
- `persistJournalEntry()` returns false and saves nothing when debits and credits differ by more than half a cent. Callers must return null on false.

## Explicit director-loan types follow the payment channel, not always 1100
`director_loan_in` / `director_loan_out` (and the other explicit director-loan types) fund from Bank/Cash 1100 ONLY when the transaction has a non-loan bank account with the bank_account channel. On a loan-purpose account they fund from Long Term Loans 4000 (in reduces the bank loan; out is a redraw), including when paid_by is another entity — do not debit AR 1130 for money that hit the loan facility. Never debit 1100 for director_loan_* on a loan ledger.

Funded outside the bank (director_funds / cash channel, or bank_account with a null bank_account_id) both legs post to 2500: the entry is visible on the account listing but moves neither cash nor the loan balance. This is deliberate — such an entry has no real second side, and debiting 1100 used to inflate Bank/Cash for money that never entered the bank. Money the director actually paid on the entity's behalf should be booked with the real expense or `asset_purchase` type plus `director_funds`, which credits 2500 properly.

Do not add synthetic 2500 lines for these types in FinancialReportService — `buildDirectorEntityLoanAccountBlock` excludes them on purpose and reads their real GL.

## Bank/Cash stays one GL (1100); per-account detail is a memo, not a sub-ledger
Do not split 1100 into per-purpose GL codes. Offset money is cash, so splitting changes presentation only and costs a migration, a full repost, reallocating manual journals/opening balances, and a wider isBankOrCashChartAccount heuristic.
The balance sheet 1100 row instead carries an optional `bank_breakdown` key (see FinancialReportService::bankCashBreakdown), attached only to the `financial.report_accounts.bank_cash` code and rendered only when not comparing. Lines come from BankAccountBalanceSnapshotService::entityBankBalancesAsOf() — the same book balances as the bank panel, entity-scoped, excluding loan-purpose accounts, dated paid_at ?? date to match journal entry dates.
journal_lines has no bank_account_id, so this can never tie exactly: the remainder is shown as `unattributed` (director funds, cross-entity, GST-exclusive differences). Do not present it as a GL sub-total or try to reconcile it by re-deriving funding-side posting rules in the report.

## Bank/Cash memo allocation follows bank ownership and journal cash amounts
The 1100 memo is scoped by bank_account.business_entity_id, not transaction.business_entity_id, so cross-entity cash belongs to the bank owner. Allocate Transaction::cashParts()['cash'] (statement direction only for transfers), exclude loan-purpose accounts, and show both legs of cash-to-cash transfers without changing 1100. Unresolvable/manual differences remain explicitly labelled as reconciliation differences. Keep comparison and CSV breakdowns aligned; skip breakdown work in entity-summary generation.

## Supersedes the older Bank/Cash memo implementation note
The earlier memo note saying it is transaction-entity scoped, hidden in comparisons, and expects cross-entity/GST differences is obsolete. Follow “Bank/Cash memo allocation follows bank ownership and journal cash amounts”; comparative and CSV output must include the allocation.

## Edit in place; reverse and void offset
Manual journals (source_type null) are edited in place on the same id. Reverse posts a new posted journal with flipped D/C, user-chosen date, and reverses_journal_entry_id pointing at the original — do not set source_type to JournalEntry or the offset drops out of the manual register. Void is a reverse on the original date plus voided_at on the original; both stay posted so the GL nets to zero. Do not delete. Cannot edit/reverse/void if already reversed or voided; cannot void a reversal (void the original). Opening-balance OPEN- references stay on edit.

## Director loan account-transactions rebuild
Account transactions for 2500 still use `buildDirectorEntityLoanAccountBlock`: opening is explicit/manual 2500 GL as of the day before start plus synthetics before start; closing adds in-period explicit/manual 2500 journal lines (`directorLoanManualGlReportLines`). Do not add synthetics for explicit `director_loan_*` types. Do not synthesise bank-received operating income (rent in an offset/bank) onto 2500, even when the bank owner differs from the booking entity. Do not feed this rebuild into balance sheet totals.

## Director loan on loan ledger posts 4000
director_loan_in/out on a loan-purpose bank account post Long Term Loans 4000 ↔ Director Loan 2500. Do not use Bank/Cash 1100 — the money never hit an operating/offset account. In reduces 4000; out is a redraw that increases 4000.

## Balance sheet 2500 is posted GL
Balance sheet 2500 is posted GL (debit−credit as-of), same as 4000/1100. Do not strip 2500 or replace it with buildDirectorEntityLoanAccountBlock synthetics — that rebuild is only for the 2500 account-transactions listing. Entity-summary director loan figures also use getAccountBalanceAsOf.

## Bank rent is not a 2500 synthetic
Do not put bank-received operating income (rent, etc.) on the 2500 account-transactions listing. Skip when the row has a bank_account_id and is not director_funds/cash — including third-party payment_channel and paid_by be:{other}. Synthetics stay director_funds/cash/orphan-bank operating posts and explicit paid_by be:{other} expenses (and income that never hit a bank). Never treat a different bank owner as a director-loan counterparty.
