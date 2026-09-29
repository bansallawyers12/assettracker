<?php

use Tests\TestCase;

uses(TestCase::class);

it('hides portfolio report settings behind a filter toggle by default', function () {
    $portfolio = file_get_contents(resource_path('views/property-reports/portfolio.blade.php'));

    expect($portfolio)->toContain('x-data="{ filtersOpen: false }"')
        ->and($portfolio)->toContain('x-show="filtersOpen"')
        ->and($portfolio)->toContain('aria-controls="portfolio-filters-panel"')
        ->and($portfolio)->toContain('id="portfolio-filters-panel"')
        ->and($portfolio)->toContain('<x-lucide-filter')
        ->and($portfolio)->toContain('Report settings');
});
