---
paths:
  - 'resources/views/invoices/**'
---

# Invoices

## Invoice create/edit UI is simplified
Manual invoice form shows only: issue/due dates, lease picker, customer, GST yes/no, line description + unit price + income account, totals. Hidden defaults: AUD currency, auto invoice number, auto reference from lease, qty=1, GST 10% inclusive (or none/0), preserve notes on edit. Do not reintroduce Currency, GST %, GST basis, Asset picker, tenant pick dropdown, Notes, Qty, or Line total columns without product approval. Backend still accepts exclusive GST and other fields via POST.

## Invoice form qty fold and exclusive preserve
When simplifying invoice lines to qty=1, fold existing quantity into unit_price on edit so totals do not shrink. Preserve exclusive gst_basis on existing drafts (create UI still defaults inclusive). Always sync asset_id from selected lease_id. Keep static value= fallbacks on hidden fields for no-JS submit.

## Lease change must not wipe exclusive GST
Lease picker must not set gstBasisWhenApplicable=inclusive on change — that wiped exclusive drafts. Lease only toggles GST applicable yes/no; basis stays inclusive (create default) or exclusive (preserved draft).
