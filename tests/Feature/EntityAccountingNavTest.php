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
        ->and($nav)->toContain("route('business-entities.financial-reports.profit-loss'")
        ->and($nav)->toContain("route('business-entities.financial-reports.balance-sheet'")
        ->and($nav)->toContain('Profit &amp; Loss')
        ->and($nav)->toContain('Balance Sheet')
        ->and($nav)->toContain('entity-external-nav')
        ->and($nav)->toContain('#tab_bank_accounts')
        ->and($nav)->not->toContain('#tab_bank_import')
        ->and($nav)->not->toContain('Bank Import')
        ->and($nav)->not->toContain('tab-link entity-tab-link entity-external-nav')
        ->and($nav)->not->toContain("route('business-entities.tracking-categories.index'")
        ->and($nav)->not->toContain('Tracking Categories');
});
