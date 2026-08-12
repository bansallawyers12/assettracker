# Project rules index

Map of standing rules. Agents must read matching files before editing covered paths.

| Glob / area | Rule file |
|-------------|-----------|
| `app/Models/Transaction.php`, `app/Services/TransactionPostingService.php`, `app/Services/LoanOffsetTransactionGuard.php`, `app/Services/BankStatementMatchSuggester.php`, `app/Services/BankStatementApplyService.php`, loan/offset bank accounts | [loan-offset-transfers.md](loan-offset-transfers.md) |
| `app/Models/Transaction.php` (`payment_channel`, funding labels), `app/Services/TransactionPostingService.php`, `app/Observers/TransactionObserver.php`, `app/Console/Commands/RepostPaidTransactionJournals.php`, entity/asset transaction Account column | [director-funds-posting.md](director-funds-posting.md) |
| `app/Http/Controllers/BalanceSheetEntryController.php`, `resources/views/business-entities/balance-sheet-entries/**`, `resources/views/bank-accounts/partials/transactions-panel.blade.php`, balance-sheet entry types (`asset_purchase`, capital, director loan) | [balance-sheet-entries.md](balance-sheet-entries.md) |
