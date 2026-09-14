<?php

use Tests\TestCase;

uses(TestCase::class);

it('renders the vendors index as a workspace spa', function () {
    $index = file_get_contents(resource_path('views/vendors/index.blade.php'));
    $list = file_get_contents(resource_path('views/vendors/partials/list.blade.php'));
    $actions = file_get_contents(resource_path('views/vendors/partials/row-actions.blade.php'));
    $form = file_get_contents(resource_path('views/vendors/partials/form.blade.php'));
    $js = file_get_contents(resource_path('js/vendors-workspace.js'));
    $controller = file_get_contents(app_path('Http/Controllers/VendorController.php'));
    $workspace = file_get_contents(app_path('Http/Controllers/VendorsWorkspaceController.php'));
    $appJs = file_get_contents(resource_path('js/app.js'));

    expect($index)->toContain('vendors-workspace')
        ->and($index)->toContain('data-workspace-url')
        ->and($index)->toContain('data-create-form-url')
        ->and($index)->toContain('data-vendor-action="create"')
        ->and($index)->toContain('data-vendors-list')
        ->and($index)->toContain('data-vendors-unlinked')
        ->and($index)->not->toContain('@if(session(\'error\'))')
        ->and($list)->toContain('vendors.partials.row-actions')
        ->and($actions)->toContain('data-vendor-action="edit"')
        ->and($actions)->toContain('data-vendor-action="delete"')
        ->and($actions)->toContain('lucide-pencil')
        ->and($actions)->toContain('lucide-trash-2')
        ->and($form)->toContain('vendors-ws-form')
        ->and($form)->toContain('bank-ws-form')
        ->and($js)->toContain('initVendorsWorkspace')
        ->and($js)->toContain('openWorkspacePanel')
        ->and($js)->toContain('submitWorkspaceForm')
        ->and($appJs)->toContain('vendors-workspace.js')
        ->and($controller)->toContain('expectsJson()')
        ->and($controller)->toContain('workspaceJsonResponse')
        ->and($controller)->toContain("['panel' => 'create']")
        ->and($controller)->toContain("'panel' => 'edit'")
        ->and($workspace)->toContain('function createForm')
        ->and($workspace)->toContain('function editForm')
        ->and($workspace)->toContain('list_html')
        ->and($workspace)->toContain('unlinked_html');
});

it('keeps vendor named routes while funneling create and edit into the spa', function () {
    expect(route('vendors.index'))->toContain('/vendors')
        ->and(route('vendors.create'))->toContain('/vendors/create')
        ->and(route('vendors.edit', 1))->toContain('/vendors/1/edit')
        ->and(route('vendors.workspace'))->toContain('/vendors/workspace')
        ->and(route('vendors.form.create'))->toContain('/vendors/form/create')
        ->and(route('vendors.form.edit', 1))->toContain('/vendors/1/form/edit')
        ->and(route('vendors.store'))->toContain('/vendors')
        ->and(route('vendors.destroy', 1))->toContain('/vendors/1');
});
