<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BankAccountStatement;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
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
        ->assertSee('Filters')
        ->assertDontSee('Report settings')
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

it('explains each portfolio figure on the property page', function () {
    $entity = portfolioHoldingEntity();
    $entity->update(['registered_email' => 'portfolio-figures@example.test']);
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
    portfolioHoldingTransaction($entity, $loan, 'internal_transfer', 250, '2026-06-20');
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

    $fundedReport = app(PropertyReportService::class)->propertyProfitLoss($funded, '2025-07-01', '2026-06-30');
    $fundedFigures = collect($fundedReport['breakdown']['figures'])->keyBy('key');

    expect(round(collect($fundedFigures['income']['lines'])->sum('amount'), 2))->toBe(12000.0)
        ->and(round(collect($fundedFigures['expenses']['lines'])->sum('amount'), 2))->toBe(17776.34)
        ->and($fundedFigures['repayment']['amount'])->toBe(6608.0)
        ->and($fundedFigures['loan_balance']['amount'])->toBe(1940433.54)
        ->and($fundedFigures['income']['text'])->toContain('was not added again')
        ->and($fundedFigures['repayment']['text'])->toContain('latest loan repayment in this period: $6,608.00 on 15 Jun 2026')
        ->and($fundedFigures['repayment']['text'])->toContain('Expenses include every loan repayment in this period, $7,608.00')
        ->and($fundedFigures['expenses']['text'])->toContain('The Repayment column is $6,608.00, which is not the loan repayment inside this total ($7,608.00)')
        ->and(collect($fundedReport['breakdown']['left_out'])->pluck('mark')->join(' '))->toContain('Internal Transfer is left out');

    $savedReport = app(PropertyReportService::class)->propertyProfitLoss($saved, '2025-07-01', '2026-06-30');
    $savedFigures = collect($savedReport['breakdown']['figures'])->keyBy('key');

    expect(round(collect($savedFigures['income']['lines'])->sum('amount'), 2))->toBe(24000.0)
        ->and(round(collect($savedFigures['expenses']['lines'])->sum('amount'), 2))->toBe(28400.0)
        ->and($savedFigures['repayment']['text'])->toContain('No loan repayment was recorded in this period')
        ->and($savedFigures['repayment']['text'])->toContain('fortnightly repayment of $1,000.00')
        ->and($savedFigures['repayment']['text'])->toContain('$26,000.00 of it falls in this period')
        ->and($savedFigures['income']['text'])->toContain('No rent was banked in this period')
        ->and($savedFigures['income']['text'])->toContain('Bald Hill tenant')
        ->and($savedFigures['council_rates']['text'])->toContain('Nothing was recorded for Council rates')
        ->and($savedFigures['loan_balance']['text'])->toContain('No loan account is linked');

    $this->actingAs(User::factory()->create())
        ->get(route('assets.financials', [
            'businessEntity' => $entity,
            'asset' => $funded,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]))
        ->assertSuccessful()
        ->assertSee('Where the portfolio figures come from')
        ->assertSee('PDF statement closing balance of $1,940,433.54 as at 31/05/2026')
        ->assertSee('The statement balance is -$1,940,433.54 and is shown as a positive balance.')
        ->assertSee('The $1.00 saved on the property was not used.')
        ->assertSee('Included in expenses, not in the Repayment column.')
        ->assertSee('$17,776.34')
        ->assertSee('data-figure="repayment"', false);
});

it('keeps negative interest and does not borrow another property from a shared loan', function () {
    $entity = portfolioHoldingEntity();
    $entity->update(['registered_email' => 'portfolio-review@example.test']);
    $loan = portfolioHoldingBank($entity, BankAccount::PURPOSE_LOAN, 'Shared loan');

    $active = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Active house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $other = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Other house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $inactive = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Inactive house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Inactive',
    ]);
    $active->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);
    $other->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);
    $inactive->bankAccounts()->attach($loan->id, ['role' => BankAccount::ROLE_LOAN]);

    portfolioHoldingTransaction($entity, $loan, 'loan_interest', -40, '2026-02-01', $active);
    portfolioHoldingTransaction($entity, $loan, 'repairs_maintenance', 75, '2026-03-01', $other);
    portfolioHoldingTransaction($entity, $loan, 'internal_transfer', 90, '2026-04-01');

    $report = app(PropertyReportService::class)->propertyProfitLoss($active, '2025-07-01', '2026-06-30');
    $figures = collect($report['breakdown']['figures'])->keyBy('key');
    $leftOut = collect($report['breakdown']['left_out'])->pluck('description')->join(' ');

    expect($figures['interest']['amount'])->toBeNull()
        ->and($figures['interest']['text'])->toContain('-$40.00')
        ->and($figures['interest']['lines'][0]['mark'])->toContain('Included in expenses')
        ->and($figures['expenses']['amount'])->toBe(-40.0)
        ->and($report['expenses']['by_type']['loan_interest']['amount'])->toBe(-40.0)
        ->and($leftOut)->not->toContain('repairs_maintenance')
        ->and($leftOut)->not->toContain('internal_transfer');

    $soleLoan = portfolioHoldingBank($entity, BankAccount::PURPOSE_LOAN, 'Sole loan');
    $soleLoan->update(['account_number' => '11223344']);
    $only = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Only house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $only->bankAccounts()->attach($soleLoan->id, ['role' => BankAccount::ROLE_LOAN]);
    $parked = Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => 'Parked house',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Inactive',
    ]);
    $parked->bankAccounts()->attach($soleLoan->id, ['role' => BankAccount::ROLE_LOAN]);
    portfolioHoldingTransaction($entity, $soleLoan, 'loan_repayments', 640, '2026-05-01');

    $soleReport = app(PropertyReportService::class)->propertyProfitLoss($only, '2025-07-01', '2026-06-30');
    $soleFigures = collect($soleReport['breakdown']['figures'])->keyBy('key');

    expect($soleFigures['repayment']['amount'])->toBe(640.0)
        ->and($soleFigures['loan_balance']['text'])->not->toContain('Parked house');
});

