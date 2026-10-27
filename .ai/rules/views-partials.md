---
paths:
  - resources/views/partials/dashboard-transaction-lines.blade.php
---

# Views Partials

## Dashboard allocations use CoA pickers
Dashboard allocations pick Chart of Accounts only (active accounts via ChartOfAccount::activeForSelect / activePnlForSelect alias), not Transaction::typeSelectGroups(). Entity dashboards pass the entity id so firm/shared + that entity’s owned accounts appear; firm-wide callers stay on shared accounts only. Persist chart_of_account_id; derive transaction_type via ChartAccountTransactionTypeMapper. Split lines also store chart_of_account_id for posting overrides. Income/expense direction still controls cash sign; P&L↔BS class flips remain ignored at post time.
