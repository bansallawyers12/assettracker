<?php

use App\Models\BusinessEntity;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\FinancialReportService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows a tax rate on each line when mixed GST is selected', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Mixed Line Tax Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'mixed-line-tax@example.test',
        'phone_number' => '0400000000',
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.create', $entity))
        ->assertSuccessful()
        ->assertSee('Mixed rates — tax per line', false)
        ->assertSee('Tax rate', false)
        ->assertSee('GST Free', false)
        ->assertSee('Tax amount', false)
        ->assertDontSee('Invoice TOTAL GST', false);
});

it('posts mixed GST from each line so profit and loss is net of GST', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Mixed Line Posting Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'mixed-line-post@example.test',
        'phone_number' => '0400000000',
    ]);

    $response = $this->actingAs($user)->post(route('business-entities.invoices.store', $entity), [
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'due_date' => '2026-10-03',
        'customer_name' => 'Alex Tenant',
        'currency' => 'AUD',
        'gst_basis' => 'manual',
        'gst_percent' => 0,
        'save_and_post' => 1,
        'lines' => [
            [
                'description' => 'September rent',
                'quantity' => 1,
                'unit_price' => 1100,
                'account_code' => '4100',
                'tax_code' => 'gst',
            ],
            [
                'description' => 'Outgoings reimbursement',
                'quantity' => 1,
                'unit_price' => 500,
                'account_code' => '4200',
                'tax_code' => 'free',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $invoice = Invoice::query()->where('business_entity_id', $entity->id)->first();
    expect($invoice)->not->toBeNull();
    $response->assertRedirect(route('business-entities.invoices.show', [$entity, $invoice]));

    $invoice->load('lines');
    $rent = $invoice->lines->firstWhere('account_code', '4100');
    $outgoings = $invoice->lines->firstWhere('account_code', '4200');

    expect($invoice->gst_basis)->toBe('manual')
        ->and($invoice->is_posted)->toBeTrue()
        ->and((float) $invoice->total_amount)->toBe(1600.0)
        ->and((float) $invoice->gst_amount)->toBe(100.0)
        ->and((float) $invoice->subtotal)->toBe(1500.0)
        ->and((float) $rent->gst_rate)->toBe(0.1)
        ->and((float) $rent->line_total)->toBe(1100.0)
        ->and((float) $outgoings->gst_rate)->toBe(0.0)
        ->and((float) $outgoings->line_total)->toBe(500.0);

    $journalLines = JournalLine::query()
        ->whereHas('journalEntry', fn ($query) => $query
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id))
        ->with('chartOfAccount')
        ->get();

    $creditFor = function (string $code) use ($journalLines): float {
        return round((float) $journalLines
            ->filter(fn (JournalLine $line) => $line->chartOfAccount?->account_code === $code)
            ->sum('credit_amount'), 2);
    };

    expect($creditFor('4100'))->toBe(1000.0)
        ->and($creditFor('4200'))->toBe(500.0)
        ->and($creditFor('2100'))->toBe(100.0)
        ->and(round((float) $journalLines
            ->filter(fn (JournalLine $line) => $line->chartOfAccount?->account_code === '1130')
            ->sum('debit_amount'), 2))->toBe(1600.0);

    $profitLoss = app(FinancialReportService::class)->generateProfitLoss($entity->id, '2026-09-01', '2026-09-30');

    expect(round(-$profitLoss['income']['total'], 2))->toBe(1500.0)
        ->and(round($profitLoss['net_profit'], 2))->toBe(1500.0);
});

it('rejects mixed GST when a line has no tax rate', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Mixed Line Validation Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'mixed-line-valid@example.test',
        'phone_number' => '0400000000',
    ]);

    $response = $this->actingAs($user)->from(route('business-entities.invoices.create', $entity))
        ->post(route('business-entities.invoices.store', $entity), [
            'invoice_number' => 'INV'.$entity->id.'-202609001',
            'issue_date' => '2026-09-03',
            'customer_name' => 'Alex Tenant',
            'currency' => 'AUD',
            'gst_basis' => 'manual',
            'gst_percent' => 0,
            'lines' => [
                [
                    'description' => 'September rent',
                    'quantity' => 1,
                    'unit_price' => 1100,
                    'account_code' => '4100',
                ],
            ],
        ]);

    $response->assertRedirect(route('business-entities.invoices.create', $entity));
    $response->assertSessionHasErrors('lines.0.tax_code');
    expect(Invoice::query()->where('business_entity_id', $entity->id)->count())->toBe(0);
});

