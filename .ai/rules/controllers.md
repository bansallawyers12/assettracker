---
paths:
  - app/Http/Controllers/BalanceSheetEntryController.php
---

# Controllers

## Deposits post to 1500; 1150 Deposits Paid is manual-journal only
Confirmed decision (reaffirmed Aug 2026): property deposits use `asset_purchase` and post to Property & Assets (Capital) 1500. Do not add a deposit type, a deposit marker on the form, or automatic posting to 1150 — both were considered and rejected because they require a settlement reclass (Dr 1500 / Cr 1150) that understates the property whenever someone forgets it.
1150 Deposits Paid stays active on purpose for the occasional manual journal on deals where committed-but-unsettled funds must be visible (Dr 1150 / Cr 2500 on exchange, Dr 1500 / Cr 1150 at settlement). Do not deactivate or delete it, and do not wire it into TransactionPostingService — BalanceSheetEntryFlowTest asserts the service references neither TYPE_DEPOSIT_PAID nor findByName('Deposits Paid').
