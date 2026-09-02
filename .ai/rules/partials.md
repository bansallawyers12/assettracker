---
paths:
  - 'resources/views/bank-accounts/partials/**'
---

# Partials

## Treat loan statements as liability activity
Label loan-purpose CSV imports as "loan activity", not cash reconciliation. Offer Loan Interest, Loan Fees, Loan Repayment, and Director Loan In/Out create types; do not offer chart-account creates. Keep loan pending counts distinct from operating cash unmatched counts.

## Reconciliation Change panel order
Match existing | Or create as type, then Or create from chart account (cash/offset only), then Create markers. Keep labels and layout stable — do not move chart accounts above Match existing.
