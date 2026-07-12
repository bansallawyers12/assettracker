<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BankAccountAssetLinkService
{
    /**
     * Leasable assets belonging to the entity (for rent-collection pickers).
     *
     * @return Collection<int, Asset>
     */
    public function leasableAssetsForEntity(BusinessEntity $entity): Collection
    {
        return $entity->assets()
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->orderBy('name')
            ->get(['id', 'name', 'asset_type', 'business_entity_id']);
    }

    /**
     * Assets on this entity that use the account as Rent Paid Into.
     *
     * @return Collection<int, Asset>
     */
    public function rentCollectionAssetsForAccount(BusinessEntity $entity, BankAccount $account): Collection
    {
        return Asset::query()
            ->where('business_entity_id', $entity->id)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('bankAccounts', function ($query) use ($account) {
                $query->where('bank_accounts.id', $account->id)
                    ->where('asset_bank_account.role', BankAccount::ROLE_RENT_COLLECTION);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'asset_type', 'business_entity_id']);
    }

    /**
     * Map bank_account_id => Collection of assets with rent_collection on this entity.
     *
     * @param  Collection<int, mixed>  $links  BusinessEntityBankAccount rows (or any with bank_account_id)
     * @return array<int, Collection<int, Asset>>
     */
    public function rentCollectionAssetsByAccountId(BusinessEntity $entity, Collection $links): array
    {
        $accountIds = $links
            ->filter(fn ($link) => ($link->purpose ?? null) === BankAccount::PURPOSE_RENT_RECEIVING)
            ->pluck('bank_account_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($accountIds === []) {
            return [];
        }

        $assets = Asset::query()
            ->where('business_entity_id', $entity->id)
            ->whereIn('asset_type', Asset::LEASABLE_ASSET_TYPES)
            ->whereHas('bankAccounts', function ($query) use ($accountIds) {
                $query->whereIn('bank_accounts.id', $accountIds)
                    ->where('asset_bank_account.role', BankAccount::ROLE_RENT_COLLECTION);
            })
            ->with(['bankAccounts' => function ($query) use ($accountIds) {
                $query->whereIn('bank_accounts.id', $accountIds)
                    ->wherePivot('role', BankAccount::ROLE_RENT_COLLECTION);
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'asset_type', 'business_entity_id']);

        $map = [];
        foreach ($accountIds as $accountId) {
            $map[(int) $accountId] = collect();
        }

        foreach ($assets as $asset) {
            foreach ($asset->bankAccounts as $linkedAccount) {
                $id = (int) $linkedAccount->id;
                if (isset($map[$id])) {
                    $map[$id]->push($asset);
                }
            }
        }

        return $map;
    }

    /**
     * Enrich grouped entity bank-account rows with rent_collection assets.
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    public function enrichHolderGroupsWithRentAssets(BusinessEntity $entity, array $groups): array
    {
        $links = collect($groups)
            ->flatMap(fn (array $group) => $group['entries'] ?? collect())
            ->map(fn (array $entry) => $entry['link'] ?? null)
            ->filter();

        $assetsByAccountId = $this->rentCollectionAssetsByAccountId($entity, $links);

        foreach ($groups as &$group) {
            $entries = $group['entries'] ?? collect();
            $group['entries'] = $entries->map(function (array $entry) use ($assetsByAccountId) {
                $purpose = $entry['purpose'] ?? null;
                $accountId = (int) ($entry['account']->id ?? 0);

                $entry['rent_assets'] = $purpose === BankAccount::PURPOSE_RENT_RECEIVING
                    ? ($assetsByAccountId[$accountId] ?? collect())
                    : collect();

                return $entry;
            });
        }
        unset($group);

        return $groups;
    }

    /**
     * @param  array<int|string>|null  $assetIds
     * @return list<int>
     */
    public function validateRentCollectionAssetIds(BusinessEntity $entity, ?array $assetIds): array
    {
        $assetIds = array_values(array_unique(array_filter(
            array_map('intval', $assetIds ?? []),
            fn (int $id) => $id > 0
        )));

        if ($assetIds === []) {
            return [];
        }

        $validIds = $this->leasableAssetsForEntity($entity)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $invalid = array_values(array_diff($assetIds, $validIds));

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'rent_collection_asset_ids' => 'Select only leasable assets that belong to this entity.',
            ]);
        }

        return $assetIds;
    }

    /**
     * Link selected assets to this account as Rent Paid Into (replaces any existing rent link on those assets).
     * Does not unlink other assets that already use this account.
     *
     * @param  list<int>  $assetIds
     * @return int Number of assets linked
     */
    public function linkRentCollectionToAssets(
        BankAccount $account,
        BusinessEntity $entity,
        array $assetIds
    ): int {
        $assetIds = $this->validateRentCollectionAssetIds($entity, $assetIds);

        if ($assetIds === []) {
            return 0;
        }

        if (! $account->isValidForAssetRole($entity, BankAccount::ROLE_RENT_COLLECTION)) {
            throw ValidationException::withMessages([
                'rent_collection_asset_ids' => 'This bank account is not valid for rent collection on this entity.',
            ]);
        }

        $linked = 0;

        foreach ($assetIds as $assetId) {
            $asset = Asset::query()
                ->where('business_entity_id', $entity->id)
                ->whereKey($assetId)
                ->first();

            if (! $asset) {
                continue;
            }

            $asset->bankAccounts()->wherePivot('role', BankAccount::ROLE_RENT_COLLECTION)->detach();
            $asset->bankAccounts()->attach($account->id, ['role' => BankAccount::ROLE_RENT_COLLECTION]);
            $linked++;
        }

        return $linked;
    }

    /**
     * Sync rent_collection links for this account on the entity: selected assets get the account;
     * assets previously linked to this account but unchecked are unlinked.
     *
     * @param  list<int>|array<int|string>|null  $assetIds
     * @return array{linked: int, unlinked: int}
     */
    public function syncRentCollectionAssets(
        BankAccount $account,
        BusinessEntity $entity,
        ?array $assetIds
    ): array {
        $assetIds = $this->validateRentCollectionAssetIds($entity, $assetIds);

        if ($assetIds !== [] && ! $account->isValidForAssetRole($entity, BankAccount::ROLE_RENT_COLLECTION)) {
            throw ValidationException::withMessages([
                'rent_collection_asset_ids' => 'This bank account is not valid for rent collection on this entity.',
            ]);
        }

        $currentlyLinked = $this->rentCollectionAssetsForAccount($entity, $account)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toUnlink = array_values(array_diff($currentlyLinked, $assetIds));
        $toLink = array_values(array_diff($assetIds, $currentlyLinked));

        foreach ($toUnlink as $assetId) {
            $asset = Asset::query()
                ->where('business_entity_id', $entity->id)
                ->whereKey($assetId)
                ->first();

            if ($asset) {
                $asset->bankAccounts()
                    ->wherePivot('role', BankAccount::ROLE_RENT_COLLECTION)
                    ->detach($account->id);
            }
        }

        $linked = $this->linkRentCollectionToAssets($account, $entity, $toLink);

        return [
            'linked' => $linked,
            'unlinked' => count($toUnlink),
        ];
    }

    /**
     * Unlink this account as Rent Paid Into on all leasable assets of the entity.
     */
    public function unlinkAllRentCollectionAssetsForAccount(
        BankAccount $account,
        BusinessEntity $entity
    ): int {
        $assets = $this->rentCollectionAssetsForAccount($entity, $account);
        $unlinked = 0;

        foreach ($assets as $asset) {
            $asset->bankAccounts()
                ->wherePivot('role', BankAccount::ROLE_RENT_COLLECTION)
                ->detach($account->id);
            $unlinked++;
        }

        return $unlinked;
    }

    /**
     * @return array<string, mixed>
     */
    public function rentCollectionAssetValidationRules(): array
    {
        return [
            'rent_collection_asset_ids' => 'nullable|array',
            'rent_collection_asset_ids.*' => 'integer|exists:assets,id',
        ];
    }
}
