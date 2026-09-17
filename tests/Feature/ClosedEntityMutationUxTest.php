<?php

use Tests\TestCase;

uses(TestCase::class);

it('explains closed-entity mutation blocks and hides create actions', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $assets = file_get_contents(resource_path('views/business-entities/partials/assets-workspace.blade.php'));
    $assetsList = file_get_contents(resource_path('views/business-entities/partials/assets/list.blade.php'));
    $persons = file_get_contents(resource_path('views/business-entities/partials/persons-workspace.blade.php'));
    $profile = file_get_contents(resource_path('views/business-entities/partials/profile/form.blade.php'));
    $contacts = file_get_contents(resource_path('views/business-entities/partials/contact-lists-workspace.blade.php'));
    $contactsList = file_get_contents(resource_path('views/business-entities/partials/contact-lists/list.blade.php'));
    $notes = file_get_contents(resource_path('views/business-entities/partials/notes-workspace.blade.php'));
    $notesList = file_get_contents(resource_path('views/business-entities/partials/notes/list.blade.php'));
    $documents = file_get_contents(resource_path('views/business-entities/partials/documents-workspace.blade.php'));
    $compliance = file_get_contents(resource_path('views/business-entities/partials/compliance-workspace.blade.php'));
    $trait = file_get_contents(app_path('Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'));
    $workspaceJs = file_get_contents(resource_path('js/entity-show-workspace.js'));
    $documentsJs = file_get_contents(resource_path('js/documents-workspace.js'));
    $contactController = file_get_contents(app_path('Http/Controllers/ContactListController.php'));
    $contactWorkspace = file_get_contents(app_path('Http/Controllers/ContactListsWorkspaceController.php'));
    $documentWorkspace = file_get_contents(app_path('Http/Controllers/DocumentWorkspaceController.php'));
    $complianceController = file_get_contents(app_path('Http/Controllers/ComplianceWorkspaceController.php'));

    expect($show)->toContain('data-closed-entity-banner')
        ->and($show)->toContain('data-entity-closed')
        ->and($show)->toContain('Reopen via company profile')
        ->and($show)->toContain('@unless ($businessEntity->isClosed())')
        ->and($show)->toContain('data-closed-invoices-notice')
        ->and($show)->toContain('data-closed-bank-accounts-notice')
        ->and($show)->toContain('isClosed() || $businessEntity->isTenancyContactOnly()')
        ->and($assets)->toContain('data-closed-assets-notice')
        ->and($assetsList)->toContain('isClosed()')
        ->and($persons)->toContain('data-closed-persons-notice')
        ->and($profile)->toContain('data-closed-profile-notice')
        ->and($profile)->toContain('Save & reopen')
        ->and($contacts)->toContain('data-closed-contacts-notice')
        ->and($contacts)->toContain('@unless ($isClosed)')
        ->and($contactsList)->toContain('canMutateContacts')
        ->and($notes)->toContain('data-closed-notes-notice')
        ->and($notesList)->toContain('canMutateNotes')
        ->and($documents)->toContain('data-closed-documents-notice')
        ->and($documents)->toContain('@unless ($isClosed)')
        ->and($compliance)->toContain('data-closed-compliance-notice')
        ->and($trait)->toContain('abortOperationalRestriction')
        ->and($trait)->toContain('expectsJson()')
        ->and($trait)->toContain('This entity is closed')
        ->and($trait)->toContain('ensureNotClosed($businessEntity)')
        ->and($workspaceJs)->toContain('status === 403')
        ->and($workspaceJs)->toContain('payload?.message')
        ->and($documentsJs)->toContain('data-entity-closed')
        ->and($contactController)->toContain('ensureNotClosed($businessEntity)')
        ->and($contactWorkspace)->toContain('ensureNotClosed($businessEntity)')
        ->and($documentWorkspace)->toContain('authorizeOpenMutation')
        ->and($complianceController)->toContain("\$workspace['locked'] = true");
});

it('blocks accounting mutations for closed entities before tenancy checks', function () {
    $trait = file_get_contents(app_path('Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'));
    $bankWorkspace = file_get_contents(app_path('Http/Controllers/BankAccountsWorkspaceController.php'));

    expect($trait)->toContain('Closed entities are blocked first')
        ->and($bankWorkspace)->toContain('ensureOperationalForAccounting($businessEntity)')
        ->and($bankWorkspace)->not->toContain('ensureNotClosed($businessEntity);');
});
