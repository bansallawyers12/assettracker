---
paths:
  - app/Models/ChartOfAccount.php
  - app/Http/Controllers/ChartOfAccountController.php
  - config/financial.php
---

# Http Controllers

## System CoA codes lock code type and category
`ChartOfAccount::systemAccountCodes()` merges `config('financial.report_accounts')` with `config('financial.system_account_codes')` (extras like 1130 and 2500). Those accounts lock code/type/category on update via `isSystemAccount()`. Name/description/parent/active stay editable. SPA uses `is_system_account` and shows report placement hints from `reportPlacementHints()` by category. Do not let posting-critical codes change code/type/category.

## CoA UI is firm-wide; stored balances unused
The chart is shared across entities. Legacy `business-entities.*.chart-of-accounts.*` GET routes redirect to the global SPA with a firm-wide flash. Do not restore per-entity CoA CRUD. The SPA lists journal line counts and must not present `opening_balance` / `current_balance` as report figures — those columns stay zeroed on create and are ignored by P&L/BS (openings use journals vs 3190).

## Entity Financial Reports CoA is read-only
`business-entities.financial-reports.chart-of-accounts` is a read-only report of the shared chart with journal line counts scoped to that entity. Keep mutations on firm `chart-of-accounts.*` only; do not add entity CoA create/edit/delete.
