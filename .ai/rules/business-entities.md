---
paths:
  - 'resources/views/business-entities/**'
  - 'app/Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'
  - 'app/Http/Controllers/*WorkspaceController.php'
  - 'app/Http/Controllers/ContactListController.php'
  - 'app/Http/Controllers/DocumentController.php'
---

# Business Entities

## No entity Bank Import tab
Do not add an entity Bank Import tab. CSV/Excel upload and match live on each account’s transactions panel under Bank Accounts. Deep links should use #tab_bank_accounts. Keep the `tab_bank_import` → `tab_bank_accounts` hash alias and legacy `?bank_account_id=` panel opener so old bookmarks still land on Bank Accounts.

## Documents checklist uses row context menu
Document checklist row actions (Upload/Reupload, Clear, Rename, Move, Delete) live in a right-click context menu, not an Actions table column. Preview pane uses the reclaimed horizontal space (~44% / max 44rem). Keep a shared hidden file input + .doc-context-menu inside .documents-workspace; rows carry data-has-file and data-label. When the entity is closed, set `data-entity-closed` and skip opening the context menu.

## Closed entities block mutations (entity-002)
Use `EnsuresOperationalBusinessEntity::ensureNotClosed` (or `authorizeOpenMutation` helpers that call it) on every entity mutate path: contacts, notes, documents, compliance, bank forms, invoices, assets, persons. Prefer JSON 403 with `message` for workspace AJAX. Hide create/edit/delete buttons behind `@unless ($businessEntity->isClosed())` and show `data-closed-*-notice` banners that point users to reopen via company profile.

## Tenancy / property-manager contacts (entity-001)
`exclude_from_financial_reports` / `isTenancyContactOnly()` hides P&L, Balance Sheet, and Manual journals; officer roles are unavailable. `ensureOperationalForAccounting` always calls `ensureNotClosed` first, then blocks tenancy contacts from accounting mutations (invoices, bank link forms). Surface `data-tenancy-*-notice` on invoices/bank tabs and list those limits in the tenancy banner so missing tools are not silent 403s.
