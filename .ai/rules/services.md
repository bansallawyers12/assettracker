---
paths:
  - app/Services/TransactionPostingService.php
---

# Services

## Loan ledger never posts to Bank/Cash
loan_repayments on a loan-purpose account unpost (no 1100). Offset↔loan internal_transfer posts 1100↔4000 only on the cash/offset side. loan_interest/fees still capitalise to 4000. After deploy, run journals:repost-paid-transactions --types=loan_repayments,internal_transfer.
