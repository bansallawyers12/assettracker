---
paths:
  - 'resources/views/business-entities/**'
---

# Business Entities

## No entity Bank Import tab
Do not add an entity Bank Import tab. CSV/Excel upload and match live on each account’s transactions panel under Bank Accounts. Deep links should use #tab_bank_accounts. Keep the `tab_bank_import` → `tab_bank_accounts` hash alias and legacy `?bank_account_id=` panel opener so old bookmarks still land on Bank Accounts.
