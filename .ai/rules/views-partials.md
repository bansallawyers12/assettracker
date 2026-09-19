---
paths:
  - resources/views/partials/dashboard-transaction-lines.blade.php
---

# Views Partials

## Dashboard allocations use CoA only
Dashboard allocations pick Chart of Accounts only (active income/expense rows), not Transaction::typeSelectGroups(). Persist chart_of_account_id; derive transaction_type via ChartAccountTransactionTypeMapper. Split lines also store chart_of_account_id for posting overrides.

## Dashboard allocations use CoA only
Dashboard allocations pick Chart of Accounts only (active income/expense rows plus Director Loan 2500 for both directions), not Transaction::typeSelectGroups(). Persist chart_of_account_id; derive transaction_type via ChartAccountTransactionTypeMapper. Split lines also store chart_of_account_id for posting overrides.