it('adds manual journal interest and does not double count a posted transaction', function () {
    $user = User::factory()->create();
    $soleEntity = portfolioHoldingEntity();
    $soleEntity->update(['registered_email' => 'portfolio-interest-sole@example.test']);
    $sole = Asset::create([
        'business_entity_id' => $soleEntity->id,
        'asset_type' => 'House Rented',
        'name' => 'Sole Interest House',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    portfolioManualInterest($soleEntity, $user, 'MJ-SOLE', 250, 'Loan interest', '2026-03-15');
    portfolioManualInterest($soleEntity, $user, 'MJ-OLD', 999, 'Loan interest', '2020-01-01');
    portfolioManualInterest($soleEntity, $user, 'OPEN-1', 80, 'Opening interest', '2026-03-15');

    $sharedEntity = portfolioHoldingEntity();
    $sharedEntity->update(['registered_email' => 'portfolio-interest-shared@example.test', 'legal_name' => 'Shared Interest Pty Ltd']);
    $named = Asset::create([
        'business_entity_id' => $sharedEntity->id,
        'asset_type' => 'House Rented',
        'name' => 'First Cranbury Road',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    $other = Asset::create([
        'business_entity_id' => $sharedEntity->id,
        'asset_type' => 'House Rented',
        'name' => 'Second Manning Road',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ]);
    portfolioHoldingTransaction($sharedEntity, portfolioHoldingBank($sharedEntity, BankAccount::PURPOSE_GENERAL, 'Operating'), 'loan_interest', 40, '2026-02-01', $named);
    portfolioManualInterest($sharedEntity, $user, 'MJ-NAMED', 80, 'Interest First Cranbury Road', '2026-04-01');
    portfolioManualInterest($sharedEntity, $user, 'MJ-PLAIN', 30, 'Interest', '2026-05-01');
    portfolioManualInterest($sharedEntity, $user, 'MJ-TXN', 999, 'Interest First Cranbury Road', '2026-05-02', Transaction::class);

    $service = app(PropertyReportService::class);
    $soleRow = collect($service->portfolio([$soleEntity->id], '2025-07-01', '2026-06-30')['properties'])->first();
    $sharedRows = collect($service->portfolio([$sharedEntity->id], '2025-07-01', '2026-06-30')['properties'])
        ->keyBy(fn (array $row) => $row['asset']->name);

    expect($soleRow['interest'])->toBe(250.0)
        ->and($soleRow['period_expenses'])->toBe(250.0)
        ->and($sharedRows['First Cranbury Road']['interest'])->toBe(120.0)
        ->and($sharedRows['Second Manning Road']['interest'])->toBeNull();

    $namedReport = $service->propertyProfitLoss($named, '2025-07-01', '2026-06-30');
    $namedInterest = collect($namedReport['breakdown']['figures'])->firstWhere('key', 'interest');
    $otherInterest = collect($service->propertyProfitLoss($other, '2025-07-01', '2026-06-30')['breakdown']['figures'])
        ->firstWhere('key', 'interest');

    expect($namedInterest['text'])->toContain('Manual journals add $80.00')
        ->and($namedInterest['text'])->toContain('Interest recorded on transactions is $40.00')
        ->and($namedInterest['text'])->toContain('$30.00 more manual journal interest')
        ->and($otherInterest['text'])->toContain('$30.00 more manual journal interest')
        ->and(collect($namedInterest['lines'])->contains(fn (array $line) => ($line['mark'] ?? '') === 'Manual journal to Interest Expense.'))->toBeTrue();
});

function portfolioManualInterest(
    BusinessEntity $entity,
    User $user,
    string $reference,
    float $amount,
    string $description,
    string $date,
    ?string $sourceType = null
): void {
    $interest = ChartOfAccount::query()->firstOrCreate(
        ['account_code' => '7500'],
        [
            'account_name' => 'Interest Expense',
            'account_type' => 'expense',
            'account_category' => 'operating_expense',
            'is_active' => true,
        ]
    );
    $loan = ChartOfAccount::query()->firstOrCreate(
        ['account_code' => '4000'],
        [
            'account_name' => 'Long Term Loans',
            'account_type' => 'liability',
            'account_category' => 'long_term_liability',
            'is_active' => true,
        ]
    );
    $entry = JournalEntry::create([
        'business_entity_id' => $entity->id,
        'entry_date' => $date,
        'reference_number' => $reference,
        'description' => $description,
        'total_debit' => abs($amount),
        'total_credit' => abs($amount),
        'is_posted' => true,
        'created_by' => $user->id,
        'source_type' => $sourceType,
    ]);
    JournalLine::create([
        'journal_entry_id' => $entry->id,
        'chart_of_account_id' => $interest->id,
        'debit_amount' => $amount,
        'credit_amount' => 0,
        'description' => $description,
    ]);
    JournalLine::create([
        'journal_entry_id' => $entry->id,
        'chart_of_account_id' => $loan->id,
        'debit_amount' => 0,
        'credit_amount' => $amount,
        'description' => $description,
    ]);
}
