<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\BusinessEntityBankAccount;
use App\Models\Person;
use App\Services\BankAccountAssetLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountsWorkspaceController extends Controller
{
    public function __construct(
        private BankAccountAssetLinkService $bankAccountAssetLinkService
    ) {}

    public function index(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $entityBankAccountLinks = $businessEntity->bankAccountLinksForDisplay();
        $entityBankAccountGroups = $this->bankAccountAssetLinkService->enrichHolderGroupsWithRentAssets(
            $businessEntity,
            BankAccount::groupedLinksByHolder($entityBankAccountLinks, $businessEntity->id)
        );

        return response()->json([
            'status' => true,
            'list_html' => view('business-entities.partials.bank-accounts.list', [
                'businessEntity' => $businessEntity,
                'holderGroups' => $entityBankAccountGroups,
            ])->render(),
        ]);
    }

    public function attachForm(Request $request, BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        $defaultPurpose = $request->query('default_purpose');
        if ($defaultPurpose && ! in_array($defaultPurpose, BankAccount::ENTITY_PURPOSES, true)) {
            $defaultPurpose = BankAccount::PURPOSE_GENERAL;
        }

        $portfolioBankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->with([
                'businessEntity',
                'holderEntity',
                'holderPerson',
                'entityPurposeLinks' => fn ($q) => $q->where('business_entity_id', $businessEntity->id),
            ])
            ->orderBy('account_name')
            ->get();

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.bank-accounts.attach-form', [
                'businessEntity' => $businessEntity,
                'portfolioBankAccounts' => $portfolioBankAccounts,
                'leasableAssets' => $this->bankAccountAssetLinkService->leasableAssetsForEntity($businessEntity),
                'defaultPurpose' => $defaultPurpose ?: BankAccount::PURPOSE_GENERAL,
            ])->render(),
        ]);
    }

    public function createForm(Request $request, BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $person) => $person->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.bank-accounts.create-form', [
                'businessEntity' => $businessEntity,
                'businessEntities' => $businessEntities,
                'persons' => $persons,
                'holderType' => $request->query('holder_type'),
                'holderEntityId' => $request->query('holder_entity_id'),
                'holderPersonId' => $request->query('holder_person_id'),
                'leasableAssets' => $this->bankAccountAssetLinkService->leasableAssetsForEntity($businessEntity),
            ])->render(),
        ]);
    }

    public function editForm(BusinessEntity $businessEntity, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        if (! $bankAccount->hasLinkOnEntity($businessEntity) && (int) $bankAccount->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }

        $bankAccount->load(['holderEntity', 'holderPerson']);
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $person) => $person->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        \App\Support\SecurityAuditLogger::bankAccountNumberViewed(auth()->user(), $bankAccount, 'edit_form');

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.entity.edit-form', [
                'businessEntity' => $businessEntity,
                'bankAccount' => $bankAccount,
                'businessEntities' => $businessEntities,
                'persons' => $persons,
            ])->render(),
        ]);
    }

    public function rentAssetsForm(BusinessEntity $businessEntity, BusinessEntityBankAccount $bankAccountLink): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        if ((int) $bankAccountLink->business_entity_id !== (int) $businessEntity->id) {
            abort(403);
        }

        if ($bankAccountLink->purpose !== BankAccount::PURPOSE_RENT_RECEIVING) {
            abort(404);
        }

        $bankAccount = $bankAccountLink->bankAccount;
        if ($bankAccount === null) {
            abort(404);
        }

        $selectedIds = $this->bankAccountAssetLinkService
            ->rentCollectionAssetsForAccount($businessEntity, $bankAccount)
            ->pluck('id')
            ->all();

        return response()->json([
            'status' => true,
            'html' => view('business-entities.partials.bank-accounts.rent-assets-form', [
                'businessEntity' => $businessEntity,
                'bankAccountLink' => $bankAccountLink,
                'bankAccount' => $bankAccount,
                'leasableAssets' => $this->bankAccountAssetLinkService->leasableAssetsForEntity($businessEntity),
                'selectedAssetIds' => $selectedIds,
            ])->render(),
        ]);
    }
}
