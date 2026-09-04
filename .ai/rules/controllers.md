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
Manual Create Invoice must link optional asset_id/lease_id (lease must belong to asset/entity), require income chart account_code (default 4100), accept gst_basis inclusive|exclusive + gst_percent (default 10 inclusive), and suggest invoice numbers as INV{entityId}-YYYYMM### with due_date default issue+30. line_total is always GST-inclusive for InvoicePostingService. Prefer Rent invoices UI for recurring rent. Persist gst_basis on invoices (not only gst_rate on lines). Edit draft invoices with the same fields/validation as create; posted invoices stay read-only. RentInvoiceService uses leases.rental_amount + payment_frequency (Weekly|Fortnightly|Monthly|Quarterly|Yearly) and always books inclusive 10% GST.
