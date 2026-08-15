<?php

uses(Tests\TestCase::class);

it('promotes profit and loss and balance sheet in the entity accounting nav', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->toContain("aria-label=\"Accounting and finance\"")
        ->and($show)->toContain("route('business-entities.financial-reports.profit-loss'")
        ->and($show)->toContain("route('business-entities.financial-reports.balance-sheet'")
        ->and($show)->toContain('Profit &amp; Loss')
        ->and($show)->toContain('Balance Sheet')
        ->and($show)->not->toContain("route('business-entities.tracking-categories.index'");
});
