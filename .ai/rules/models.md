---
paths:
  - app/Models/Transaction.php
---

# Models

## Director loan pickers are in vs out only
New-entry pickers show only director_loan_in and director_loan_out. director_loan_repayment stays in allTypes / posting / related-party lists as a hidden alias (same GL as out). Statement keywords for repayments map to director_loan_out. Edit screens keep the old type under the Current group.
