---
paths:
  - app/Models/ChartOfAccount.php
  - app/Http/Controllers/ChartOfAccountController.php
  - config/financial.php
---

# Http Controllers

## System CoA codes lock code type and category
`ChartOfAccount::systemAccountCodes()` merges `config('financial.report_accounts')` with `config('financial.system_account_codes')` (extras like 1130 and 2500). Those accounts lock code/type/category on update via `isSystemAccount()`. Name/description/parent/active stay editable. SPA uses `is_system_account` and shows report placement hints from `reportPlacementHints()` by category. Do not let posting-critical codes change code/type/category.

## CoA UI: firm/shared vs entity-owned
`chart_of_accounts.business_entity_id` null = firm/shared; set = entity-only. Firm `/chart-of-accounts` lists firmWide() only. Entity Financial Reports CoA lists that entity’s owned accounts plus firm accounts with journal activity for the entity. Add from the entity page POSTs `business_entity_id` to firm `chart-of-accounts.store` (still no separate entity CRUD routes). `account_code` stays globally unique. System codes stay firm-only. Pickers default to firm/shared; entity contexts use `availableForEntity` / `activeForSelect($entityId)`. Do not present `opening_balance` / `current_balance` as report figures. Legacy `business-entities.*.chart-of-accounts.*` GET routes still redirect to the firm SPA.
