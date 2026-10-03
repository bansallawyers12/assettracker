<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Models\BusinessEntity;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PropertyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function portfolioHoldingEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Portfolio Holding Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'portfolio-holding@example.test',
        'phone_number' => '0400000101',
    ]);
}

function portfolioHoldingBank(BusinessEntity $entity, string $purpose, string $name): BankAccount
{
    return BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => $purpose === BankAccount::PURPOSE_LOAN ? '55667788' : '99887766',
        'account_name' => $name,
        'account_purpose' => $purpose,
    ]);
}

function portfolioHoldingTransaction(
    BusinessEntity $entity,
    BankAccount $bank,
    string $type,
    float $amount,
    string $date,
    ?Asset $asset = null
): Transaction {
    return Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'asset_id' => $asset?->id,
        'date' => $date,
        'amount' => $amount,
        'description' => $type,
        'transaction_type' => $type,
        'payment_status' => 'paid',
        'paid_at' => $date,
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$entity->id,
    ]);
}

it('reads portfolio holding figures from transactions and falls back to the property', function () {
    $entity = portfolioHoldingEntity();
    $operating = portfolioHoldingBank($entity, BankAccount::PURPOSE_GENERAL, 'Operating');
    $loan = portfolioHoldingBank($entity, BankAccount::PURPOSE_LOAN, 'Loan');

    $funded = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '219 South Gippsland Highway',
        'address' => '219 South Gippsland Highway, Cranbourne',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 1966000,
        'current_value' => 1966000,
        'status' => 'Active',
        'loan_balance' => 1,
        'loan_payment_amount' => 999,
        'loan_payment_frequency' => 'Monthly',
        'council_rates_amount' => 50,
        'land_tax_amount' => 100,
        'owners_corp_amount' => 10,
    ]);
    $funded->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);

    BankAccountStatement::create([
        'bank_account_id' => $loan->id,
        'statement_period_start' => '2026-05-01',
        'statement_period_end' => '2026-05-31',
        'opening_balance' => -2000000,
        'closing_balance' => -1940433.54,
        'file_name' => 'loan.pdf',
        'path' => 'statements/loan.pdf',
    ]);

    portfolioHoldingTransaction($entity, $loan, 'loan_repayments', 1000, '2026-01-15', $funded);
    portfolioHoldingTransaction($entity, $loan, 'loan_repayments', 6608, '2026-06-15');
    portfolioHoldingTransaction($entity, $loan, 'loan_interest', 400.5, '2026-06-15');
    portfolioHoldingTransaction($entity, $operating, 'land_tax', 2786.55, '2026-03-01', $funded);
    portfolioHoldingTransaction($entity, $operating, 'valuation_and_rates', 3381.29, '2026-02-01', $funded);
    portfolioHoldingTransaction($entity, $operating, 'oc_fees', 3600, '2026-04-01', $funded);
    portfolioHoldingTransaction($entity, $operating, 'rental_income', 12000, '2026-06-01', $funded);
    Tenant::create([
        'asset_id' => $funded->id,
        'name' => 'Funded tenant',
        'rent_amount' => 9999,
        'rent_frequency' => 'Monthly',
        'move_in_date' => '2024-01-01',
    ]);

    $saved = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '1 Bald Hill',
        'address' => '1 Bald Hill Rd, Pakenham',
        'acquisition_date' => '2021-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 500000,
        'status' => 'Active',
        'loan_balance' => 1000,
        'loan_payment_amount' => 1000,
        'loan_payment_frequency' => 'Fortnightly',
        'council_rates_amount' => 300,
        'land_tax_amount' => 200,
        'owners_corp_amount' => 400,
    ]);
    portfolioHoldingTransaction($entity, $operating, 'rent_to_related_party', 1500, '2026-05-01', $saved);
    Tenant::create([
        'asset_id' => $saved->id,
        'name' => 'Bald Hill tenant',
        'rent_amount' => 2000,
        'rent_frequency' => 'Monthly',
        'move_in_date' => '2024-01-01',
    ]);

    $report = app(PropertyReportService::class)->portfolio(
        [$entity->id],
        '2025-07-01',
        '2026-06-30',
        PropertyReportService::BASIS_CASH
    );

    $rows = collect($report['properties'])->keyBy(fn (array $row) => $row['asset']->name);

    expect($rows['219 South Gippsland Highway']['loan_balance'])->toBe(1940433.54)
        ->and($rows['219 South Gippsland Highway']['repayment'])->toBe(6608.0)
        ->and($rows['219 South Gippsland Highway']['interest'])->toBe(400.5)
        ->and($rows['219 South Gippsland Highway']['council_rates'])->toBe(3381.29)
        ->and($rows['219 South Gippsland Highway']['land_tax'])->toBe(2786.55)
        ->and($rows['219 South Gippsland Highway']['strata'])->toBe(3600.0)
        ->and($rows['219 South Gippsland Highway']['period_income'])->toBe(12000.0)
        ->and($rows['219 South Gippsland Highway']['period_expenses'])->toBe(17776.34)
        ->and($rows['1 Bald Hill']['loan_balance'])->toBe(1000.0)
        ->and($rows['1 Bald Hill']['repayment'])->toBe(1000.0)
        ->and($rows['1 Bald Hill']['interest'])->toBeNull()
        ->and($rows['1 Bald Hill']['council_rates'])->toBe(300.0)
        ->and($rows['1 Bald Hill']['land_tax'])->toBe(200.0)
        ->and($rows['1 Bald Hill']['strata'])->toBe(400.0)
        ->and($rows['1 Bald Hill']['period_income'])->toBe(24000.0)
        ->and($rows['1 Bald Hill']['period_expenses'])->toBe(28400.0)
        ->and($report['totals']['total_loan_balance'])->toBe(1941433.54)
        ->and($report['totals']['total_land_tax'])->toBe(2986.55);

    $this->actingAs(User::factory()->create())
        ->get(route('portfolio.index', [
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))
        ->assertSuccessful()
        ->assertSee('Loan balance')
        ->assertSee('Repayment')
        ->assertSee('Interest')
        ->assertSee('Council rates')
        ->assertSee('Land tax')
        ->assertSee('Strata')
        ->assertDontSee('Monthly rent')
        ->assertSee('$12,000.00')
        ->assertSee('$24,000.00')
        ->assertSee('$28,400.00')
        ->assertSee('$1,940,433.54')
        ->assertSee('$6,608.00')
        ->assertDontSee('$2,166.67');
});

