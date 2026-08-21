---
paths:
  - app/Services/TransactionPostingService.php
  - app/Services/BankStatementApplyService.php
  - 'app/Services/**'
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
`director_loan_in` / `director_loan_out` (and the other explicit director-loan types) fund from Bank/Cash 1100 ONLY when the transaction has a bank account with the bank_account channel.

Funded outside the bank (director_funds / cash channel, or bank_account with a null bank_account_id) both legs post to 2500: the entry is visible on the account listing but moves neither cash nor the loan balance. This is deliberate — such an entry has no real second side, and debiting 1100 used to inflate Bank/Cash for money that never entered the bank. Money the director actually paid on the entity's behalf should be booked with the real expense or `asset_purchase` type plus `director_funds`, which credits 2500 properly.

Do not add synthetic 2500 lines for these types in FinancialReportService — `buildDirectorEntityLoanAccountBlock` excludes them on purpose and reads their real GL.
