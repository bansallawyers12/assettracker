<?php

use App\Models\BusinessEntity;
use App\Services\BankAccountAssetLinkService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('allows empty rent collection asset ids when the entity has no leasable assets', function () {
    $service = new class extends BankAccountAssetLinkService
    {
        public function leasableAssetsForEntity(BusinessEntity $entity): Collection
        {
            return collect();
        }
    };

    $entity = new BusinessEntity;
    $entity->id = 9;

    expect($service->requireRentCollectionAssetsWhenLeasable($entity, []))->toBe([]);
});

it('rejects empty rent collection asset ids when leasable assets exist', function () {
    $service = new class extends BankAccountAssetLinkService
    {
        public function leasableAssetsForEntity(BusinessEntity $entity): Collection
        {
            return collect([(object) ['id' => 5, 'name' => 'Unit 1']]);
        }
    };

    $entity = new BusinessEntity;
    $entity->id = 9;

    expect(fn () => $service->requireRentCollectionAssetsWhenLeasable($entity, []))
        ->toThrow(ValidationException::class);
});

it('requires rent asset selection before creating a rent receiving purpose link', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));
    $service = file_get_contents(app_path('Services/BankAccountAssetLinkService.php'));
    $fields = file_get_contents(resource_path('views/bank-accounts/partials/rent-collection-asset-fields.blade.php'));
    $list = file_get_contents(resource_path('views/bank-accounts/partials/holder-grouped-list.blade.php'));

    expect($service)->toContain('function requireRentCollectionAssetsWhenLeasable')
        ->and($service)->toContain('Select at least one leasable asset for this rent collection account.')
        ->and($controller)->toContain('requireRentCollectionAssetsWhenLeasable')
        ->and($fields)->toContain('$assetsRequired')
        ->and($fields)->toContain('Required when rent receiving')
        ->and($list)->toContain('data-rent-assets-missing');
});
