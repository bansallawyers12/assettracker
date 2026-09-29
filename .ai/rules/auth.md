---
paths:
  - 'app/Policies/**'
  - 'app/Enums/AppRole.php'
  - 'app/Models/User.php'
---

# Authentication and access

## Portfolio mutations use create/canMutatePortfolio, not viewAny
Firm portfolio is read-shared (`viewAny`/`view` stay open for authenticated users). Mutating controllers (vendors, chart of accounts, portfolio bank accounts, reminders, email templates) must authorize `create` on `BusinessEntity` or `canMutatePortfolio()` so `viewer` cannot write. Do not authorize mutations with `viewAny`.

## Dashboard transaction create is an exception
`BusinessEntityController::storeTransaction` (dashboard batch create) must authorize `recordTransaction`, not `update`, so viewers can record income/expense while remaining read-only for entity edits and transaction edit/delete (`update` policy unchanged).
