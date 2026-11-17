<?php

use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\InvoicePaymentAllocation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GstWorksheetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function gstWorksheetEntity(string $name, array $extra = []): BusinessEntity
{
    return BusinessEntity::create(array_merge([
        'legal_name' => $name,
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
        'phone_number' => '0400000191',
        'gst_registered' => true,
        'bas_reporting_frequency' => 'quarterly',
    ], $extra));
}

function gstWorksheetPaid(
    BusinessEntity $entity,
    string $type,
    float $amount,
    float $gst,
    string $date,
    ?string $paidAt = null,
    string $status = 'paid',
): Transaction {
    return Transaction::create([
        'business_entity_id' => $entity->id,
        'date' => $date,
        'paid_at' => $paidAt,
        'amount' => $amount,
        'gst_amount' => $gst,
        'gst_basis' => 'inclusive',
        'description' => $type,
        'transaction_type' => $type,
        'payment_status' => $status,
    ]);
}

function gstWorksheetReport(array $ids, bool $includeUnregistered = false, ?string $layout = null): array
{
    return app(GstWorksheetService::class)->generate(
        $ids,
        Carbon::parse('2025-07-01'),
        $includeUnregistered,
        $layout,
    );
}

it('adds quarterly gst columns that sum to the financial year', function () {
    $entity = gstWorksheetEntity('Quarter Sum Pty Ltd');

    gstWorksheetPaid($entity, 'rental_income', 1100, 100, '2025-08-15', '2025-08-15');
    gstWorksheetPaid($entity, 'repairs_maintenance', 220, 20, '2025-09-10', '2025-09-10');
    gstWorksheetPaid($entity, 'rental_income', 550, 50, '2025-11-02', '2025-11-02');
    gstWorksheetPaid($entity, 'sales_revenue', 110, 10, '2026-05-01', '2026-05-01');
    gstWorksheetPaid($entity, 'bas_payments', 110, 0, '2025-09-28', '2025-09-28');
    gstWorksheetPaid($entity, 'payg_payment', 80, 0, '2025-08-20', '2025-08-20');
    gstWorksheetPaid($entity, 'rental_income', 330, 30, '2025-07-20', null);
    gstWorksheetPaid($entity, 'rental_income', 440, 40, '2025-07-01', '2025-10-05');
    gstWorksheetPaid($entity, 'rental_income', 999, 99, '2025-08-01', '2025-08-01', 'unpaid');

    $report = gstWorksheetReport([$entity->id]);
    $periods = $report['entities'][0]['periods'];

    expect($periods['q1']['gst_collected'])->toBe(130.0)
        ->and($periods['q1']['gst_credits'])->toBe(20.0)
        ->and($periods['q1']['sales_ex_gst'])->toBe(1300.0)
        ->and($periods['q1']['purchases_ex_gst'])->toBe(200.0)
        ->and($periods['q2']['gst_collected'])->toBe(90.0)
        ->and($periods['q4']['gst_collected'])->toBe(10.0)
        ->and($periods['q3']['gst_collected'])->toBe(0.0);

    foreach (['sales_ex_gst', 'purchases_ex_gst', 'gst_collected', 'gst_credits', 'net_gst', 'total_bas'] as $row) {
        $quarterSum = round(
            $periods['q1'][$row] + $periods['q2'][$row] + $periods['q3'][$row] + $periods['q4'][$row],
            2
        );

        expect($quarterSum)->toBe($periods['fy'][$row]);
    }

    expect($periods['q1']['net_gst'])->toBe(110.0)
        ->and($periods['q1']['purchases_ex_gst'])->toBe(200.0)
        ->and($periods['q1']['total_bas'])->toBe(110.0)
        ->and($periods['q1'])->not->toHaveKey('payg_paid');
});

