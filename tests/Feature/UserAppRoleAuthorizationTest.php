<?php

use App\Enums\AppRole;
use App\Models\BusinessEntity;
use App\Models\User;
use App\Policies\BusinessEntityPolicy;
use Tests\TestCase;

uses(TestCase::class);

it('allows staff to mutate the firm-shared portfolio while viewers are read-only', function () {
    $staff = new User(['app_role' => AppRole::Staff, 'email' => 'staff@example.com']);
    $viewer = new User(['app_role' => AppRole::Viewer, 'email' => 'viewer@example.com']);
    $entity = new BusinessEntity(['legal_name' => 'Test Pty Ltd']);

    $policy = new BusinessEntityPolicy;

    expect($staff->canMutatePortfolio())->toBeTrue()
        ->and($viewer->canMutatePortfolio())->toBeFalse()
        ->and($policy->view($staff, $entity))->toBeTrue()
        ->and($policy->view($viewer, $entity))->toBeTrue()
        ->and($policy->update($staff, $entity))->toBeTrue()
        ->and($policy->update($viewer, $entity))->toBeFalse()
        ->and($policy->create($viewer))->toBeFalse()
        ->and($policy->delete($viewer, $entity))->toBeFalse();
});

it('exposes app role assignment on admin user create form and store validation', function () {
    $form = file_get_contents(resource_path('views/admin/users/partials/create-form.blade.php'));
    $controller = file_get_contents(app_path('Http/Controllers/Admin/UserManagementController.php'));
    $user = file_get_contents(app_path('Models/User.php'));
    $migration = collect(glob(database_path('migrations/*add_app_role_to_users_table.php')))->first();

    expect($form)->toContain('name="app_role"')
        ->and($form)->toContain('AppRole::assignable')
        ->and($form)->toContain('no public registration')
        ->and($controller)->toContain("'app_role'")
        ->and($user)->toContain('canMutatePortfolio')
        ->and($user)->toContain('appRole(')
        ->and($migration)->not->toBeNull()
        ->and(file_get_contents($migration))->toContain('app_role');
});
