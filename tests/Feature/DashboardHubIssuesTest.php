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
