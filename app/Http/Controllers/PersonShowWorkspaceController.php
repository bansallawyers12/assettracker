<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Person;
use App\Support\SecurityAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonShowWorkspaceController extends Controller
{
    public function roles(Person $person): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        [$entityPersons, $groupedRoles] = $this->loadRoles($person);

        return response()->json([
            'status' => true,
            'roles_html' => view('persons.partials.roles-list', [
                'person' => $person,
                'groupedRoles' => $groupedRoles,
            ])->render(),
            'summary_html' => view('persons.partials.summary-stats', [
                'entityPersons' => $entityPersons,
            ])->render(),
        ]);
    }

    public function entityPicker(Person $person): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()
            ->whereNotIn('entity_type', ['Tenancy Contact', 'Property Manager'])
            ->orderBy('legal_name')
            ->get();

        return response()->json([
            'status' => true,
            'html' => view('persons.partials.roles.entity-picker', [
                'person' => $person,
                'businessEntities' => $businessEntities,
            ])->render(),
        ]);
    }

    public function bankAccounts(Person $person): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return response()->json([
            'status' => true,
            'list_html' => self::bankAccountsListHtml($person),
        ]);
    }

    public function createBankAccountForm(Request $request, Person $person): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $p) => $p->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'status' => true,
            'html' => view('persons.partials.bank-accounts.create-form', [
                'person' => $person,
                'businessEntities' => $businessEntities,
                'persons' => $persons,
                'holderType' => $request->query('holder_type', BankAccount::HOLDER_PERSON),
                'holderPersonId' => $request->integer('holder_person_id') ?: $person->id,
            ])->render(),
        ]);
    }

    public function editBankAccountForm(Person $person, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureBankAccountBelongsToPerson($person, $bankAccount);

        $bankAccount->load(['holderEntity', 'holderPerson']);
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $p) => $p->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        SecurityAuditLogger::bankAccountNumberViewed(auth()->user(), $bankAccount, 'edit_form');

        return response()->json([
            'status' => true,
            'html' => view('persons.partials.bank-accounts.edit-form', [
                'person' => $person,
                'bankAccount' => $bankAccount,
                'businessEntities' => $businessEntities,
                'persons' => $persons,
            ])->render(),
        ]);
    }

    public static function bankAccountsListHtml(Person $person): string
    {
        $heldBankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->withDeleteCounts()
            ->where('holder_type', BankAccount::HOLDER_PERSON)
            ->where('holder_person_id', $person->id)
            ->with(['businessEntity', 'holderEntity', 'holderPerson'])
            ->orderBy('account_name')
            ->get();

        $heldBankAccountGroups = BankAccount::groupedByHolder($heldBankAccounts);

        return view('persons.partials.bank-accounts.list', [
            'person' => $person,
            'holderGroups' => $heldBankAccountGroups,
        ])->render();
    }

    private function loadRoles(Person $person): array
    {
        $entityPersons = EntityPerson::where('person_id', $person->id)
            ->with(['businessEntity', 'person', 'trusteeEntity'])
            ->orderBy('business_entity_id')
            ->orderBy('role')
            ->get();

        $groupedRoles = $entityPersons->groupBy('business_entity_id');

        return [$entityPersons, $groupedRoles];
    }

    private function ensureBankAccountBelongsToPerson(Person $person, BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }

        if ($bankAccount->holder_type !== BankAccount::HOLDER_PERSON
            || (int) $bankAccount->holder_person_id !== (int) $person->id) {
            abort(404);
        }
    }
}
