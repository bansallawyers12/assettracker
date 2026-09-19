<?php

use Tests\TestCase;

uses(TestCase::class);

it('defines vehicle-oriented, property-oriented, and shared tabs in asset show view', function () {
    $show = file_get_contents(resource_path('views/assets/show.blade.php'));

    // Vehicle-oriented tabs (for Car)
    expect($show)->toContain('id="tab_details"')
        ->and($show)->toContain('id="tab_registration"')
        ->and($show)->toContain('id="tab_insurance"')
        ->and($show)->toContain('id="tab_service"')
        ->and($show)->toContain('href="#tab_registration"')
        ->and($show)->toContain('href="#tab_insurance"')
        ->and($show)->toContain('href="#tab_service"');

    // Car quick actions
    expect($show)->toContain("@if (\$asset->asset_type === 'Car')")
        ->and($show)->toContain("@click=\"setTab('tab_service')\"")
        ->and($show)->toContain("@click=\"setTab('tab_registration')\"")
        ->and($show)->toContain("@click=\"setTab('tab_insurance')\"")
        ->and($show)->toContain("@click=\"setTab('tab_documents')\"");

    $invoicesTab = file_get_contents(resource_path('views/assets/partials/invoices-tab.blade.php'));

    // Property-oriented tabs (for leasable property)
    expect($show)->toContain('id="tab_tenants"')
        ->and($show)->toContain('id="tab_leases"')
        ->and($show)->toContain('id="tab_financials"')
        ->and($show)->toContain("include('assets.partials.invoices-tab'")
        ->and($invoicesTab)->toContain('id="tab_invoices"')
        ->and($show)->toContain('href="#tab_tenants"')
        ->and($show)->toContain('href="#tab_leases"')
        ->and($show)->toContain('href="#tab_financials"')
        ->and($show)->toContain('href="#tab_invoices"');

    // Shared tabs (for all assets)
    expect($show)->toContain('id="tab_transactions"')
        ->and($show)->toContain('id="tab_documents"')
        ->and($show)->toContain('id="tab_compliance"')
        ->and($show)->toContain('id="tab_notes"')
        ->and($show)->toContain('id="tab_reminders"')
        ->and($show)->toContain('id="tab_emails"')
        ->and($show)->toContain('href="#tab_transactions"')
        ->and($show)->toContain('href="#tab_documents"')
        ->and($show)->toContain('href="#tab_compliance"')
        ->and($show)->toContain('href="#tab_notes"')
        ->and($show)->toContain('href="#tab_reminders"')
        ->and($show)->toContain('href="#tab_emails"');
});

it('supports tab aliases and deep-link activation in asset show spa controller', function () {
    $spaJs = file_get_contents(resource_path('js/asset-show-page.js'));

    expect($spaJs)->toContain('resolveTabId(tabId)')
        ->and($spaJs)->toContain("tab_service_history: 'tab_service'")
        ->and($spaJs)->toContain("tab_tenant: 'tab_tenants'")
        ->and($spaJs)->toContain("tab_lease: 'tab_leases'")
        ->and($spaJs)->toContain("tab_financial: 'tab_financials'")
        ->and($spaJs)->toContain("tab_invoice: 'tab_invoices'")
        ->and($spaJs)->toContain("tab_transaction: 'tab_transactions'")
        ->and($spaJs)->toContain("tab_document: 'tab_documents'")
        ->and($spaJs)->toContain("tab_note: 'tab_notes'")
        ->and($spaJs)->toContain("tab_reminder: 'tab_reminders'")
        ->and($spaJs)->toContain("tab_email: 'tab_emails'")
        ->and($spaJs)->toContain("'linked-accounts': 'tab_details'")
        ->and($spaJs)->toContain("tab_insurance' && !tabs.includes('tab_insurance') && tabs.includes('tab_financials')")
        ->and($spaJs)->toContain("this.activeTab === 'tab_compliance'")
        ->and($spaJs)->toContain('compliance-tab-activated');
});

it('preserves tab hash on tenant, lease, and email pagination interactions', function () {
    $controller = file_get_contents(app_path('Http/Controllers/AssetController.php'));
    $assetShow = file_get_contents(resource_path('views/assets/show.blade.php'));
    $entityShow = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    // Controller store redirects
    expect($controller)->toContain(".'#tab_tenants')")
        ->and($controller)->toContain(".'#tab_leases')");

    // Email pagination fragments
    expect($assetShow)->toContain("fragment('tab_emails')")
        ->and($entityShow)->toContain("fragment('tab_emails')");
});
