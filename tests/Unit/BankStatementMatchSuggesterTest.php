<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\BankStatementMatchSuggester;
use App\Services\BankStatementParseService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

function makeEntry(array $attrs = []): BankStatementEntry
{
    $entry = new BankStatementEntry(array_merge([
        'date' => now()->toDateString(),
        'amount' => -100.00,
        'description' => 'Test line',
        'transaction_type' => 'debit',
    ], $attrs));
    $entry->id = $attrs['id'] ?? 1;

    return $entry;
}

function makeTransaction(array $attrs = []): Transaction
{
    $transaction = new Transaction(array_merge([
        'date' => now()->toDateString(),
        'amount' => 100.00,
        'description' => 'Booked fee',
        'transaction_type' => 'management_fees',
        'payment_status' => 'unpaid',
    ], $attrs));
    $transaction->id = $attrs['id'] ?? 10;

    return $transaction;
}

it('suggests a high-confidence match for amount date and direction', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry(['amount' => -250.50, 'date' => '2026-08-01']);
    $candidate = makeTransaction([
        'id' => 22,
        'amount' => 250.50,
        'date' => '2026-08-02',
        'transaction_type' => 'management_fees',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect([$candidate]));

    expect($suggestion['action'])->toBe('match_transaction')
        ->and($suggestion['confidence'])->toBe('high')
        ->and($suggestion['transaction_id'])->toBe(22);
});

it('suggests medium confidence when date is outside three days but within fourteen', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry(['amount' => -80, 'date' => '2026-08-01']);
    $candidate = makeTransaction([
        'id' => 33,
        'amount' => 80,
        'date' => '2026-08-10',
        'transaction_type' => 'other_expenses',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect([$candidate]));

    expect($suggestion['action'])->toBe('match_transaction')
        ->and($suggestion['confidence'])->toBe('medium')
        ->and($suggestion['transaction_id'])->toBe(33);
});

it('suggests loan interest from macquarie subcategory', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -5210.06,
        'description' => 'Interest charge',
        'meta' => [
            'bank_profile' => 'macquarie',
            'subcategory' => 'Interest',
        ],
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $suggestion = $suggester->suggest($entry, $account, new Collection, 7);

    expect($suggestion['action'])->toBe('create_transaction')
        ->and($suggestion['confidence'])->toBe('high')
        ->and($suggestion['transaction_type'])->toBe('loan_interest')
        ->and($suggestion['asset_id'])->toBe(7);
});

it('suggests loan fees and repayments from macquarie subcategory', function (string $subcategory, string $type) {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -50,
        'description' => 'Loan activity',
        'meta' => [
            'bank_profile' => 'macquarie',
            'subcategory' => $subcategory,
        ],
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['transaction_type'])->toBe($type)
        ->and($suggestion['confidence'])->toBe('high');
})->with([
    'fees' => ['Other Fees', 'loan_fees'],
    'transfer' => ['Transfer', 'loan_repayments'],
]);

it('suggests director loan in when money is received on the loan ledger', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 15000,
        'description' => 'Director loan from AJ',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('create_transaction')
        ->and($suggestion['transaction_type'])->toBe('director_loan_in');
});

it('does not suggest operating income types on the loan ledger', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 500,
        'description' => 'Invoice payment received',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('none');
});

it('flags dishonour lines as low confidence none', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 100,
        'description' => 'Dishonour fee reversal',
        'meta' => [
            'bank_profile' => 'macquarie',
            'subcategory' => 'Dishonour',
        ],
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_LOAN]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('none')
        ->and($suggestion['confidence'])->toBe('low');
});

it('falls back to keyword rules for operating accounts', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -1200,
        'description' => 'Monthly loan repayment to bank',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('create_transaction')
        ->and($suggestion['confidence'])->toBe('medium')
        ->and($suggestion['transaction_type'])->toBe('loan_repayments');
});