it('does not treat an old mixed draft as GST free until each line has a rate', function () {
    $this->seed(ChartOfAccountSeeder::class);
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Legacy Mixed Draft Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'legacy-mixed@example.test',
        'phone_number' => '0400000000',
    ]);

    $invoice = Invoice::create([
        'business_entity_id' => $entity->id,
        'invoice_number' => 'INV'.$entity->id.'-202609001',
        'issue_date' => '2026-09-03',
        'due_date' => '2026-10-03',
        'customer_name' => 'Alex Tenant',
        'currency' => 'AUD',
        'status' => 'draft',
        'is_posted' => false,
        'gst_basis' => 'manual',
        'subtotal' => 1500,
        'gst_amount' => 100,
        'total_amount' => 1600,
    ]);
    $invoice->lines()->create([
        'description' => 'September rent',
        'quantity' => 1,
        'unit_price' => 1100,
        'line_total' => 1100,
        'gst_rate' => 0,
        'account_code' => '4100',
    ]);
    $invoice->lines()->create([
        'description' => 'Outgoings',
        'quantity' => 1,
        'unit_price' => 500,
        'line_total' => 500,
        'gst_rate' => 0,
        'account_code' => '4200',
    ]);

    $this->actingAs($user)
        ->get(route('business-entities.invoices.edit', [$entity, $invoice]))
        ->assertSuccessful()
        ->assertSee('Choose GST 10% or GST Free on every line before you save.', false)
        ->assertSee('\u0022tax_code\u0022:\u0022\u0022', false);

    $unchanged = $this->actingAs($user)
        ->from(route('business-entities.invoices.edit', [$entity, $invoice]))
        ->put(route('business-entities.invoices.update', [$entity, $invoice]), [
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => '2026-09-03',
            'due_date' => '2026-10-03',
            'customer_name' => 'Alex Tenant',
            'currency' => 'AUD',
            'gst_basis' => 'manual',
            'gst_percent' => 0,
            'lines' => [
                [
                    'description' => 'September rent',
                    'quantity' => 1,
                    'unit_price' => 1100,
                    'account_code' => '4100',
                ],
                [
                    'description' => 'Outgoings',
                    'quantity' => 1,
                    'unit_price' => 500,
                    'account_code' => '4200',
                    'tax_code' => 'free',
                ],
            ],
        ]);

    $unchanged->assertSessionHasErrors('lines.0.tax_code');
    expect((float) $invoice->fresh()->gst_amount)->toBe(100.0);

    $saved = $this->actingAs($user)->put(route('business-entities.invoices.update', [$entity, $invoice]), [
        'invoice_number' => $invoice->invoice_number,
        'issue_date' => '2026-09-03',
        'due_date' => '2026-10-03',
        'customer_name' => 'Alex Tenant',
        'currency' => 'AUD',
        'gst_basis' => 'manual',
        'gst_percent' => 0,
        'lines' => [
            [
                'description' => 'September rent',
                'quantity' => 1,
                'unit_price' => 1100,
                'account_code' => '4100',
                'tax_code' => 'gst',
            ],
            [
                'description' => 'Outgoings',
                'quantity' => 1,
                'unit_price' => 500,
                'account_code' => '4200',
                'tax_code' => 'free',
            ],
        ],
    ]);

    $saved->assertSessionHasNoErrors();
    $invoice->refresh()->load('lines');

    expect((float) $invoice->gst_amount)->toBe(100.0)
        ->and((float) $invoice->subtotal)->toBe(1500.0)
        ->and((float) $invoice->lines->firstWhere('account_code', '4100')->gst_rate)->toBe(0.1)
        ->and((float) $invoice->lines->firstWhere('account_code', '4200')->gst_rate)->toBe(0.0);
});
