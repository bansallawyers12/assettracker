<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PropertyReportService;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function monthlyCashEntity(string $name): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => $name,
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
        'phone_number' => '0400000101',
    ]);
}

function monthlyCashAsset(BusinessEntity $entity, string $name, array $extra = []): Asset
{
    return Asset::create(array_merge([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => $name,
        'address' => $name.' Road',
        'acquisition_date' => '2020-01-01',
        'acquisition_cost' => 100000,
        'current_value' => 100000,
        'status' => 'Active',
    ], $extra));
}

function monthlyCashBank(BusinessEntity $entity): BankAccount
{
    return BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => (string) random_int(10000000, 99999999),
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
}

function monthlyCashPaid(BusinessEntity $entity, BankAccount $bank, Asset $asset, string $type, float $amount, string $date): Transaction
{
    return Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'asset_id' => $asset->id,
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

function monthlyCashDue(BusinessEntity $entity, BankAccount $bank, Asset $asset, string $type, float $amount, string $date): Transaction
{
    return Transaction::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $bank->id,
        'asset_id' => $asset->id,
        'date' => $date,
        'due_date' => $date,
        'amount' => $amount,
        'description' => $type,
        'transaction_type' => $type,
        'payment_status' => 'unpaid',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'paid_by' => 'be:'.$entity->id,
    ]);
}

function monthlyCashJournal(BusinessEntity $entity, User $user, string $code, string $accountName, float $amount, string $date): void
{
    $account = ChartOfAccount::query()->firstOrCreate(
        ['account_code' => $code],
        [
            'account_name' => $accountName,
            'account_type' => 'expense',
            'account_category' => 'operating_expense',
            'is_active' => true,
        ]
    );
    $offset = ChartOfAccount::query()->firstOrCreate(
        ['account_code' => '1100'],
        [
            'account_name' => 'Bank',
            'account_type' => 'asset',
            'account_category' => 'current_asset',
            'is_active' => true,
        ]
    );
    $entry = JournalEntry::create([
        'business_entity_id' => $entity->id,
        'entry_date' => $date,
        'reference_number' => 'MJ-'.$code.'-'.$entity->id,
        'description' => $accountName,
        'total_debit' => abs($amount),
        'total_credit' => abs($amount),
        'is_posted' => true,
        'created_by' => $user->id,
    ]);
    JournalLine::create([
        'journal_entry_id' => $entry->id,
        'chart_of_account_id' => $account->id,
        'debit_amount' => $amount,
        'credit_amount' => 0,
        'description' => $accountName,
    ]);
    JournalLine::create([
        'journal_entry_id' => $entry->id,
        'chart_of_account_id' => $offset->id,
        'debit_amount' => 0,
        'credit_amount' => $amount,
        'description' => $accountName,
    ]);
}

it('resolves each monthly cost in source order and does not double count', function () {
    $user = User::factory()->create();
    $inYear = FinancialYear::currentStart()->addMonths(2)->toDateString();
    $lastYear = FinancialYear::previousStart()->addMonths(3)->toDateString();
    $start = FinancialYear::currentStart()->toDateString();
    $end = FinancialYear::currentEnd()->toDateString();

    $paidEntity = monthlyCashEntity('Paid Land Tax Pty Ltd');
    $paidBank = monthlyCashBank($paidEntity);
    $paidAsset = monthlyCashAsset($paidEntity, 'Paid House', ['land_tax_amount' => 99999]);
    monthlyCashPaid($paidEntity, $paidBank, $paidAsset, 'land_tax', 1200, $inYear);
    monthlyCashJournal($paidEntity, $user, '5130', 'Land Tax', 2400, $inYear);
    monthlyCashDue($paidEntity, $paidBank, $paidAsset, 'land_tax', 3600, $inYear);

    $journalEntity = monthlyCashEntity('Journal Land Tax Pty Ltd');
    $journalAsset = monthlyCashAsset($journalEntity, 'Journal House', ['land_tax_amount' => 99999]);
    monthlyCashJournal($journalEntity, $user, '5130', 'Land Tax', 2400, $inYear);
    monthlyCashDue($journalEntity, monthlyCashBank($journalEntity), $journalAsset, 'land_tax', 3600, $inYear);

    $dueEntity = monthlyCashEntity('Due Land Tax Pty Ltd');
    $dueAsset = monthlyCashAsset($dueEntity, 'Due House', ['land_tax_amount' => 99999]);
    monthlyCashDue($dueEntity, monthlyCashBank($dueEntity), $dueAsset, 'land_tax', 3600, $inYear);
    monthlyCashPaid($dueEntity, monthlyCashBank($dueEntity), $dueAsset, 'land_tax', 4800, $lastYear);

    $lastYearEntity = monthlyCashEntity('Last Year Land Tax Pty Ltd');
    $lastYearAsset = monthlyCashAsset($lastYearEntity, 'Last Year House', ['land_tax_amount' => 99999]);
    monthlyCashPaid($lastYearEntity, monthlyCashBank($lastYearEntity), $lastYearAsset, 'land_tax', 4800, $lastYear);

    $savedEntity = monthlyCashEntity('Saved Land Tax Pty Ltd');
    monthlyCashAsset($savedEntity, 'Saved House', ['land_tax_amount' => 6000]);

    $missingEntity = monthlyCashEntity('Missing Land Tax Pty Ltd');
    monthlyCashAsset($missingEntity, 'Clayton');

    $service = app(PropertyReportService::class);
    $row = function (BusinessEntity $entity) use ($service, $start, $end): array {
        return collect($service->portfolio([$entity->id], $start, $end)['properties'])->first();
    };

    $paid = $row($paidEntity);
    $journal = $row($journalEntity);
    $due = $row($dueEntity);
    $previous = $row($lastYearEntity);
    $saved = $row($savedEntity);
    $missing = $row($missingEntity);

    expect($paid['monthly']['land_tax'])->toBe(['amount' => 100.0, 'source' => 'paid'])
        ->and($paid['land_tax'])->toBe(1200.0)
        ->and($journal['monthly']['land_tax'])->toBe(['amount' => 200.0, 'source' => 'journal'])
        ->and($due['monthly']['land_tax'])->toBe(['amount' => 300.0, 'source' => 'due'])
        ->and($previous['monthly']['land_tax'])->toBe(['amount' => 400.0, 'source' => 'last year'])
        ->and($saved['monthly']['land_tax'])->toBe(['amount' => 500.0, 'source' => 'saved'])
        ->and($missing['monthly']['land_tax'])->toBe(['amount' => null, 'source' => 'missing'])
        ->and(collect($service->portfolio([$missingEntity->id], $start, $end)['checklist'])->pluck('text')->all())
        ->toContain('Clayton — land tax: missing')
        ->and(collect($service->portfolio([$lastYearEntity->id], $start, $end)['checklist'])->pluck('text')->all())
        ->toContain('Last Year House — land tax: last year\'s figure, nothing entered this year');
});

