<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $accounts = ChartOfAccount::query()
            ->firmWide()
            ->withCount(['journalLines', 'childAccounts', 'assetsAsDepreciationAccount'])
            ->orderBy('account_code')
            ->get()
            ->map(fn (ChartOfAccount $account) => $this->accountPayload($account))
            ->values();

        return view('chart-of-accounts.index', [
            'accountsPayload' => $accounts,
            'accountTypes' => ChartOfAccount::$accountTypes,
            'accountCategories' => ChartOfAccount::$accountCategories,
            'reportPlacementHints' => ChartOfAccount::reportPlacementHints(),
            'storeUrl' => route('chart-of-accounts.store'),
            'indexUrl' => route('chart-of-accounts.index'),
            'openPanel' => $request->query('panel'),
            'openAccountId' => $request->query('account'),
        ]);
    }

    /**
     * Active firm/shared accounts as JSON.
     */
    public function apiIndex(): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return response()->json([
            'success' => true,
            'accounts' => ChartOfAccount::query()
                ->firmWide()
                ->where('is_active', true)
                ->orderBy('account_code')
                ->get()
                ->map(fn (ChartOfAccount $account) => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'account_category' => $account->account_category,
                    'business_entity_id' => null,
                ]),
        ]);
    }

    public function getAccountsJson(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        return response()->json([
            'success' => true,
            'accounts' => ChartOfAccount::activeForSelect((int) $businessEntity->id)
                ->map(fn (ChartOfAccount $account) => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'account_category' => $account->account_category,
                    'business_entity_id' => $account->business_entity_id !== null ? (int) $account->business_entity_id : null,
                ]),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', BusinessEntity::class);

        return redirect()->route('chart-of-accounts.index', ['panel' => 'create']);
    }

    public function store(Request $request, ?BusinessEntity $businessEntity = null): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);

        $ownerEntity = $this->resolveAccountOwnerEntity($request, $businessEntity);
        if ($ownerEntity) {
            $this->authorize('update', $ownerEntity);
            $this->ensureNotClosed($ownerEntity);
        }

        $this->validateNewAccount($request, $ownerEntity?->id);

        $account = ChartOfAccount::create([
            'business_entity_id' => $ownerEntity?->id,
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'account_category' => $request->account_category,
            'parent_account_id' => $request->parent_account_id,
            'description' => $request->description,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $account->loadCount(['journalLines', 'childAccounts', 'assetsAsDepreciationAccount']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Chart of account created successfully.',
                'account' => $this->accountPayload($account),
            ], 201);
        }

        if ($ownerEntity) {
            return redirect()
                ->route('business-entities.financial-reports.chart-of-accounts', $ownerEntity)
                ->with('success', 'Chart of account created successfully.');
        }

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account created successfully.');
    }

    public function edit(ChartOfAccount $chart_of_account): RedirectResponse
    {
        $this->authorize('create', BusinessEntity::class);

        return redirect()->route('chart-of-accounts.index', [
            'panel' => 'edit',
            'account' => $chart_of_account->id,
        ]);
    }

    public function update(Request $request, ChartOfAccount $chart_of_account, ?BusinessEntity $businessEntity = null): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

        $ownerEntityId = $chart_of_account->business_entity_id !== null
            ? (int) $chart_of_account->business_entity_id
            : null;

        $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'account_code')->ignore($chart_of_account->id),
                function ($attribute, $value, $fail) use ($chart_of_account) {
                    if ($chart_of_account->isSystemAccount()
                        && trim((string) $value) !== trim((string) $chart_of_account->account_code)) {
                        $fail(__('System account code cannot be changed.'));
                    }
                },
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => [
                'required',
                'in:'.implode(',', array_keys(ChartOfAccount::$accountTypes)),
                function ($attribute, $value, $fail) use ($chart_of_account) {
                    if ($chart_of_account->isSystemAccount()
                        && (string) $value !== (string) $chart_of_account->account_type) {
                        $fail(__('System account type cannot be changed.'));
                    }
                },
            ],
            'account_category' => [
                'required',
                'string',
                'max:50',
                Rule::in(array_keys(ChartOfAccount::$accountCategories)),
                function ($attribute, $value, $fail) use ($chart_of_account) {
                    if ($chart_of_account->isSystemAccount()
                        && (string) $value !== (string) $chart_of_account->account_category) {
                        $fail(__('System account category cannot be changed.'));
                    }
                },
            ],
            'parent_account_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
                function ($attribute, $value, $fail) use ($chart_of_account, $ownerEntityId) {
                    if (! $value) {
                        return;
                    }
                    $pid = (int) $value;
                    if ($pid === (int) $chart_of_account->id) {
                        $fail(__('An account cannot be its own parent.'));

                        return;
                    }
                    if ($this->parentWouldCreateCycle($chart_of_account, $pid)) {
                        $fail(__('That parent would create a circular hierarchy.'));

                        return;
                    }
                    if (! $this->parentIsAllowedForOwner($pid, $ownerEntityId)) {
                        $fail(__('Parent account must be firm-wide or belong to the same entity.'));
                    }
                },
            ],
            'description' => 'nullable|string',
            'is_active' => 'nullable|in:0,1',
        ]);

        $attributes = [
            'account_name' => $request->account_name,
            'parent_account_id' => $request->parent_account_id ?: null,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! $chart_of_account->isSystemAccount()) {
            $attributes['account_code'] = $request->account_code;
            $attributes['account_type'] = $request->account_type;
            $attributes['account_category'] = $request->account_category;
        }

        $chart_of_account->update($attributes);

        $chart_of_account->refresh();
        $chart_of_account->loadCount(['journalLines', 'childAccounts', 'assetsAsDepreciationAccount']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Chart of account updated successfully.',
                'account' => $this->accountPayload($chart_of_account),
            ]);
        }

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account updated successfully.');
    }

    public function destroy(Request $request, ChartOfAccount $chart_of_account, ?BusinessEntity $businessEntity = null): RedirectResponse|JsonResponse
    {
        $this->authorize('create', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

        if ($chart_of_account->journalLines()->exists()) {
            return $this->destroyBlocked(
                $request,
                'Cannot delete account with existing journal entries. Deactivate instead.'
            );
        }

        if ($chart_of_account->childAccounts()->exists()) {
            return $this->destroyBlocked(
                $request,
                'Cannot delete an account that has sub-accounts. Reassign or remove sub-accounts first.'
            );
        }

        if ($chart_of_account->assetsAsDepreciationAccount()->exists()) {
            return $this->destroyBlocked(
                $request,
                'Cannot delete an account linked as a depreciation account on one or more assets.'
            );
        }

        $id = $chart_of_account->id;
        $chart_of_account->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Chart of account deleted successfully.',
                'id' => $id,
            ]);
        }

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account deleted successfully.');
    }

    /**
     * @return array{
     *     id: int,
     *     account_code: string,
     *     account_name: string,
     *     account_type: string,
     *     account_category: string,
     *     parent_account_id: int|null,
     *     description: string|null,
     *     is_active: bool,
     *     is_system_account: bool,
     *     journal_lines_count: int,
     *     can_delete: bool,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    private function accountPayload(ChartOfAccount $account): array
    {
        return $account->toSpaPayload();
    }

    private function destroyBlocked(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()->route('chart-of-accounts.index')->with('error', $message);
    }

    private function validateNewAccount(Request $request, ?int $ownerEntityId): void
    {
        $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'account_code'),
                function ($attribute, $value, $fail) use ($ownerEntityId) {
                    if ($ownerEntityId === null) {
                        return;
                    }
                    if (in_array(trim((string) $value), ChartOfAccount::systemAccountCodes(), true)) {
                        $fail(__('System account codes are firm-wide and cannot be created per entity.'));
                    }
                },
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:'.implode(',', array_keys(ChartOfAccount::$accountTypes)),
            'account_category' => ['required', 'string', 'max:50', Rule::in(array_keys(ChartOfAccount::$accountCategories))],
            'parent_account_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
                function ($attribute, $value, $fail) use ($ownerEntityId) {
                    if (! $value) {
                        return;
                    }
                    $parent = ChartOfAccount::find($value);
                    if ($parent && ! $parent->is_active) {
                        $fail(__('Cannot assign an inactive account as parent.'));

                        return;
                    }
                    if (! $this->parentIsAllowedForOwner((int) $value, $ownerEntityId)) {
                        $fail(__('Parent account must be firm-wide or belong to the same entity.'));
                    }
                },
            ],
            'description' => 'nullable|string',
            'business_entity_id' => ['nullable', 'integer', 'exists:business_entities,id'],
        ]);
    }

    private function resolveAccountOwnerEntity(Request $request, ?BusinessEntity $routeEntity): ?BusinessEntity
    {
        if ($routeEntity) {
            return $routeEntity;
        }

        $id = (int) $request->input('business_entity_id', 0);
        if ($id <= 0) {
            return null;
        }

        return BusinessEntity::query()->find($id);
    }

    private function parentIsAllowedForOwner(int $parentId, ?int $ownerEntityId): bool
    {
        $parent = ChartOfAccount::query()->find($parentId);
        if (! $parent) {
            return false;
        }

        if ($parent->business_entity_id === null) {
            return true;
        }

        return $ownerEntityId !== null && (int) $parent->business_entity_id === $ownerEntityId;
    }

    /**
     * True if assigning $newParentId as parent would make $account an ancestor of itself.
     */
    private function parentWouldCreateCycle(ChartOfAccount $account, int $newParentId): bool
    {
        $current = $newParentId;
        $guard = 0;
        while ($current && $guard++ < 500) {
            if ((int) $current === (int) $account->id) {
                return true;
            }
            $current = ChartOfAccount::where('id', $current)->value('parent_account_id');
        }

        return false;
    }
}
