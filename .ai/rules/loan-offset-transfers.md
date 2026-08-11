# Loan vs offset accounts

## Rule

- **Loan account** owns loan economics: `loan_interest`, `loan_fees`, `loan_repayments`.
- **Offset account** is cash. Money moved between offset and loan is `internal_transfer` (no P&L journal).
- Never book `loan_*` types on an offset-purpose account when a linked loan exists; `LoanOffsetTransactionGuard` enforces this.
- Internal transfers require `counterpart_bank_account_id` on manual create; import may omit it and optionally resolve the asset’s linked loan.
- Offset is included in `BankAccount::ENTITY_OPERATING_PURPOSES` so statements can be reconciled after guards exist.
