<?php

use App\Enums\AppRole;
use App\Models\BusinessEntity;
use App\Models\User;
use App\Policies\BusinessEntityPolicy;
use App\Policies\EmailTemplatePolicy;
use App\Policies\ReminderPolicy;
use Tests\TestCase;

uses(TestCase::class);

it('allows staff to mutate the firm-shared portfolio while viewers are read-only', function () {
    $staff = new User(['app_role' => AppRole::Staff, 'email' => 'staff@example.com']);
    $viewer = new User(['app_role' => AppRole::Viewer, 'email' => 'viewer@example.com']);
    $entity = new BusinessEntity(['legal_name' => 'Test Pty Ltd']);

    $policy = new BusinessEntityPolicy;
    $reminderPolicy = new ReminderPolicy;
    $emailTemplatePolicy = new EmailTemplatePolicy;

    expect($staff->canMutatePortfolio())->toBeTrue()
        ->and($viewer->canMutatePortfolio())->toBeFalse()
        ->and($policy->view($staff, $entity))->toBeTrue()
        ->and($policy->view($viewer, $entity))->toBeTrue()
        ->and($policy->update($staff, $entity))->toBeTrue()
        ->and($policy->update($viewer, $entity))->toBeFalse()
        ->and($policy->create($viewer))->toBeFalse()
        ->and($policy->delete($viewer, $entity))->toBeFalse()
        ->and($reminderPolicy->create($viewer))->toBeFalse()
        ->and($reminderPolicy->create($staff))->toBeTrue()
        ->and($emailTemplatePolicy->create($viewer))->toBeFalse()
        ->and($emailTemplatePolicy->create($staff))->toBeTrue();
});

it('gates portfolio mutations behind BusinessEntity create ability not viewAny', function () {
    $vendor = file_get_contents(app_path('Http/Controllers/VendorController.php'));
    $coa = file_get_contents(app_path('Http/Controllers/ChartOfAccountController.php'));
    $bankPanel = file_get_contents(app_path('Http/Controllers/BankAccountPanelController.php'));
    $entity = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($vendor)->toContain("authorize('create', BusinessEntity::class)")
        ->and($vendor)->toContain('function store(')
        ->and(substr_count($vendor, "authorize('viewAny', BusinessEntity::class)"))->toBe(1)
        ->and($coa)->toContain("authorize('create', BusinessEntity::class)")
        ->and($coa)->toContain('function store(')
        ->and($bankPanel)->toContain("authorize('create', BusinessEntity::class)")
        ->and($bankPanel)->toContain('portfolioCreateForm')
        ->and($entity)->toContain('storePortfolioBankAccount')
        ->and($entity)->toMatch("/function storePortfolioBankAccount[\s\S]*?authorize\('create', BusinessEntity::class\)/");
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

it('enforces primary administrator protection and password management constraints', function () {
    $primaryAdminEmail = 'admin@example.com';
    config(['admin.email' => $primaryAdminEmail]);

    $primaryAdmin = new User(['email' => $primaryAdminEmail]);
    $normalUser = new User(['email' => 'user@example.com']);

    expect($primaryAdmin->isPrimaryAdministrator())->toBeTrue()
        ->and($normalUser->isPrimaryAdministrator())->toBeFalse();

    $routes = file_get_contents(base_path('routes/web.php'));
    expect($routes)->toContain("'super.admin'")
        ->and($routes)->toContain("'password.confirm'");

    $wsController = file_get_contents(app_path('Http/Controllers/Admin/AdminUsersWorkspaceController.php'));
    expect($wsController)->toContain('$user->isPrimaryAdministrator()')
        ->and($wsController)->toContain('The primary administrator password cannot be reset here');
});
