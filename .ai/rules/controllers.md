---
paths:
  - app/Http/Controllers/BalanceSheetEntryController.php
  - app/Http/Controllers/InvoiceController.php
---

# Controllers

## Deposits post to 1500; 1150 Deposits Paid is manual-journal only
Confirmed decision (reaffirmed Aug 2026): property deposits use `asset_purchase` and post to Property & Assets (Capital) 1500. Do not add a deposit type, a deposit marker on the form, or automatic posting to 1150 — both were considered and rejected because they require a settlement reclass (Dr 1500 / Cr 1150) that understates the property whenever someone forgets it.
1150 Deposits Paid stays active on purpose for the occasional manual journal on deals where committed-but-unsettled funds must be visible (Dr 1150 / Cr 2500 on exchange, Dr 1500 / Cr 1150 at settlement). Do not deactivate or delete it, and do not wire it into TransactionPostingService — BalanceSheetEntryFlowTest asserts the service references neither TYPE_DEPOSIT_PAID nor findByName('Deposits Paid').

## Manual invoice create links asset/lease and GST
Manual Create Invoice must link optional asset_id/lease_id (lease must belong to asset/entity), require income chart account_code (default 4100), accept gst_basis inclusive|exclusive|none + gst_percent (default 10 inclusive; none forces 0% GST), and suggest invoice numbers as INV{entityId}-YYYYMM### with due_date default issue+30. line_total is always the cash total for InvoicePostingService. Prefer Rent invoices UI for recurring rent. Persist gst_basis on invoices. Edit draft invoices with the same fields/validation as create; posted invoices stay read-only. Save & post persists and posts in one DB transaction. RentInvoiceService uses leases.rental_amount + payment_frequency; GST follows leases.gst_applicable (true = inclusive 10%, false = gst_basis none / 0% GST). Do not rewrite already-posted rent invoices.
