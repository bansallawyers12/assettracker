<?php

use Tests\TestCase;

uses(TestCase::class);

it('explains closed-entity mutation blocks and hides create actions', function () {
    $show = file_get_contents(resource_path('views/business-entities/show.blade.php'));
    $assets = file_get_contents(resource_path('views/business-entities/partials/assets-workspace.blade.php'));
    $assetsList = file_get_contents(resource_path('views/business-entities/partials/assets/list.blade.php'));
    $persons = file_get_contents(resource_path('views/business-entities/partials/persons-workspace.blade.php'));
    $profile = file_get_contents(resource_path('views/business-entities/partials/profile/form.blade.php'));
    $trait = file_get_contents(app_path('Http/Controllers/Concerns/EnsuresOperationalBusinessEntity.php'));
    $workspaceJs = file_get_contents(resource_path('js/entity-show-workspace.js'));

    expect($show)->toContain('data-closed-entity-banner')
        ->and($show)->toContain('data-entity-closed')
        ->and($show)->toContain('Reopen via company profile')
        ->and($show)->toContain('@unless ($businessEntity->isClosed())')
        ->and($assets)->toContain('data-closed-assets-notice')
        ->and($assetsList)->toContain('isClosed()')
        ->and($persons)->toContain('data-closed-persons-notice')
        ->and($profile)->toContain('data-closed-profile-notice')
        ->and($profile)->toContain('Save & reopen')
        ->and($trait)->toContain('abortOperationalRestriction')
        ->and($trait)->toContain('expectsJson()')
        ->and($trait)->toContain('This entity is closed')
        ->and($workspaceJs)->toContain('status === 403')
        ->and($workspaceJs)->toContain('payload?.message');
});