it('suggests asic payment for asic fee descriptions', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -575.14,
        'description' => 'Payment to ASIC - CRN 2296823408705',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('create_transaction')
        ->and($suggestion['confidence'])->toBe('medium')
        ->and($suggestion['transaction_type'])->toBe('asic_payment');
});

it('returns none when no rule matches', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -12.34,
        'description' => 'ZZZ unknown merchant xyz',
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('none');
});

it('suggests internal transfer on offset accounts instead of loan repayment', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -2000,
        'description' => 'Transfer to loan account',
        'meta' => [
            'bank_profile' => 'macquarie',
            'subcategory' => 'Transfer',
        ],
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    $suggestion = $suggester->suggest($entry, $account, collect(), 9);

    expect($suggestion['action'])->toBe('create_transaction')
        ->and($suggestion['transaction_type'])->toBe(Transaction::TYPE_INTERNAL_TRANSFER)
        ->and($suggestion['asset_id'])->toBe(9);
});

it('does not suggest loan interest create on offset accounts', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => -100,
        'description' => 'Interest charge',
        'meta' => [
            'subcategory' => 'Interest',
        ],
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    $suggestion = $suggester->suggest($entry, $account, collect());

    expect($suggestion['action'])->toBe('none')
        ->and($suggestion['transaction_type'])->toBeNull();
});

it('matches internal transfer candidates regardless of default expense direction', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry(['amount' => 1500, 'date' => '2026-08-01', 'description' => 'Redraw to offset']);
    $candidate = makeTransaction([
        'id' => 88,
        'amount' => 1500,
        'date' => '2026-08-01',
        'transaction_type' => Transaction::TYPE_INTERNAL_TRANSFER,
    ]);
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_OFFSET]);

    $suggestion = $suggester->suggest($entry, $account, collect([$candidate]));

    expect($suggestion['action'])->toBe('match_transaction')
        ->and($suggestion['transaction_id'])->toBe(88);
});

it('suggests a high-confidence invoice match when amount and customer name align', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 10000,
        'date' => '2026-08-09',
        'description' => 'RANJEET SINGH Rent Melbourne',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'INV64-202604001',
        'customer_name' => 'Ranjeet Singh',
        'total_amount' => 10000,
        'issue_date' => '2026-04-01',
        'status' => 'approved',
        'is_posted' => true,
    ]);
    $invoice->id = 81;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect(), null, collect([$invoice]));

    expect($suggestion['action'])->toBe('match_invoice')
        ->and($suggestion['confidence'])->toBe('high')
        ->and($suggestion['invoice_id'])->toBe(81)
        ->and($suggestion['invoice_number'])->toBe('INV64-202604001');
});

it('suggests a medium-confidence invoice match on amount only', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 10000,
        'date' => '2026-08-09',
        'description' => 'DEPOSIT INTERNET',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'INV1-202604001',
        'customer_name' => 'Ranjeet Singh',
        'total_amount' => 10000,
        'issue_date' => '2026-04-01',
        'status' => 'approved',
        'is_posted' => true,
    ]);
    $invoice->id = 82;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect(), null, collect([$invoice]));

    expect($suggestion['action'])->toBe('match_invoice')
        ->and($suggestion['confidence'])->toBe('medium')
        ->and($suggestion['invoice_id'])->toBe(82);
});

it('prefers an existing transaction match over an unpaid invoice', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry(['amount' => 500, 'date' => '2026-08-01', 'description' => 'Alex Tenant rent']);
    $candidate = makeTransaction([
        'id' => 44,
        'amount' => 500,
        'date' => '2026-08-01',
        'transaction_type' => Transaction::TYPE_INVOICE_PAYMENT,
        'payment_status' => 'paid',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'INV1-202608001',
        'customer_name' => 'Alex Tenant',
        'total_amount' => 500,
        'issue_date' => '2026-08-01',
        'status' => 'approved',
    ]);
    $invoice->id = 90;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect([$candidate]), null, collect([$invoice]));

    expect($suggestion['action'])->toBe('match_transaction')
        ->and($suggestion['transaction_id'])->toBe(44)
        ->and($suggestion['invoice_id'])->toBeNull();
});

