<?php

use App\Services\CommitmentReportService;
use Tests\TestCase;

uses(TestCase::class);

it('renders extend 30 days button for asic due dates on dashboard', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain('Extend (30 days)')
        ->and($html)->toContain("route('entity-persons.extend-due-date'");
});

it('falls back to entity create for add asset when no entities exist', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain("route('business-entities.assets.create', \$businessEntities->first()->id) : route('business-entities.create')");
});

it('differentiates reminder validation errors from transaction errors', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain('$isReminderError =')
        ->and($html)->toContain('$isTransactionError =')
        ->and($html)->toContain('$dashboardReminderErrorToast')
        ->and($html)->toContain("title: 'Could not save reminder'");
});

it('preserves reminder form selections on validation redirect', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain("@selected(old('business_entity_id') == \$entity->id)")
        ->and($html)->toContain("@selected(old('asset_id') == \$asset->id)")
        ->and($html)->toContain("@selected(old('repeat_type', 'none') === 'none')")
        ->and($html)->toContain('syncReminderAssetSelect');
});

it('uses uniquePersons count for the persons stat card', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain('{{ $uniquePersons->count() }}')
        ->and($html)->toContain('Persons</div>');
});

it('returns zero active commitments for an empty entity id array', function () {
    $service = app(CommitmentReportService::class);
    $result = $service->dashboardSummary([]);

    expect($result)->toBe([
        'active_count' => 0,
        'total_balance_due' => 0.0,
    ]);
});

it('renders interactive links on all dashboard stat cards', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain("route('business-entities.index')")
        ->and($html)->toContain("route('assets.index')")
        ->and($html)->toContain("route('persons.index')")
        ->and($html)->toContain("route('bills-tasks.index', ['tab' => 'reminders'])")
        ->and($html)->toContain("route('bills-tasks.index', ['tab' => 'due'])")
        ->and($html)->toContain("route('commitments.index')");
});

it('provides primary creation actions in hero banner and quick actions sidebar', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    // Hero banner actions
    expect($html)->toContain('New Entity')
        ->and($html)->toContain('New Asset')
        ->and($html)->toContain('New Person')
        ->and($html)->toContain('Add Transaction')
        // Quick Actions sidebar
        ->and($html)->toContain('Add Entity')
        ->and($html)->toContain('Add Asset')
        ->and($html)->toContain('Add Person');
});

it('provides empty state creation links and accounting commitments link', function () {
    $html = file_get_contents(resource_path('views/dashboard.blade.php'));

    expect($html)->toContain('No entities yet.')
        ->and($html)->toContain('No assets yet.')
        ->and($html)->toContain('No persons yet.')
        ->and($html)->toContain("route('chart-of-accounts.index')")
        ->and($html)->toContain("route('bank-accounts.index')")
        ->and($html)->toContain("route('transactions.index')")
        ->and($html)->toContain("route('invoices.index')")
        ->and($html)->toContain("route('vendors.index')")
        ->and($html)->toContain("route('commitments.index')");
});