it('leaves a gst payment to the ato off the worksheet payg row', function () {
    $entity = gstWorksheetEntity('Gst Payment Only Pty Ltd');

    gstWorksheetPaid($entity, 'rental_income', 11000, 1000, '2026-08-15', '2026-08-15');
    gstWorksheetPaid($entity, 'repairs_maintenance', 3300, 300, '2026-08-20', '2026-08-20');
    gstWorksheetPaid($entity, 'bas_payments', 700, 70, '2026-09-21', '2026-09-21');

    $report = app(GstWorksheetService::class)->generate(
        [$entity->id],
        Carbon::parse('2026-07-01'),
        false,
        'quarterly',
    );
    $periods = $report['entities'][0]['periods'];
    $labels = collect($report['rows'])->pluck('label')->all();

    expect($labels)->not->toContain('PAYG paid')
        ->and($periods['q1']['sales_ex_gst'])->toBe(10000.0)
        ->and($periods['q1']['purchases_ex_gst'])->toBe(3000.0)
        ->and($periods['q1']['gst_collected'])->toBe(1000.0)
        ->and($periods['q1']['gst_credits'])->toBe(300.0)
        ->and($periods['q1']['net_gst'])->toBe(700.0)
        ->and($periods['q1']['total_bas'])->toBe(700.0);
});

it('omits an unregistered entity until included, then keeps its gst out of the bas figures', function () {
    $registered = gstWorksheetEntity('Registered Gst Pty Ltd');
    $unregistered = gstWorksheetEntity('No Gst Pty Ltd', [
        'gst_registered' => false,
        'bas_reporting_frequency' => 'annual',
    ]);

    gstWorksheetPaid($registered, 'rental_income', 1100, 100, '2025-08-15', '2025-08-15');
    gstWorksheetPaid($unregistered, 'rental_income', 110, 10, '2025-08-16', '2025-08-16');

    $hidden = gstWorksheetReport([$registered->id, $unregistered->id]);

    expect(collect($hidden['entities'])->pluck('entity.id')->all())->toBe([$registered->id])
        ->and($hidden['entities'][0]['periods']['q1']['gst_collected'])->toBe(100.0);

    $shown = gstWorksheetReport([$registered->id, $unregistered->id], true);
    $unregisteredRow = collect($shown['entities'])->first(fn (array $row) => $row['entity']->id === $unregistered->id);

    expect($unregisteredRow['gst_registered'])->toBeFalse()
        ->and($unregisteredRow['periods']['q1']['sales_ex_gst'])->toBe(100.0)
        ->and($unregisteredRow['periods']['q1']['gst_collected'])->toBeNull()
        ->and($unregisteredRow['periods']['q1']['gst_credits'])->toBeNull()
        ->and($unregisteredRow['periods']['q1']['net_gst'])->toBeNull()
        ->and($unregisteredRow['periods']['q1']['total_bas'])->toBeNull()
        ->and($unregisteredRow['periods']['q1']['gst_recorded_warning'])->toBe(10.0)
        ->and($shown['portfolio']['periods']['q1']['gst_collected'])->toBe(100.0)
        ->and($shown['portfolio']['periods']['q1']['gst_recorded_warning'])->toBe(10.0)
        ->and($shown['portfolio']['periods']['q1']['sales_ex_gst'])->toBe(1100.0);
});

it('opens mixed lodgement cycles on quarterly columns and honours layout=annual', function () {
    $quarterly = gstWorksheetEntity('Quarterly Lodger Pty Ltd', [
        'bas_reporting_frequency' => 'quarterly',
    ]);
    $annual = gstWorksheetEntity('Annual Lodger Pty Ltd', [
        'bas_reporting_frequency' => 'annual',
    ]);
    $monthly = gstWorksheetEntity('Monthly Lodger Pty Ltd', [
        'bas_reporting_frequency' => 'monthly',
    ]);

    $mixed = gstWorksheetReport([$quarterly->id, $annual->id]);
    expect($mixed['layout'])->toBe('quarterly')
        ->and($mixed['visible_period_keys'])->toBe(['q1', 'q2', 'q3', 'q4', 'fy'])
        ->and(collect($mixed['entities'])->pluck('lodgement_label')->all())
        ->toContain('Lodges quarterly')
        ->toContain('Lodges annually');

    $forced = gstWorksheetReport([$quarterly->id, $annual->id], false, 'annual');
    expect($forced['layout'])->toBe('annual')
        ->and($forced['visible_period_keys'])->toBe(['fy'])
        ->and(collect($forced['entities'])->pluck('lodgement_label')->all())
        ->toContain('Lodges quarterly')
        ->toContain('Lodges annually');

    $onlyAnnual = gstWorksheetReport([$annual->id]);
    expect($onlyAnnual['layout'])->toBe('annual');

    $onlyMonthly = gstWorksheetReport([$monthly->id]);
    expect($onlyMonthly['layout'])->toBe('quarterly')
        ->and($onlyMonthly['entities'][0]['lodgement_label'])->toBe('Lodges quarterly');
});

