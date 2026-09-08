<?php

use App\Models\EntityPerson;
use Tests\TestCase;

uses(TestCase::class);

it('keeps bank import discoverable on bank accounts without a separate tab', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));

    expect($show)->not->toContain('id="tab_bank_import"')
        ->and($show)->not->toContain('href="#tab_bank_import"')
        ->and($show)->toContain("tab_bank_import: 'tab_bank_accounts'")
        ->and($show)->toContain('data-bank-import-hint')
        ->and($show)->toContain('data-bank-import-static-hint')
        ->and($show)->toContain("aliasedFrom === 'tab_bank_import'")
        ->and($show)->toContain('Looking for Bank Import?');
});

it('gates company profile edit triggers behind the update policy', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $sidebar = file_get_contents(resource_path('views/business-entities/partials/entity-details-sidebar.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/EntityShowWorkspaceController.php'));

    expect($show)->toContain("@can('update', \$businessEntity)")
        ->and($show)->toContain('data-entity-profile-edit')
        ->and($show)->toContain('Ask a staff user to reopen this entity from the company profile.')
        ->and($sidebar)->toContain("@can('update', \$businessEntity)")
        ->and($sidebar)->toContain('data-entity-profile-edit')
        ->and($controller)->toContain("authorize('update', \$businessEntity)")
        ->and($controller)->toContain('never during show-page render');
});

it('keeps trust appointor on the company profile instead of officer roles', function () {
    $form = file_get_contents(resource_path('views/business-entities/partials/persons/form.blade.php'));
    $list = file_get_contents(resource_path('views/business-entities/partials/persons/list.blade.php'));
    $workspace = file_get_contents(resource_path('views/business-entities/partials/persons-workspace.blade.php'));
    $personsWorkspace = file_get_contents(app_path('Http/Controllers/PersonsWorkspaceController.php'));
    $entityPerson = file_get_contents(app_path('Http/Controllers/EntityPersonController.php'));
    $sidebar = file_get_contents(resource_path('views/business-entities/partials/entity-details-sidebar.blade.php'));

    expect(EntityPerson::ROLES)->not->toContain('Appointor')
        ->and($form)->toContain('data-appointor-profile-hint')
        ->and($form)->toContain('data-entity-profile-edit')
        ->and($form)->not->toContain("route('business-entities.edit'")
        ->and($list)->toContain('data-legacy-appointor-card')
        ->and($workspace)->toContain('data-trust-appointor-persons-notice')
        ->and($personsWorkspace)->toContain("role === 'Appointor'")
        ->and($personsWorkspace)->toContain('Appointor is managed on the trust company profile')
        ->and($entityPerson)->toContain('Appointor is managed on the trust company profile')
        ->and($entityPerson)->toContain("'role' => 'required|in:Director,Secretary,Shareholder,Trustee,Beneficiary,Settlor,Owner'")
        ->and($sidebar)->toContain('data-trust-appointor-sidebar');
});
