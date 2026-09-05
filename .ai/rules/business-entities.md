---
paths:
  - 'resources/views/business-entities/**'
---

# Business Entities

## No entity Bank Import tab
Do not add an entity Bank Import tab. CSV/Excel upload and match live on each account’s transactions panel under Bank Accounts. Deep links should use #tab_bank_accounts. Keep the `tab_bank_import` → `tab_bank_accounts` hash alias and legacy `?bank_account_id=` panel opener so old bookmarks still land on Bank Accounts.

## Documents checklist uses row context menu
Document checklist row actions (Upload/Reupload, Clear, Rename, Move, Delete) live in a right-click context menu, not an Actions table column. Preview pane uses the reclaimed horizontal space (~44% / max 44rem). Keep a shared hidden file input + .doc-context-menu inside .documents-workspace; rows carry data-has-file and data-label.
