<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\Person;
use App\Support\SecurityAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountPanelController extends Controller
{
    public function portfolioWorkspace(): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return response()->json([
            'status' => true,
            'list_html' => self::portfolioListHtml(),
        ]);
    }

    public function portfolioCreateForm(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $person) => $person->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.portfolio.create-form', [
                'businessEntities' => $businessEntities,
                'persons' => $persons,
                'holderType' => $request->query('holder_type'),
                'holderEntityId' => $request->query('holder_entity_id'),
                'holderPersonId' => $request->query('holder_person_id'),
                'purpose' => $request->query('purpose'),
            ])->render(),
        ]);
    }

    public function portfolioEditForm(BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        $this->ensureOwned($bankAccount);

        $bankAccount->load(['holderEntity', 'holderPerson']);
        $businessEntities = BusinessEntity::operationalEntities()->orderBy('legal_name')->get();
        $persons = Person::query()
            ->linkedToOperationalEntities()
            ->get()
            ->sortBy(fn (Person $person) => $person->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        SecurityAuditLogger::bankAccountNumberViewed(auth()->user(), $bankAccount, 'edit_form');

        return response()->json([
            'status' => true,
            'html' => view('bank-accounts.partials.portfolio.edit-form', [
                'bankAccount' => $bankAccount,
                'businessEntities' => $businessEntities,
                'persons' => $persons,
            ])->render(),
        ]);
    }

    public static function portfolioListHtml(): string
    {
        $bankAccounts = BankAccount::query()
            ->visibleInPortfolio()
            ->withDeleteCounts()
            ->with(['businessEntity', 'holderEntity', 'holderPerson'])
            ->orderBy('account_name')
            ->get();

        return view('bank-accounts.partials.portfolio.list', [
            'holderGroups' => BankAccount::groupedByHolder($bankAccounts),
        ])->render();
    }

    public static function listHtmlForContext(?string $context): ?string
    {
        if ($context === null || $context === '' || $context === 'portfolio') {
            return self::portfolioListHtml();
        }

        if (str_starts_with($context, 'person:')) {
            $person = Person::find((int) substr($context, 7));

            return $person ? PersonShowWorkspaceController::bankAccountsListHtml($person) : null;
        }

        if (str_starts_with($context, 'entity:')) {
            $entity = BusinessEntity::find((int) substr($context, 7));
            if (! $entity) {
                return null;
            }

            $links = $entity->bankAccountLinksForDisplay();

            return view('business-entities.partials.bank-accounts.list', [
                'businessEntity' => $entity,
                'holderGroups' => BankAccount::groupedLinksByHolder($links, $entity->id),
            ])->render();
        }

        return self::portfolioListHtml();
    }

    private function ensureOwned(BankAccount $bankAccount): void
    {
        if (! $bankAccount->isAccessibleByCurrentUser()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