it('leaves interest out of monthly expenses and rounds set aside up with the buffer', function () {
    $entity = monthlyCashEntity('Set Aside Pty Ltd');
    $bank = monthlyCashBank($entity);
    $inYear = FinancialYear::currentStart()->addMonths(2)->toDateString();
    $asset = monthlyCashAsset($entity, 'Buffer House', [
        'loan_payment_amount' => 99999,
        'loan_payment_frequency' => 'Monthly',
        'rental_income' => 6000,
    ]);
    monthlyCashPaid($entity, $bank, $asset, 'loan_repayments', 6000, FinancialYear::currentStart()->addMonth()->toDateString());
    monthlyCashPaid($entity, $bank, $asset, 'loan_repayments', 6000, $inYear);
    monthlyCashPaid($entity, $bank, $asset, 'loan_interest', 6000, $inYear);

    $start = FinancialYear::currentStart()->toDateString();
    $end = FinancialYear::currentEnd()->toDateString();
    $row = collect(app(PropertyReportService::class)->portfolio([$entity->id], $start, $end)['properties'])->first();

    expect($row['repayment'])->toBe(6000.0)
        ->and($row['monthly']['repayment'])->toBe(['amount' => 1000.0, 'source' => 'paid'])
        ->and($row['monthly']['interest'])->toBe(['amount' => 500.0, 'source' => 'paid'])
        ->and($row['monthly']['rent'])->toBe(['amount' => 500.0, 'source' => 'saved'])
        ->and($row['monthly']['expenses'])->toBe(1000.0)
        ->and($row['monthly']['set_aside'])->toBe(550.0);

    $fractionEntity = monthlyCashEntity('Fraction Pty Ltd');
    monthlyCashAsset($fractionEntity, 'Fraction House', [
        'land_tax_amount' => 1210,
        'rental_income' => 100,
    ]);
    $fraction = collect(app(PropertyReportService::class)->portfolio(
        [$fractionEntity->id],
        $start,
        $end,
        PropertyReportService::BASIS_CASH,
        false,
        0
    )['properties'])->first();

    expect($fraction['monthly']['land_tax']['amount'])->toBe(100.83)
        ->and($fraction['monthly']['rent']['amount'])->toBe(8.33)
        ->and($fraction['monthly']['set_aside'])->toBe(93.0);
});

it('keeps an annual bill as a twelfth of the amount found when the period is one month', function () {
    $entity = monthlyCashEntity('October Bill Pty Ltd');
    $bank = monthlyCashBank($entity);
    $asset = monthlyCashAsset($entity, 'October House');
    monthlyCashPaid($entity, $bank, $asset, 'land_tax', 1200, '2026-10-15');
    monthlyCashPaid($entity, $bank, $asset, 'loan_repayments', 1000, '2026-10-15');

    $row = collect(app(PropertyReportService::class)->portfolio(
        [$entity->id],
        '2026-10-01',
        '2026-10-31'
    )['properties'])->first();

    expect($row['monthly']['land_tax'])->toBe(['amount' => 100.0, 'source' => 'paid'])
        ->and($row['monthly']['repayment']['source'])->toBe('paid')
        ->and($row['monthly']['repayment']['amount'])->toBeGreaterThan(900.0)
        ->and(collect(app(PropertyReportService::class)->portfolio([$entity->id], '2026-10-01', '2026-10-31')['checklist'])->pluck('text')->all())
        ->not->toContain('October House — interest: missing')
        ->and(collect(app(PropertyReportService::class)->portfolio([$entity->id], '2026-10-01', '2026-10-31')['checklist'])->pluck('text')->all())
        ->not->toContain('October House — other expenses: missing');
});

it('shows monthly columns by default and keeps yield columns behind the yield view', function () {
    $entity = monthlyCashEntity('Clayton Portfolio Pty Ltd');
    monthlyCashAsset($entity, 'Clayton');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('portfolio.index'))
        ->assertSuccessful()
        ->assertSee('Monthly rent')
        ->assertSee('Monthly expenses')
        ->assertSee('Set aside')
        ->assertSee('Insurance')
        ->assertDontSee('Still to enter')
        ->assertDontSee('Gross yield');

    $this->actingAs($user)
        ->get(route('portfolio.index', ['view' => 'yield']))
        ->assertSuccessful()
        ->assertSee('Gross yield')
        ->assertSee('Net yield')
        ->assertDontSee('Monthly rent');
});
