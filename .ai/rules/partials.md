---
paths:
  - 'resources/views/bank-accounts/partials/**'
---

# Partials

## Treat loan statements as liability activity
Label loan-purpose CSV imports as "loan activity", not cash reconciliation. Offer Loan Interest, Loan Fees, Loan Repayment, and Director Loan In/Out create types; do not offer chart-account creates. Keep loan pending counts distinct from operating cash unmatched counts.

## Offset statement pickers exclude loan economics
For offset-purpose accounts, build create-type options with typeSelectGroupsForBankAccount() (no Loan group). Surface a short note that interest/fees/repayments belong on the linked loan account and offset↔loan cash moves are Internal transfer.

## Reconciliation Change panel order
Match existing | Or create as type, then Or create from chart account (cash/offset only), then Create markers. Keep labels and layout stable — do not move chart accounts above Match existing.
