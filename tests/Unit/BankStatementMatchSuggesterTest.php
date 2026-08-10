<?php

use App\Models\BankAccount;
use App\Models\BankStatementEntry;
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

it('exposes loan types and interest expense posting map', function () {
    expect(Transaction::allTypes())->toHaveKeys(['loan_interest', 'loan_fees', 'loan_repayments']);

    $source = file_get_contents(app_path('Services/TransactionPostingService.php'));
    expect($source)->toContain("'loan_interest'")
        ->and($source)->toContain('Interest Expense')
        ->and($source)->toContain("'loan_fees'")
        ->and($source)->toContain('Long Term Loans');
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

it('applies matches inside a single database transaction', function () {
    $source = file_get_contents(app_path('Services/BankStatementApplyService.php'));

    expect($source)->toContain('return DB::transaction(function () use ($bankAccount, $businessEntity, $matches)')
        ->and($source)->toContain('claimedTransactionIds')
        ->and($source)->toContain('is selected for more than one statement line');
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