it('does not copy a shared loan or another property onto this row', function () {
    $entity = portfolioHoldingEntity();
    $entity->update(['registered_email' => 'portfolio-shared-loan@example.test']);
    $loan = portfolioHoldingBank($entity, BankAccount::PURPOSE_LOAN, 'Shared loan');

    $first = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'First house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
        'loan_payment_amount' => 111,
        'loan_payment_frequency' => 'Monthly',
    ]);
    $second = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Second house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
        'loan_payment_amount' => 222,
        'loan_payment_frequency' => 'Monthly',
    ]);
    $splitHouse = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Split house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $splitLoan = portfolioHoldingBank($entity, BankAccount::PURPOSE_LOAN, 'Split loan');
    $splitLoan->update(['account_number' => '44556677']);
    $first->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);
    $second->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);
    $splitHouse->bankAccounts()->attach($splitLoan->id, ['role' => BankAccount::ROLE_LOAN]);

    portfolioHoldingTransaction($entity, $loan, 'loan_repayments', 5000, '2026-06-01');
    portfolioHoldingTransaction($entity, $loan, 'loan_interest', 80, '2026-06-01', $second);

    $split = portfolioHoldingTransaction($entity, $splitLoan, Transaction::TYPE_SPLIT, 900, '2026-05-01');
    $split->lines()->create([
        'sort_order' => 0,
        'transaction_type' => 'loan_repayments',
        'amount' => 900,
    ]);

    $rows = collect(app(PropertyReportService::class)->portfolio(
        [$entity->id],
        '2025-07-01',
        '2026-06-30'
    )['properties'])->keyBy(fn (array $row) => $row['asset']->name);

    expect($rows['First house']['repayment'])->toBe(111.0)
        ->and($rows['First house']['interest'])->toBeNull()
        ->and($rows['Second house']['repayment'])->toBe(222.0)
        ->and($rows['First house']['period_expenses'])->toBe(1332.0)
        ->and($rows['Second house']['interest'])->toBe(80.0)
        ->and($rows['Second house']['period_expenses'])->toBe(2744.0)
        ->and($rows['Split house']['repayment'])->toBe(900.0)
        ->and($rows['Split house']['period_expenses'])->toBe(900.0)
        ->and($rows->sum('repayment'))->toBe(1233.0);
});
