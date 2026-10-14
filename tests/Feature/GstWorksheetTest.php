<?php

use App\Models\BusinessEntity;
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
    gstWorksheetPaid($entity, 'payg_payment', 80, 0, '2025-08-20', '2025-08-20');
    gstWorksheetPaid($entity, 'payg_payment', 20, 0, '2026-04-15', '2026-04-15');
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

    foreach (['sales_ex_gst', 'purchases_ex_gst', 'gst_collected', 'gst_credits', 'net_gst', 'payg_paid', 'total_bas'] as $row) {
        $quarterSum = round(
            $periods['q1'][$row] + $periods['q2'][$row] + $periods['q3'][$row] + $periods['q4'][$row],
            2
        );

        expect($quarterSum)->toBe($periods['fy'][$row]);
    }

    expect($periods['q1']['net_gst'])->toBe(110.0)
        ->and($periods['q1']['payg_paid'])->toBe(80.0)
        ->and($periods['q1']['total_bas'])->toBe(190.0);
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
    $default->assertSee('direction=income', false);
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
        ->and($csvBody)->toContain('Q1 Jul–Sep')
        ->and($csvBody)->toContain('Sales (ex GST)')
        ->and($csvBody)->toContain('GST recorded on transactions')
        ->and($csvBody)->toContain('Not GST registered')
        ->and($csvBody)->toContain('—');

    $summary = $this->actingAs($user)->get(route('financial-reports.entity-summary', [
        'scope' => 'selected',
        'entity_ids' => [$quarterly->id],
        'fy_from' => '2025-07-01',
        'fy_to' => '2025-07-01',
    ]));
    $summary->assertOk();
    $summary->assertSee('data-entity-summary-gst-link', false);
    $summary->assertSee('/financial-reports/gst', false);

    $hub = file_get_contents(resource_path('views/financial-reports/index.blade.php'));
    $profitLoss = strpos($hub, "'route' => 'financial-reports.profit-loss'");
    $gst = strpos($hub, "'route' => 'financial-reports.gst'");
    expect($profitLoss)->not->toBeFalse()
        ->and($gst)->toBeGreaterThan($profitLoss);
});
