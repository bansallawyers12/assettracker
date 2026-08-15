# Loan vs offset accounts

## Rule

- **Loan account** owns loan economics: `loan_interest`, `loan_fees`, `loan_repayments`. Treat its CSV as loan activity, not cash reconciliation. Loan-ledger transactions must not post to Bank/Cash (`1100`).
- **Offset account** is cash. Money moved between offset and loan is `internal_transfer`.
- Never book `loan_*` types on an offset-purpose account when a linked loan exists; `LoanOffsetTransactionGuard` enforces this.
- Internal transfers require `counterpart_bank_account_id` on manual create; import may omit it and optionally resolve the asset’s linked loan.
- Offset is included in `BankAccount::ENTITY_OPERATING_PURPOSES` so statements can be reconciled after guards exist.
- **Posting:** `loan_interest` / `loan_fees` capitalise (Dr expense / Cr 4000 — no 1100). `loan_repayments` on the loan account do not post. Offset↔loan `internal_transfer` posts on the **cash/offset side only** (Dr 4000 / Cr 1100 when cash leaves; reverse on redraw). Offset transfers still post when import omitted `counterpart_bank_account_id`. Cash↔cash internal transfers remain a wash (no journal). Re-post after statement match (`postAfterStatementLinked`) so abs(amount) imports pick up the statement sign. Re-post existing rows with `php artisan journals:repost-paid-transactions --types=loan_repayments,internal_transfer`.
- Loan-purpose import UI uses `Transaction::loanActivityTypeSelectGroups()` and “Update loan activity”. Do not mix loan pending lines into operating cash unmatched totals.
