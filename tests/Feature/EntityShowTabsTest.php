<?php

use Tests\TestCase;

uses(TestCase::class);

it('provides direct toolbar links and containers for all 11 entity show tabs', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    $expectedTabs = [
        'tab_assets',
        'tab_persons',
        'tab_documents',
        'tab_compliance',
        'tab_notes',
        'tab_contact_lists',
        'tab_compose_email',
        'tab_emails',
        'tab_bank_accounts',
        'tab_transactions',
        'tab_invoices',
        'tab_financial_reports',
    ];

    foreach ($expectedTabs as $tabId) {
        // Tab content container exists
        expect($show)->toContain('id="'.$tabId.'"');
        // Tab navigation link exists
        expect($show)->toContain('href="#'.$tabId.'"');
    }

    // Both primary nav and accounting nav live within #entity-tabs toolbar
    expect($show)->toContain('id="entity-tabs"')
        ->and($show)->toContain('aria-label="Entity sections"')
        ->and($show)->toContain('aria-label="Accounting and finance"');
});

it('supports tab aliases and smooth in-tab jumps on entity show', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    // Aliases
    expect($show)->toContain("tab_bank_import: 'tab_bank_accounts'")
        ->and($show)->toContain("tab_reports: 'tab_financial_reports'");

    // In-tab jump link from financial reports to compliance uses smooth switcher
    expect($show)->toContain('href="#tab_compliance"')
        ->and($show)->toContain('js-entity-tab-jump inline-flex items-center px-3 py-2 bg-violet-100');

    // Tenancy contact graceful notice exists in financial reports tab
    expect($show)->toContain('data-tenancy-financial-reports-notice')
        ->and($show)->toContain('Financial reports are not available');
});
