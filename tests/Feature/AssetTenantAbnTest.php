<?php

use Tests\TestCase;

uses(TestCase::class);

it('includes an optional ABN field on the tenant form after address', function () {
    $tenantForm = file_get_contents(resource_path('views/assets/partials/tenants/form.blade.php'));
    $createPage = file_get_contents(resource_path('views/assets/tenants/create.blade.php'));
    $show = file_get_contents(resource_path('views/assets/show.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/AssetController.php'));
    $model = file_get_contents(app_path('Models/Tenant.php'));

    expect($tenantForm)->toContain('name="abn"')
        ->and($tenantForm)->toMatch('/Address[\s\S]*name="abn"[\s\S]*Lease Start Date/')
        ->and($createPage)->toContain('name="abn"')
        ->and($show)->toContain('>ABN</dt>')
        ->and($controller)->toContain("'abn' =>")
        ->and($model)->toContain("'abn'");
});
