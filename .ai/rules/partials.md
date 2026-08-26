---
paths:
  - 'resources/views/bank-accounts/partials/**'
---

# Partials

## Treat loan statements as liability activity
Label loan-purpose CSV imports as "loan activity", not cash reconciliation. Offer only Loan Interest, Loan Fees, or Loan Repayment create types; do not offer chart-account creates. Keep loan pending counts distinct from operating cash unmatched counts.

## Reconciliation Change panel: three different pickers
- **Create from chart of accounts** = active GL accounts (listbox). Cash/offset only.
- **Match existing transaction** = unmatched booked `transactions` rows — never chart accounts.
- **Or create as type** = transaction type (e.g. Internal Transfer).
