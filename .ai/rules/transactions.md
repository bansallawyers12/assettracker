---
paths:
  - 'resources/views/business-entities/bank-accounts/transactions/**'
---

# Transactions

## Statement vs manual transaction edit
Edit uses one route and one view (`business-entities.transactions.edit` → `edit.blade.php`). If the transaction has bank_statement_entries, lock cash identity (date, amount, bank account, paid status, paid-by, payment channel) with Locked badges + `data-statement-edit-locked-notice` — lock from `isLinkedToBankStatement()`, not from `edit_origin` (that hidden field can be tampered). GST (no GST / inclusive / manual; not exclusive), vendor, invoice number, payment docs, type, asset, and markers stay editable. Statement POST still sends `edit_origin=statement`. Do not put `data-transaction-paid-by-form` on the matched form: paid-by is hidden and client validation would block submit. Server resolves GST via `TransactionGstResolver` (do not preserve GST/vendor from the existing row). Unmatch / Remove & Redo (`data-statement-edit-full-form-path`) are for a wrong match or changing date/amount/account — not for GST. Default save returns to #tab_transactions; only return_to=bank-account opens the bank panel.
