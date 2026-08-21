---
paths:
  - app/Console/Commands/AuditUnmatchedLoanRepayments.php
---

# Commands

## Loan-ledger repayments: detect orphans, never auto-post them
`loan_repayments` on a loan-purpose account posts nothing on purpose — the cash side is the internal transfer on the offset/general account, which posts Dr 4000 / Cr 1100. Posting both sides would reduce the loan twice.
Do not block the row on save (bank imports routinely create one side before the other) and do not auto-post the cash side (it would have to un-post itself when the offset row arrives, double-counting whenever matching is imperfect).
Use `php artisan loans:audit-unmatched-repayments` instead: read-only, pairs by transfer_group_id or by counterpart account + amount within --days, exits non-zero when gaps exist so it can be scheduled.

## Loan repayment audit matches cash transfers one-to-one
Do not match by transfer_group_id: loan_repayments do not retain one and independently imported transfer rows get separate UUIDs. Match each repayment to at most one outgoing internal transfer in the same entity, with the same amount and date window, resolving the loan counterpart explicitly or through the offset account's asset-linked loan. Keep date filtering in SQL and validate entity/date/window options.

## Supersedes the older loan audit pairing note
The earlier note saying the audit pairs by transfer_group_id is obsolete. Follow “Loan repayment audit matches cash transfers one-to-one”: transfer_group_id is never a valid pairing signal here.
