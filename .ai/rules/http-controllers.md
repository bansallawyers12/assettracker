---
paths:
  - app/Models/ChartOfAccount.php
  - app/Http/Controllers/ChartOfAccountController.php
  - config/financial.php
---

# Http Controllers

## System CoA codes lock code type and category
`ChartOfAccount::systemAccountCodes()` merges `config('financial.report_accounts')` with `config('financial.system_account_codes')` (extras like 1130 and 2500). Those accounts lock code/type/category on update via `isSystemAccount()`. Name/description/parent/active stay editable. SPA uses `is_system_account` and shows report placement hints from `reportPlacementHints()` by category. Do not let posting-critical codes change code/type/category.
