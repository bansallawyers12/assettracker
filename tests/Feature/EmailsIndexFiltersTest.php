<?php

use Tests\TestCase;

uses(TestCase::class);

it('collapses emails index filters behind a Filters toggle', function () {
    $index = file_get_contents(resource_path('views/emails/index.blade.php'));

    expect($index)->toContain('filtersOpen')
        ->and($index)->toContain('email-filters-panel')
        ->and($index)->toContain('lucide-filter')
        ->and($index)->toContain('Filters')
        ->and($index)->toContain('Search emails')
        ->and($index)->toContain('date_from')
        ->and($index)->toContain('date_to')
        ->and($index)->toContain('label_id')
        ->and($index)->toContain('Apply')
        ->and($index)->toContain('Mailbox')
        ->and($index)->toContain('emailViewer');
});
