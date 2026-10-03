<?php

use Tests\TestCase;

uses(TestCase::class);

it('opens portfolio filters from the header and does not repeat them in a second bar', function () {
    $portfolio = file_get_contents(resource_path('views/property-reports/portfolio.blade.php'));

    expect($portfolio)->toContain('x-data="{ filtersOpen: false }"')
        ->and($portfolio)->toContain('x-show="filtersOpen"')
        ->and($portfolio)->toContain('aria-controls="portfolio-filters-panel"')
        ->and($portfolio)->toContain('id="portfolio-filters-panel"')
        ->and($portfolio)->toContain('<x-lucide-filter')
        ->and($portfolio)->toContain('Filters')
        ->and($portfolio)->not->toContain('Report settings');

    $filtersButton = strpos($portfolio, 'aria-controls="portfolio-filters-panel"');
    $headerEnd = strpos($portfolio, 'id="portfolio-filters-panel"');

    expect($filtersButton)->toBeInt()
        ->and($headerEnd)->toBeInt()
        ->and($filtersButton)->toBeLessThan($headerEnd);
});
