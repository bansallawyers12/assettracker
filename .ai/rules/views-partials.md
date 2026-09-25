---
paths:
  - resources/views/partials/dashboard-transaction-lines.blade.php
---

# Views Partials

## Dashboard allocations use full CoA
Dashboard allocations pick Chart of Accounts only (all active accounts via ChartOfAccount::activeForSelect / activePnlForSelect alias), not Transaction::typeSelectGroups(). Persist chart_of_account_id; derive transaction_type via ChartAccountTransactionTypeMapper. Split lines also store chart_of_account_id for posting overrides. Income/expense direction still controls cash sign; P&L↔BS class flips remain ignored at post time.