it('opens quarter links on the payment date rather than the transaction date', function () {
    $user = User::factory()->create();
    $entity = gstWorksheetEntity('Cash Date Pty Ltd');
    gstWorksheetPaid($entity, 'rental_income', 110, 10, '2025-07-01', '2025-10-05');
    Transaction::query()->where('business_entity_id', $entity->id)->update([
        'description' => 'October paid July dated',
    ]);

    $byPaymentDate = $this->actingAs($user)->get(route('transactions.index', [
        'entity_id' => $entity->id,
        'date_from' => '2025-10-01',
        'date_to' => '2025-12-31',
        'payment_status' => 'paid',
        'cash_dated' => 1,
    ]));
    $byPaymentDate->assertOk();
    $byPaymentDate->assertSee('October paid July dated');
    $byPaymentDate->assertSee('data-tx-cash-dated-notice', false);

    $byTransactionDate = $this->actingAs($user)->get(route('transactions.index', [
        'entity_id' => $entity->id,
        'date_from' => '2025-10-01',
        'date_to' => '2025-12-31',
        'payment_status' => 'paid',
    ]));
    $byTransactionDate->assertOk();
    $byTransactionDate->assertDontSee('October paid July dated');
});

it('shows the gst worksheet with quarter links, em dashes, and the reports index entry', function () {
    $user = User::factory()->create();
    $quarterly = gstWorksheetEntity('Screen Quarterly Pty Ltd', [
        'bas_reporting_frequency' => 'quarterly',
    ]);
    $annual = gstWorksheetEntity('Screen Annual Pty Ltd', [
        'bas_reporting_frequency' => 'annual',
    ]);
    $unregistered = gstWorksheetEntity('Screen Unregistered Pty Ltd', [
        'gst_registered' => false,
    ]);

    gstWorksheetPaid($quarterly, 'rental_income', 1100, 100, '2025-08-15', '2025-08-15');
    gstWorksheetPaid($quarterly, 'repairs_maintenance', 220, 20, '2025-09-10', '2025-09-10');
    gstWorksheetPaid($unregistered, 'rental_income', 110, 10, '2025-08-16', '2025-08-16');

    $query = [
        'fy' => '2025-07-01',
        'scope' => 'selected',
        'entity_ids' => [$quarterly->id, $annual->id, $unregistered->id],
    ];

    $default = $this->actingAs($user)->get(route('financial-reports.gst', $query));
    $default->assertOk();
    $default->assertSee('data-gst-layout="quarterly"', false);
    $default->assertSee('Q1 Jul–Sep', false);
    $default->assertSee('Lodges quarterly');
    $default->assertSee('Lodges annually');
    $default->assertSee('data-gst-cash-basis-notice', false);
    $default->assertDontSee('data-gst-entity="'.$unregistered->id.'"', false);
    $default->assertDontSee('Not GST registered');
    $default->assertSee('data-gst-entity="'.$quarterly->id.'"', false);
    $default->assertSee('data-gst-cell="gst_collected" data-period="q1" data-entity="'.$quarterly->id.'"', false);
    $default->assertSee('date_from=2025-07-01', false);
    $default->assertSee('date_to=2025-09-30', false);
    $default->assertSee('payment_status=paid', false);
    $default->assertSee('cash_dated=1', false);
    $default->assertSee('gst_activity=1', false);
    $default->assertSee('direction=income', false);
    $default->assertDontSee('PAYG paid');
    $default->assertSee('100.00');
    $default->assertSee('20.00');

    $annualLayout = $this->actingAs($user)->get(route('financial-reports.gst', array_merge($query, [
        'layout' => 'annual',
    ])));
    $annualLayout->assertOk();
    $annualLayout->assertSee('data-gst-layout="annual"', false);
    $annualLayout->assertDontSee('Q1 Jul–Sep', false);
    $annualLayout->assertSee('Lodges quarterly');
    $annualLayout->assertSee('Lodges annually');
    $annualLayout->assertSee('FY 2025-2026', false);

    $included = $this->actingAs($user)->get(route('financial-reports.gst', array_merge($query, [
        'include_unregistered' => 1,
    ])));
    $included->assertOk();
    $included->assertSee('Screen Unregistered Pty Ltd');
    $included->assertSee('Not GST registered');
    $included->assertSee('GST recorded on transactions');
    $included->assertSee(
        'data-gst-cell="gst_collected" data-period="q1" data-entity="'.$unregistered->id.'" class="text-gray-400">—',
        false
    );
    $included->assertSee(
        'data-gst-cell="gst_recorded_warning" data-period="q1" data-entity="'.$unregistered->id.'"',
        false
    );
    $included->assertSee(
        'data-gst-cell="gst_collected" data-period="q1" data-gst-portfolio-cell="1"',
        false
    );
    $included->assertSee('>10.00<', false);
    $included->assertDontSee('>110.00<', false);

    $csv = $this->actingAs($user)->get(route('financial-reports.gst', array_merge($query, [
        'include_unregistered' => 1,
        'format' => 'csv',
    ])));
    $csv->assertOk();
    $csvBody = $csv->streamedContent();
    expect($csvBody)->toContain('GST worksheet')
        ->and($csvBody)->toContain('SECTION,Worksheet')
        ->and($csvBody)->toContain('Q1 Jul–Sep')
        ->and($csvBody)->toContain('Sales (ex GST)')
        ->and($csvBody)->toContain('GST recorded on transactions')
        ->and($csvBody)->toContain('Not GST registered')
        ->and($csvBody)->toContain('—')
        ->and($csvBody)->toContain('Supporting transactions')
        ->and($csvBody)->toContain('Each quarter is listed once')
        ->and($csvBody)->toContain('Rental Income')
        ->and($csvBody)->toContain('1000.00')
        ->and($csvBody)->toContain('Repairs')
        ->and($csvBody)->toContain('200.00')
        ->and($csvBody)->toContain('GST recorded')
        ->and(substr_count($csvBody, 'rental_income'))->toBe(2);

    $summary = $this->actingAs($user)->get(route('financial-reports.entity-summary', [
        'scope' => 'selected',
        'entity_ids' => [$quarterly->id],
        'fy_from' => '2025-07-01',
        'fy_to' => '2025-07-01',
    ]));
    $summary->assertOk();
    $summary->assertSee('data-entity-summary-gst-link', false);
    $summary->assertSee('/financial-reports/gst', false);

    $annualCsv = $this->actingAs($user)->get(route('financial-reports.gst', array_merge($query, [
        'layout' => 'annual',
        'format' => 'csv',
    ])));
    $annualCsv->assertOk();
    $annualBody = $annualCsv->streamedContent();
    expect($annualBody)->toContain('Supporting transactions')
        ->and($annualBody)->toContain('The financial year is listed once')
        ->and($annualBody)->toContain('FY 2025-2026')
        ->and($annualBody)->not->toContain('Q1 Jul')
        ->and(substr_count($annualBody, 'rental_income'))->toBe(1);

    $hub = file_get_contents(resource_path('views/financial-reports/index.blade.php'));
    $profitLoss = strpos($hub, "'route' => 'financial-reports.profit-loss'");
    $gst = strpos($hub, "'route' => 'financial-reports.gst'");
    expect($profitLoss)->not->toBeFalse()
        ->and($gst)->toBeGreaterThan($profitLoss);
});

