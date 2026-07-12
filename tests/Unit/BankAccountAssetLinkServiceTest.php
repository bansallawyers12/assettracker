<?php

namespace Tests\Unit;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Services\BankAccountAssetLinkService;
use PHPUnit\Framework\TestCase;

class BankAccountAssetLinkServiceTest extends TestCase
{
    public function test_rent_collection_asset_validation_rules(): void
    {
        $rules = (new BankAccountAssetLinkService)->rentCollectionAssetValidationRules();

        $this->assertArrayHasKey('rent_collection_asset_ids', $rules);
        $this->assertArrayHasKey('rent_collection_asset_ids.*', $rules);
        $this->assertSame('nullable|array', $rules['rent_collection_asset_ids']);
    }

    public function test_validate_rent_collection_asset_ids_returns_empty_for_null_or_blank(): void
    {
        $service = new BankAccountAssetLinkService;
        $entity = new BusinessEntity;
        $entity->id = 1;

        $this->assertSame([], $service->validateRentCollectionAssetIds($entity, null));
        $this->assertSame([], $service->validateRentCollectionAssetIds($entity, []));
        $this->assertSame([], $service->validateRentCollectionAssetIds($entity, [0, '', null]));
    }

    public function test_enrich_holder_groups_leaves_non_rent_rows_without_assets(): void
    {
        $service = new BankAccountAssetLinkService;
        $entity = new BusinessEntity;
        $entity->id = 33;

        $account = new BankAccount(['account_name' => 'Loan Acct']);
        $account->id = 10;

        $link = new \App\Models\BusinessEntityBankAccount([
            'business_entity_id' => 33,
            'bank_account_id' => 10,
            'purpose' => BankAccount::PURPOSE_LOAN,
        ]);

        $groups = [
            [
                'label' => 'Entity',
                'entries' => collect([
                    [
                        'link' => $link,
                        'account' => $account,
                        'purpose' => BankAccount::PURPOSE_LOAN,
                    ],
                ]),
            ],
        ];

        $enriched = $service->enrichHolderGroupsWithRentAssets($entity, $groups);

        $this->assertTrue($enriched[0]['entries'][0]['rent_assets']->isEmpty());
    }

    public function test_purpose_rent_receiving_constant_matches_ui_expectation(): void
    {
        $this->assertSame('rent_receiving', BankAccount::PURPOSE_RENT_RECEIVING);
        $this->assertSame('rent_collection', BankAccount::ROLE_RENT_COLLECTION);
        $this->assertContains(BankAccount::PURPOSE_RENT_RECEIVING, BankAccount::RENT_RECEIVING_PURPOSES);
    }
}