it('does not suggest rental income when an unpaid invoice amount matches', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 1100,
        'date' => '2026-09-01',
        'description' => 'Rental income received',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'RENT202609001',
        'customer_name' => 'Sam Tenant',
        'total_amount' => 1100,
        'issue_date' => '2026-09-01',
        'status' => 'approved',
    ]);
    $invoice->id = 91;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect(), null, collect([$invoice]));

    expect($suggestion['action'])->toBe('match_invoice')
        ->and($suggestion['invoice_id'])->toBe(91)
        ->and($suggestion['transaction_type'])->toBeNull();
});

it('only stores counterpart fields for internal transfers in apply service', function () {
    $source = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($source)->toContain('if ($resolvedType === Transaction::TYPE_INTERNAL_TRANSFER)')
        ->and($source)->toContain('$counterpartId = null')
        ->and($source)->toContain('$transferGroupId = null');
});

it('enforces loan activity create types in apply service', function () {
    $source = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($source)->toContain('$bankAccount->isLoanLedgerAccount()')
        ->and($source)->toContain('Transaction::isAllowedOnBankAccount')
        ->and($source)->toContain('Transaction::bankAccountTypeRestrictionMessage')
        ->and($source)->toContain('Loan activity must use Loan Interest, Loan Fees, Loan Repayment, or Director Loan In/Out rather than a chart account.');
});

it('exposes loan types and interest expense posting map', function () {
    expect(Transaction::allTypes())->toHaveKeys(['loan_interest', 'loan_fees', 'loan_repayments', 'internal_transfer']);

    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));
    expect($source)->toContain("'loan_interest'")
        ->and($source)->toContain('Interest Expense')
        ->and($source)->toContain("'loan_fees'")
        ->and($source)->toContain('Long Term Loans')
        ->and($source)->toContain('isInternalTransfer');
});

it('claims each candidate transaction at most once across suggestMany', function () {
    $suggester = new BankStatementMatchSuggester;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $entries = collect([
        makeEntry(['id' => 1, 'amount' => -100, 'date' => '2026-08-01', 'description' => 'Fee A']),
        makeEntry(['id' => 2, 'amount' => -100, 'date' => '2026-08-01', 'description' => 'Fee B']),
    ]);

    $candidates = collect([
        makeTransaction(['id' => 50, 'amount' => 100, 'date' => '2026-08-01', 'transaction_type' => 'management_fees']),
    ]);

    $suggestions = $suggester->suggestMany($entries, $account, $candidates);

    expect($suggestions[1]['action'])->toBe('match_transaction')
        ->and($suggestions[1]['transaction_id'])->toBe(50)
        ->and($suggestions[2]['action'])->not->toBe('match_transaction');
});

it('does not auto-suggest a partial invoice match without a customer name signal', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 3000,
        'date' => '2026-08-09',
        'description' => 'DEPOSIT INTERNET',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'INV1-202604001',
        'customer_name' => 'Ranjeet Singh',
        'total_amount' => 10000,
        'issue_date' => '2026-04-01',
        'status' => 'approved',
        'is_posted' => true,
    ]);
    $invoice->id = 83;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect(), null, collect([$invoice]));

    expect($suggestion['action'])->not->toBe('match_invoice');
});

it('suggests a partial invoice match when customer name is present', function () {
    $suggester = new BankStatementMatchSuggester;
    $entry = makeEntry([
        'amount' => 3000,
        'date' => '2026-08-09',
        'description' => 'RANJEET SINGH Rent',
    ]);
    $invoice = new Invoice([
        'invoice_number' => 'INV1-202604001',
        'customer_name' => 'Ranjeet Singh',
        'total_amount' => 10000,
        'issue_date' => '2026-04-01',
        'status' => 'approved',
        'is_posted' => true,
    ]);
    $invoice->id = 84;
    $invoice->setRelation('paymentAllocations', collect());
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);

    $suggestion = $suggester->suggest($entry, $account, collect(), null, collect([$invoice]));

    expect($suggestion['action'])->toBe('match_invoice')
        ->and($suggestion['invoice_id'])->toBe(84)
        ->and($suggestion['confidence'])->toBe('medium')
        ->and($suggestion['reason'])->toContain('Partial payment');
});