it('lists only the paid transactions that make up each gst worksheet figure', function () {
    $user = User::factory()->create();
    $entity = gstWorksheetEntity('Export Lines Pty Ltd');

    gstWorksheetPaid($entity, 'rental_income', 1100, 100, '2025-08-15', '2025-08-15')
        ->update(['description' => 'August rent', 'invoice_number' => 'INV-AUG']);
    gstWorksheetPaid($entity, 'repairs_maintenance', 220, 20, '2025-09-10', '2025-09-10')
        ->update(['description' => 'September repair']);
    gstWorksheetPaid($entity, 'rental_income', 440, 40, '2025-07-01', '2025-10-05')
        ->update(['description' => 'October paid July dated']);
    gstWorksheetPaid($entity, 'payg_payment', 80, 0, '2025-08-20', '2025-08-20')
        ->update(['description' => 'PAYG instalment']);
    gstWorksheetPaid($entity, 'bas_payments', 110, 0, '2025-09-28', '2025-09-28')
        ->update(['description' => 'ATO gst payment']);
    gstWorksheetPaid($entity, 'rental_income', 999, 99, '2025-08-01', '2025-08-01', 'unpaid')
        ->update(['description' => 'Unpaid rent']);

    $split = gstWorksheetPaid($entity, 'split', 1320, 0, '2025-08-01', '2025-08-01');
    $split->update(['description' => 'Split parent']);
    $split->lines()->create([
        'sort_order' => 1,
        'transaction_type' => 'rental_income',
        'amount' => 1100,
        'gst_amount' => 100,
        'gst_basis' => 'inclusive',
        'description' => 'Split rent',
        'invoice_number' => 'INV-SPLIT',
    ]);
    $split->lines()->create([
        'sort_order' => 2,
        'transaction_type' => 'repairs_maintenance',
        'amount' => 220,
        'gst_amount' => 20,
        'gst_basis' => 'inclusive',
        'description' => 'Split repair',
    ]);

    $csv = $this->actingAs($user)->get(route('financial-reports.gst', [
        'fy' => '2025-07-01',
        'scope' => 'selected',
        'entity_ids' => [$entity->id],
        'layout' => 'quarterly',
        'format' => 'csv',
    ]));
    $csv->assertOk();

    $rows = array_map('str_getcsv', preg_split('/\r\n|\n|\r/', trim($csv->streamedContent())) ?: []);
    $supporting = [];
    $inSupporting = false;
    foreach ($rows as $row) {
        if (($row[0] ?? '') === 'SECTION' && ($row[1] ?? '') === 'Supporting transactions') {
            $inSupporting = true;

            continue;
        }
        if (! $inSupporting || ($row[0] ?? '') === 'Note' || ($row[0] ?? '') === 'Entity') {
            continue;
        }
        $supporting[] = $row;
    }

    $descriptions = array_column($supporting, 5);

    expect($descriptions)->toContain('August rent')
        ->and($descriptions)->toContain('September repair')
        ->and($descriptions)->toContain('October paid July dated')
        ->and($descriptions)->toContain('Split rent')
        ->and($descriptions)->toContain('Split repair')
        ->and($descriptions)->toContain('Total')
        ->and($descriptions)->not->toContain('PAYG instalment')
        ->and($descriptions)->not->toContain('ATO gst payment')
        ->and($descriptions)->not->toContain('Unpaid rent')
        ->and($descriptions)->not->toContain('Split parent');

    $august = collect($supporting)->first(fn (array $row) => ($row[5] ?? '') === 'August rent');
    $october = collect($supporting)->first(fn (array $row) => ($row[5] ?? '') === 'October paid July dated');
    $splitRent = collect($supporting)->first(fn (array $row) => ($row[5] ?? '') === 'Split rent');
    $q1Total = collect($supporting)->first(
        fn (array $row) => ($row[1] ?? '') === 'Q1 Jul–Sep' && ($row[5] ?? '') === 'Total'
    );
    $q2Total = collect($supporting)->first(
        fn (array $row) => ($row[1] ?? '') === 'Q2 Oct–Dec' && ($row[5] ?? '') === 'Total'
    );

    expect($august[1])->toBe('Q1 Jul–Sep')
        ->and($august[2])->toBe('2025-08-15')
        ->and($august[4])->toBe('INV-AUG')
        ->and($august[7])->toBe('Rental Income')
        ->and($august[10])->toBe('1000.00')
        ->and($august[12])->toBe('100.00')
        ->and($october[1])->toBe('Q2 Oct–Dec')
        ->and($october[2])->toBe('2025-10-05')
        ->and($october[3])->toBe('2025-07-01')
        ->and($splitRent[4])->toBe('INV-SPLIT')
        ->and($splitRent[10])->toBe('1000.00')
        ->and($q1Total[10])->toBe('2000.00')
        ->and($q1Total[11])->toBe('400.00')
        ->and($q1Total[12])->toBe('200.00')
        ->and($q1Total[13])->toBe('40.00')
        ->and($q2Total[10])->toBe('400.00')
        ->and($q2Total[12])->toBe('40.00');
});

