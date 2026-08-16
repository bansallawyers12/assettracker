---
paths:
  - 'resources/views/business-entities/bank-accounts/transactions/**'
---

# Transactions

## Statement vs manual transaction edit
Edit uses one route (business-entities.transactions.edit). If the transaction has bank_statement_entries, render edit-from-statement (classify type/asset/markers; date/amount come from the statement and stay locked). Otherwise render the full Add-transaction form. Statement updates must send edit_origin=statement and preserve payment/GST/vendor/paid_by so they are not wiped. Default save returns to #tab_transactions; only return_to=bank-account opens the bank panel.
