# Balance sheet entries vs add transaction

## Rule

- **Add balance sheet entry** (`BalanceSheetEntryController`, bank panel CTA) is for capital / asset purchase and director-loan balance-sheet movements funded outside the bank statement (`director_funds`, `cash`, `external_third_party`). Always `payment_status=paid`, `bank_account_id=null`. Type allowlist: `Transaction::balanceSheetTypes()` — reuse **`asset_purchase`** (and `capital_expenditure`) for property deposits/purchases; posts to Property & Assets (Capital) `1500`. Do not add a separate `deposit_paid` type.
- **Dashboard Add transaction** remains the general P&L / bills flow (full type set, may use bank channel).
- **Bank create / reconcile / invoice payment** remain `payment_channel=bank_account`.
- Do not deep-link the bank panel CTA to `open_add_transaction` on the dashboard. Do not allow `loan_*` or `internal_transfer` on the balance-sheet entry form (loan/offset rules stay on bank flows).