it('leaves funding movements off the worksheet and takes gst from the invoice', function () {
    $entity = gstWorksheetEntity('Invoice Gst Pty Ltd');

    gstWorksheetPaid($entity, 'loan_drawdown', 5000, 0, '2025-08-01', '2025-08-01');
    gstWorksheetPaid($entity, 'equity_contribution', 2000, 0, '2025-08-02', '2025-08-02');
    gstWorksheetPaid($entity, 'director_loan_in', 300, 0, '2025-08-03', '2025-08-03');
    gstWorksheetPaid($entity, 'internal_transfer', 800, 0, '2025-08-04', '2025-08-04');
    gstWorksheetPaid($entity, 'loan_repayments', 400, 0, '2025-08-05', '2025-08-05');
    gstWorksheetPaid($entity, 'loan_interest', 50, 0, '2025-08-06', '2025-08-06');
    gstWorksheetPaid($entity, 'loan_fees', 25, 0, '2025-08-06', '2025-08-06');
    gstWorksheetPaid($entity, 'director_loan_out', 120, 0, '2025-08-07', '2025-08-07');
    gstWorksheetPaid($entity, 'asset_purchase', 1100, 100, '2025-08-08', '2025-08-08');

    $taxed = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV-TAXED',
        'issue_date' => '2025-08-01',
        'due_date' => '2025-08-08',
        'customer_name' => 'Tenant',
        'currency' => 'AUD',
        'status' => 'approved',
        'is_posted' => true,
        'gst_basis' => 'inclusive',
        'subtotal' => 1000,
        'gst_amount' => 100,
        'total_amount' => 1100,
    ]);
    $full = gstWorksheetPaid($entity, 'invoice_payment', 1100, 0, '2025-08-15', '2025-08-15');
    InvoicePaymentAllocation::create([
        'transaction_id' => $full->id,
        'invoice_id' => $taxed->id,
        'amount' => 1100,
    ]);

    $partialInvoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV-PART',
        'issue_date' => '2025-08-01',
        'due_date' => '2025-08-08',
        'customer_name' => 'Tenant',
        'currency' => 'AUD',
        'status' => 'partial',
        'is_posted' => true,
        'gst_basis' => 'inclusive',
        'subtotal' => 2000,
        'gst_amount' => 200,
        'total_amount' => 2200,
    ]);
    $partial = gstWorksheetPaid($entity, 'invoice_payment', 1100, 0, '2025-08-20', '2025-08-20');
    InvoicePaymentAllocation::create([
        'transaction_id' => $partial->id,
        'invoice_id' => $partialInvoice->id,
        'amount' => 1100,
    ]);

    $overriddenInvoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV-OVER',
        'issue_date' => '2025-08-01',
        'due_date' => '2025-08-08',
        'customer_name' => 'Tenant',
        'currency' => 'AUD',
        'status' => 'approved',
        'is_posted' => true,
        'gst_basis' => 'inclusive',
        'subtotal' => 1000,
        'gst_amount' => 100,
        'total_amount' => 1100,
    ]);
    $overridden = gstWorksheetPaid($entity, 'invoice_payment', 1100, 40, '2025-08-21', '2025-08-21');
    InvoicePaymentAllocation::create([
        'transaction_id' => $overridden->id,
        'invoice_id' => $overriddenInvoice->id,
        'amount' => 1100,
    ]);

    $free = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV-FREE',
        'issue_date' => '2025-08-01',
        'due_date' => '2025-08-08',
        'customer_name' => 'Tenant',
        'currency' => 'AUD',
        'status' => 'approved',
        'is_posted' => true,
        'gst_basis' => 'none',
        'subtotal' => 500,
        'gst_amount' => 0,
        'total_amount' => 500,
    ]);
    $freePayment = gstWorksheetPaid($entity, 'invoice_payment', 500, 0, '2025-08-22', '2025-08-22');
    InvoicePaymentAllocation::create([
        'transaction_id' => $freePayment->id,
        'invoice_id' => $free->id,
        'amount' => 500,
    ]);

    $periods = gstWorksheetReport([$entity->id])['entities'][0]['periods'];

    expect($periods['q1']['sales_ex_gst'])->toBe(3560.0)
        ->and($periods['q1']['gst_collected'])->toBe(240.0)
        ->and($periods['q1']['purchases_ex_gst'])->toBe(1000.0)
        ->and($periods['q1']['gst_credits'])->toBe(100.0)
        ->and($periods['q1']['net_gst'])->toBe(140.0);
});

