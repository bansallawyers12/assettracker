---
paths:
  - 'resources/views/invoices/**'
---

# Invoices

## Invoice create/edit UI is simplified
Manual invoice form shows only: issue/due dates, lease picker, customer, GST yes/no, line description + unit price + account (full chart), totals. Hidden defaults: AUD currency, auto invoice number, auto reference from lease, qty=1, GST 10% inclusive (or none/0), preserve notes on edit. Do not reintroduce Currency, GST %, GST basis, Asset picker, tenant pick dropdown, Notes, Qty, or Line total columns without product approval. Backend still accepts exclusive GST and other fields via POST.

## Lease picker shows past-expiry leases
Lease / tenant options include leases with past end_date. end_date is expiry/term, not closed. Do not re-add an end_date filter or "Include ended leases" toggle on this form.

## Invoice form qty fold and exclusive preserve
When simplifying invoice lines to qty=1, fold existing quantity into unit_price on edit so totals do not shrink. Preserve exclusive gst_basis on existing drafts (create UI still defaults inclusive). Always sync asset_id from selected lease_id. Keep static value= fallbacks on hidden fields for no-JS submit.

## Lease change must not wipe exclusive GST
Lease picker must not set gstBasisWhenApplicable=inclusive on change — that wiped exclusive drafts. Lease only toggles GST applicable yes/no; basis stays inclusive (create default) or exclusive (preserved draft).

## Invoice form account column uses full chart
Line item account column label is Account (not Income account). Options come from all active chart accounts.

## Record payment supports director funds
Invoice show Record payment offers Paid via: Bank account or Director funds (no bank). Director funds clears AR against 2500 (no statement match). Bank path unchanged. Do not hide the form when the entity has no operating bank — default to director funds instead.
