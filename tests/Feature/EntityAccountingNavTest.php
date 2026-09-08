<?php

use Tests\TestCase;

uses(TestCase::class);

it('promotes profit and loss and balance sheet in the entity accounting nav', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->toContain('aria-label="Accounting and finance"');

    $start = strpos($show, 'aria-label="Accounting and finance"');
    $end = strpos($show, '</nav>', $start);
    expect($start)->not->toBeFalse()
        ->and($end)->not->toBeFalse();

    $nav = substr($show, $start, $end - $start);

    expect($nav)->toContain('@unless ($businessEntity->isTenancyContactOnly())')
        ->and($nav)->toContain('@else')
        ->and($nav)->toContain('data-tenancy-accounting-unavailable')
        ->and($nav)->toContain("route('business-entities.financial-reports.profit-loss'")
        ->and($nav)->toContain("route('business-entities.financial-reports.balance-sheet'")
        ->and($nav)->toContain("route('business-entities.financial-reports.journal-entries.index'")
        ->and($nav)->toContain('Profit &amp; Loss')
        ->and($nav)->toContain('Balance Sheet')
        ->and($nav)->toContain('Manual journals')
        ->and($nav)->toContain('entity-external-nav')
        ->and($nav)->toContain('#tab_bank_accounts')
        ->and($nav)->not->toContain('#tab_bank_import')
        ->and($nav)->not->toContain('Bank Import')
        ->and($nav)->not->toContain('tab-link entity-tab-link entity-external-nav')
        ->and($nav)->not->toContain("route('business-entities.tracking-categories.index'")
        ->and($nav)->not->toContain('Tracking Categories');
});

it('explains missing accounting tabs for tenancy property-manager contacts', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $persons = file_get_contents(resource_path('views/business-entities/partials/persons-workspace.blade.php'));
    $personsList = file_get_contents(resource_path('views/business-entities/partials/persons/list.blade.php'));
    $sidebar = file_get_contents(resource_path('views/business-entities/partials/entity-details-sidebar.blade.php'));
    $createFields = file_get_contents(resource_path('views/business-entities/partials/create-form-fields.blade.php'));

    expect($show)->toContain('data-tenancy-contact-banner')
        ->and($show)->toContain('Contact only')
        ->and($show)->toContain('Profit & Loss, Balance Sheet, and Manual journals')
        ->and($show)->toContain('Company officer / trustee roles on the Persons tab')
        ->and($show)->toContain('data-tenancy-accounting-unavailable')
        ->and($show)->toContain('data-tenancy-financial-reports-notice')
        ->and($persons)->toContain('data-tenancy-persons-notice')
        ->and($persons)->toContain('Officer roles are not used here')
        ->and($personsList)->toContain('isTenancyContactOnly()')
        ->and($sidebar)->toContain('Contact only')
        ->and($createFields)->toContain('company officer roles stay unavailable');
});
