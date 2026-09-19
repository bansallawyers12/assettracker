<?php

use Tests\TestCase;

uses(TestCase::class);

it('includes entity bank accounts tab and sub-workspace tabs', function () {
    $entityShow = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    // 1. Entity Bank accounts tab (with #tab_bank_import alias)
    expect($entityShow)->toContain('id="tab_bank_accounts"')
        ->and($entityShow)->toContain("tab_bank_import: 'tab_bank_accounts'");

    // 2. Entity Transactions / Invoices / Documents / Compliance / Notes / Contact lists / Emails
    expect($entityShow)->toContain('id="tab_transactions"')
        ->and($entityShow)->toContain('id="tab_invoices"')
        ->and($entityShow)->toContain('id="tab_documents"')
        ->and($entityShow)->toContain('id="tab_compliance"')
        ->and($entityShow)->toContain('id="tab_notes"')
        ->and($entityShow)->toContain('id="tab_contact_lists"')
        ->and($entityShow)->toContain('id="tab_emails"');

    // 4. Entity create invoice
    expect($entityShow)->toContain("route('business-entities.invoices.create', \$businessEntity->id)");
});

it('includes asset tabs, lease/tenant create, and direct financials detail report link', function () {
    $assetShow = file_get_contents(resource_path('views/assets/show.blade.php'));
    $invoicesTab = file_get_contents(resource_path('views/assets/partials/invoices-tab.blade.php'));

    // 5. Asset Tenants / Leases / Financials / Invoices / Documents / Compliance
    expect($assetShow)->toContain('id="tab_tenants"')
        ->and($assetShow)->toContain('id="tab_leases"')
        ->and($assetShow)->toContain('id="tab_financials"')
        ->and($assetShow)->toContain("@include('assets.partials.invoices-tab'")
        ->and($invoicesTab)->toContain('id="tab_invoices"')
        ->and($assetShow)->toContain('id="tab_documents"')
        ->and($assetShow)->toContain('id="tab_compliance"');

    // 6. Asset lease create / tenant create
    expect($assetShow)->toContain('data-lease-create')
        ->and($assetShow)->toContain('data-tenant-create');

    // 7. Asset financials detail report
    expect($assetShow)->toContain("route('assets.financials', [\$businessEntity, \$asset])")
        ->and($assetShow)->toContain('Property Financials Report');
});

it('includes bank statement and transaction action handlers and fallback links', function () {
    $actions = file_get_contents(resource_path('views/bank-accounts/partials/account-link-actions.blade.php'));

    // 3, 8 & 9. Statements and Transactions actions with modal trigger and fallback links
    expect($actions)->toContain('data-bank-action="statements"')
        ->and($actions)->toContain('href="{{ $statementsUrl }}"')
        ->and($actions)->toContain('data-bank-action="transactions"')
        ->and($actions)->toContain('href="{{ $transactionsUrl }}"');
});

it('includes manual journal create link on journal entries register', function () {
    $journalsIndex = file_get_contents(resource_path('views/financial-reports/journal-entries-index.blade.php'));

    // 10. Manual journal create
    expect($journalsIndex)->toContain('href="{{ $createUrl }}"')
        ->and($journalsIndex)->toContain('New journal');
});

it('includes email reply action on email message view', function () {
    $emailShow = file_get_contents(resource_path('views/emails/show.blade.php'));

    // 11. Email reply
    expect($emailShow)->toContain("route('emails.reply', \$message->id)");
});

it('includes record invoice payment and unpost actions on invoice view', function () {
    $invoiceShow = file_get_contents(resource_path('views/invoices/show.blade.php'));

    // 12. Record invoice payment / unpost
    expect($invoiceShow)->toContain("route('business-entities.invoices.record-payment', [\$businessEntity, \$invoice])")
        ->and($invoiceShow)->toContain("route('business-entities.invoices.unpost', [\$businessEntity, \$invoice])");
});

it('includes person bank account create form action in person workspace', function () {
    $personShow = file_get_contents(resource_path('views/persons/show.blade.php'));

    // 13. Person bank account form
    expect($personShow)->toContain('data-open-add-bank-account')
        ->and($personShow)->toContain("route('persons.bank-accounts.form.create', \$person)");
});

it('includes vendor edit action in vendor list', function () {
    $vendorRow = file_get_contents(resource_path('views/vendors/partials/row-actions.blade.php'));

    // 14. Vendor edit (workspace)
    expect($vendorRow)->toContain('data-vendor-action="edit"');
});
