# Director funds / cash payment channel posting

## Rule

- Payment channels `director_funds` and `cash` fund operating transactions via **Director / Entity Loan (2500)**, not company bank cash GL. `bank_account` still uses cash when a bank is linked. `external_third_party` is unchanged (still cash-side until product defines otherwise).
- Paid rows with `payment_channel=bank_account` but `bank_account_id=null` (inconsistent) also fund via 2500.
- Priority in `TransactionPostingService::fundingSideLine`: (1) explicit `director_loan_*` types use existing loan booking, (2) cross-entity `paid_by=be:{other}` → intercompany 2500 + payer journal, (3) director-funds funding rules above → 2500, (4) else cash.
- Entity/asset transaction lists: when `bank_account_id` is null, show `Transaction::nonBankFundingAccountLabel()` (paying entity name if `paid_by` is `be:…`, else **"Director funds"**). Do not show "Unassigned".
- After changing funding-side rules, re-post with `php artisan journals:repost-paid-transactions --channels=director_funds,cash`.
