<?php

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Support\TransactionListFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('returns empty filters by default', function () {
    expect(TransactionListFilters::empty())->toMatchArray([
        'q' => null,
        'date_from' => null,
        'date_to' => null,
        'entity_id' => null,
        'type' => null,
        'direction' => null,
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ]);
});

it('parses and normalizes filter request input', function () {
    $request = Request::create('/transactions', 'GET', [
        'q' => '  council rates  ',
        'date_from' => '2026-01-01',
        'date_to' => '2026-01-31',
        'entity_id' => '12',
        'direction' => 'expense',
        'payment_status' => 'unpaid',
        'match_status' => 'unmatched',
        'subject_to_bas' => 'yes',
        'is_flagged' => 'no',
    ]);

    $filters = TransactionListFilters::fromRequest($request);

    expect($filters['q'])->toBe('council rates')
        ->and($filters['entity_id'])->toBe(12)
        ->and($filters['direction'])->toBe('expense')
        ->and(TransactionListFilters::isActive($filters))->toBeTrue();
});

it('applies text search and date filters to a transaction query', function () {
    $query = Transaction::query();

    TransactionListFilters::apply($query, [
        'q' => 'rates',
        'date_from' => '2026-01-01',
        'date_to' => '2026-01-31',
        'entity_id' => null,
        'type' => null,
        'direction' => null,
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ]);

    $sql = strtolower($query->toSql());

    expect($sql)->toContain('transactions')
        ->and($sql)->toContain('description')
        ->and($sql)->toContain('invoice_number')
        ->and($sql)->toMatch('/["`]transactions["`]\s*\.\s*["`]description["`]/')
        ->and($sql)->toMatch('/["`]transactions["`]\s*\.\s*["`]date["`]/');
});

it('keeps filter columns qualified when related tables are joined', function () {
    $query = Transaction::query()
        ->leftJoin('assets', 'assets.id', '=', 'transactions.asset_id');

    TransactionListFilters::apply($query, [
        'q' => 'rates',
        'date_from' => null,
        'date_to' => null,
        'entity_id' => 3,
        'type' => null,
        'direction' => null,
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ]);

    $sql = strtolower($query->toSql());

    expect($sql)->toMatch('/["`]transactions["`]\s*\.\s*["`]description["`]/')
        ->and($sql)->toMatch('/["`]transactions["`]\s*\.\s*["`]business_entity_id["`]/')
        ->and($sql)->not->toMatch('/\bwhere\s+["`]?description["`]?\s+like/')
        ->and($sql)->not->toMatch('/\band\s+["`]?business_entity_id["`]?\s*=/');
});

it('accepts bank account transaction relations when applying filters', function () {
    $bankAccount = new BankAccount;
    $relation = $bankAccount->transactions();

    TransactionListFilters::apply($relation, TransactionListFilters::empty());

    expect($relation->toSql())->toBeString();
});

it('builds query params without empty values', function () {
    $params = TransactionListFilters::queryParams([
        'q' => 'water',
        'date_from' => null,
        'date_to' => null,
        'entity_id' => 5,
        'type' => null,
        'direction' => 'income',
        'payment_status' => null,
        'match_status' => null,
        'subject_to_bas' => null,
        'is_flagged' => null,
    ], ['sort' => 'date']);

    expect($params)->toBe([
        'sort' => 'date',
        'q' => 'water',
        'entity_id' => 5,
        'direction' => 'income',
    ]);
});
