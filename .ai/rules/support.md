---
paths:
  - app/Support/TransactionGstResolver.php
---

# Support

## Manual GST for mixed-rate invoices
Inclusive/Exclusive assume a single 10% rate on the whole line amount. For invoices with mixed GST (some lines GST-free), use gst_basis=manual and enter the invoice TOTAL GST. Manual is treated like inclusive for cash (amount is bank total; net = amount − gst). Do not auto-calc 1/11 when basis is manual.

## GST amount ceiling and edit overrides
Inclusive/manual GST is a component of the line amount — reject gst_amount > amount. Exclusive GST sits on top of amount. Edit/create forms must treat Manual (and non-standard Inclusive overrides) as gstTouched so changing amount does not wipe the invoice GST.
