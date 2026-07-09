<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountsWorkspaceController extends Controller
{
    public function index(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $entityBankAccountLinks = $businessEntity->bankAccountLinksForDisplay();
        $entityBankAccountGroups = BankAccount::groupedLinksByHolder($entityBankAccountLinks, $businessEntity->id);

        return response()->json([
            'status' => true,
            'list_html' => view('business-entities.partials.bank-accounts.list', [
                'businessEntity' => $businessEntity,
                'holderGroups' => $entityBankAccountGroups,
            ])->render(),
        ]);
    }

    public function attachForm(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

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
}
