<?php

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\BusinessEntityBankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function editLinkFormEntity(User $user): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Maanya Street Discretionary Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '6 Lisbon St',
        'registered_email' => 'demo@example.test',
        'phone_number' => '0400000000',
        'user_id' => $user->id,
    ]);
}

function editLinkFormAccount(BusinessEntity $entity, User $user, string $purpose = BankAccount::PURPOSE_GENERAL): BankAccount
{
    return BankAccount::create([
        'business_entity_id' => $entity->id,
        'user_id' => $user->id,
        'bank_name' => 'Westpac',
        'bsb' => '033000',
        'account_number' => '12346553',
        'account_name' => '71 Princess Highway',
        'account_purpose' => $purpose,
        'holder_type' => BankAccount::HOLDER_ENTITY,
        'holder_entity_id' => $entity->id,
    ]);
}

it('renders the rent collection asset picker without a blade compile error', function () {
    $this->withViewErrors([]);

    $html = view('bank-accounts.partials.rent-collection-asset-fields', [
        'leasableAssets' => collect([
            (object) ['id' => 9, 'name' => 'Unit 1'],
        ]),
        'selectedAssetIds' => [],
        'purposeSelectId' => 'edit_link_account_purpose',
        'defaultPurpose' => BankAccount::PURPOSE_GENERAL,
        'fieldId' => 'edit_link_rent_collection_asset_ids',
    ])->render();

    expect($html)->toContain('edit_link_rent_collection_asset_ids')
        ->and($html)->toContain('Unit 1')
        ->and($html)->toContain('hidden')
        ->and($html)->not->toMatch('/\srequired(\s|>)/');
});

it('marks rent collection assets required when the current purpose is rent receiving', function () {
    $this->withViewErrors([]);

    $html = view('bank-accounts.partials.rent-collection-asset-fields', [
        'leasableAssets' => collect([
            (object) ['id' => 9, 'name' => 'Unit 1'],
        ]),
        'selectedAssetIds' => [9],
        'purposeSelectId' => 'edit_link_account_purpose',
        'defaultPurpose' => BankAccount::PURPOSE_RENT_RECEIVING,
        'fieldId' => 'edit_link_rent_collection_asset_ids',
    ])->render();

    expect($html)->toMatch('/\srequired(\s|>)/')
        ->and($html)->not->toContain('class="bank-field hidden"');
});

it('loads the entity bank account edit link form', function () {
    $user = User::factory()->create();
    $entity = editLinkFormEntity($user);
    $account = editLinkFormAccount($entity, $user);
    $link = BusinessEntityBankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_account_id' => $account->id,
        'purpose' => BankAccount::PURPOSE_GENERAL,
    ]);

    Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '71 Princess Highway',
        'acquisition_date' => '2023-03-24',
        'acquisition_cost' => 500000,
        'current_value' => 500000,
        'status' => 'Active',
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('entities.bank-account-links.form.edit', [$entity, $link]));

    $response->assertSuccessful()
        ->assertJsonPath('status', true);

    expect($response->json('html'))->toContain('data-edit-link-form')
        ->and($response->json('html'))->toContain('71 Princess Highway');
});
