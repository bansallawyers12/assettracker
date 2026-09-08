---
paths:
  - 'resources/views/invoices/**'
---

# Invoices

## Invoice create/edit UI is simplified
Manual invoice form shows only: issue/due dates, lease picker, customer, GST yes/no, line description + unit price + income account, totals. Hidden defaults: AUD currency, auto invoice number, auto reference from lease, qty=1, GST 10% inclusive (or none/0), preserve notes on edit. Do not reintroduce Currency, GST %, GST basis, Asset picker, tenant pick dropdown, Notes, Qty, or Line total columns without product approval. Backend still accepts exclusive GST and other fields via POST.
