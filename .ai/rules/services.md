---
paths:
  - app/Services/TransactionPostingService.php
  - app/Services/BankStatementApplyService.php
---

# Services

## Loan ledger never posts to Bank/Cash
loan_repayments on a loan-purpose account unpost (no 1100). Offset↔loan internal_transfer posts 1100↔4000 only on the cash/offset side. loan_interest/fees still capitalise to 4000. After deploy, run journals:repost-paid-transactions --types=loan_repayments,internal_transfer.

## Explicit director loans always post to 2500
All explicit director-loan transaction types (`director_loan_in`, out, repayment, and legacy aliases) post to Director / Entity Loan account 2500. Ignore stale `chart_of_account_id` overrides for these types; re-post affected paid rows after deployment.

## Keep imported funding types distinct
When creating transactions from chart-account reconciliation: account 2500 maps to director_loan_in/out; other liability inflows map to loan_drawdown; equity inflows map to equity_contribution. Do not use director_loan_in as a generic positive liability/equity type.