it('opens gst worksheet links on the transactions that make up the cell', function () {
    $user = User::factory()->create();
    $entity = gstWorksheetEntity('Link Scope Pty Ltd');

    gstWorksheetPaid($entity, 'rental_income', 1100, 100, '2025-08-15', '2025-08-15')
        ->update(['description' => 'August rent']);
    gstWorksheetPaid($entity, 'loan_drawdown', 5000, 0, '2025-08-16', '2025-08-16')
        ->update(['description' => 'Loan drawn']);
    gstWorksheetPaid($entity, 'internal_transfer', 800, 0, '2025-08-17', '2025-08-17')
        ->update(['description' => 'Offset transfer']);
    gstWorksheetPaid($entity, 'payg_payment', 80, 0, '2025-08-20', '2025-08-20')
        ->update(['description' => 'PAYG instalment']);
    gstWorksheetPaid($entity, 'repairs_maintenance', 220, 20, '2025-09-10', '2025-09-10')
        ->update(['description' => 'September repair']);

    $sales = $this->actingAs($user)->get(route('transactions.index', [
        'entity_id' => $entity->id,
        'date_from' => '2025-07-01',
        'date_to' => '2025-09-30',
        'payment_status' => 'paid',
        'cash_dated' => 1,
        'gst_activity' => 1,
        'direction' => 'income',
    ]));

    $sales->assertOk();
    $sales->assertSee('August rent');
    $sales->assertSee('data-tx-gst-activity-notice', false);
    $sales->assertSee('data-tx-gst-ex-gst', false);
    $sales->assertSee('1,000.00');
    $sales->assertSee('100.00');
    $sales->assertDontSee('Loan drawn');
    $sales->assertDontSee('Offset transfer');
    $sales->assertDontSee('September repair');
    $sales->assertDontSee('PAYG instalment');

    $purchases = $this->actingAs($user)->get(route('transactions.index', [
        'entity_id' => $entity->id,
        'date_from' => '2025-07-01',
        'date_to' => '2025-09-30',
        'payment_status' => 'paid',
        'cash_dated' => 1,
        'gst_activity' => 1,
        'direction' => 'expense',
    ]));

    $purchases->assertOk();
    $purchases->assertSee('September repair');
    $purchases->assertSee('200.00');
    $purchases->assertSee('20.00');
    $purchases->assertDontSee('PAYG instalment');
    $purchases->assertDontSee('Offset transfer');
    $purchases->assertDontSee('August rent');
    $purchases->assertDontSee('Loan drawn');
});
