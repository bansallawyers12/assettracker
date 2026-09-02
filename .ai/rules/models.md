---
paths:
  - app/Models/Transaction.php
---

# Models

## Director loan pickers are in vs out only
New-entry pickers show only director_loan_in and director_loan_out. director_loan_repayment stays in allTypes / posting / related-party lists as a hidden alias (same GL as out). Statement keywords for repayments map to director_loan_out. Edit screens keep the old type under the Current group.

## Loan ledger pickers include director loan in/out
Loan-purpose accounts use loanActivityTypeSelectGroups(): Loan Interest / Fees / Repayment plus Director Loan In and Out. Validate creates against loanLedgerAllowedTypes(), not loanActivityTypes() alone. Director money received on the loan account is director_loan_in (posts 4000↔2500, never 1100).