it('depletes invoice remaining across suggestMany so two partials can target one invoice', function () {
    $suggester = new BankStatementMatchSuggester;
    $account = new BankAccount(['account_purpose' => BankAccount::PURPOSE_GENERAL]);
    $invoice = new Invoice([
        'invoice_number' => 'INV1-202604001',
        'customer_name' => 'Ranjeet Singh',
        'total_amount' => 10000,
        'issue_date' => '2026-04-01',
        'status' => 'approved',
        'is_posted' => true,
    ]);
    $invoice->id = 85;
    $invoice->setRelation('paymentAllocations', collect());

    $entries = collect([
        makeEntry(['id' => 11, 'amount' => 3000, 'date' => '2026-08-01', 'description' => 'RANJEET SINGH part 1']),
        makeEntry(['id' => 12, 'amount' => 7000, 'date' => '2026-08-02', 'description' => 'RANJEET SINGH part 2']),
    ]);

    $suggestions = $suggester->suggestMany($entries, $account, collect(), null, collect([$invoice]));

    expect($suggestions[11]['action'])->toBe('match_invoice')
        ->and($suggestions[11]['invoice_id'])->toBe(85)
        ->and($suggestions[12]['action'])->toBe('match_invoice')
        ->and($suggestions[12]['invoice_id'])->toBe(85);
});

it('applies matches inside a single database transaction', function () {
    $source = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($source)->toContain('return DB::transaction(function () use ($bankAccount, $businessEntity, $matches)')
        ->and($source)->toContain('claimedTransactionIds')
        ->and($source)->toContain('is selected for more than one statement line')
        ->and($source)->not->toContain('claimedInvoiceIds');
});

it('fingerprints statement lines with reference and balance for duplicate detection', function () {
    $service = new BankStatementParseService;

    $base = [
        'date' => '2026-08-01',
        'amount' => -50.0,
        'description' => 'Package fee',
        'meta' => ['balance_after' => 1000.0, 'reference' => 'REF-1'],
    ];
    $same = $service->entryFingerprint($base);
    $differentBalance = $service->entryFingerprint([
        ...$base,
        'meta' => ['balance_after' => 950.0, 'reference' => 'REF-1'],
    ]);
    $differentRef = $service->entryFingerprint([
        ...$base,
        'meta' => ['balance_after' => 1000.0, 'reference' => 'REF-2'],
    ]);

    expect($same)->toBe($service->entryFingerprint($base))
        ->and($same)->not->toBe($differentBalance)
        ->and($same)->not->toBe($differentRef);
});

it('documents create-vs-match duplicate transaction handling in services', function () {
    $parse = file_get_contents(app_path('Services/BankStatementParseService.php'));
    $suggester = file_get_contents(app_path('Services/BankStatementMatchSuggester.php'));
    $apply = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($parse)->toContain('skippedDuplicates')
        ->and($parse)->toContain('batchOccurrence')
        ->and($suggester)->toContain('claiming each matched transaction at most once')
        ->and($apply)->toContain('Selected transaction is already matched to a statement line');
});

it('maps director loan repayment keywords to director_loan_out', function () {
    $suggester = new BankStatementMatchSuggester;

    expect($suggester->determineTransactionType('Director loan repayment', -5000))->toBe('director_loan_out')
        ->and($suggester->determineTransactionType('Repay director', -250))->toBe('director_loan_out')
        ->and($suggester->determineTransactionType('Loan to director', -1000))->toBe('director_loan_out')
        ->and($suggester->determineTransactionType('Loan from director', 25000))->toBe('director_loan_in');
});
