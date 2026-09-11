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
     * Active accounts as JSON (shared by all business entities).
     */
    public function apiIndex(): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return response()->json([
            'success' => true,
            'accounts' => ChartOfAccount::query()
                ->where('is_active', true)
                ->orderBy('account_code')
                ->get()
                ->map(fn (ChartOfAccount $account) => [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'account_category' => $account->account_category,
                ]),
        ]);
    }

    public function getAccountsJson(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        return $this->apiIndex();
    }

    public function create(): RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return redirect()->route('chart-of-accounts.index', ['panel' => 'create']);
    }

    public function store(Request $request, ?BusinessEntity $businessEntity = null): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

        $this->validateNewAccount($request);

        $account = ChartOfAccount::create([
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

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account created successfully.');
    }

    public function edit(ChartOfAccount $chart_of_account): RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        return redirect()->route('chart-of-accounts.index', [
            'panel' => 'edit',
            'account' => $chart_of_account->id,
        ]);
    }

    public function update(Request $request, ChartOfAccount $chart_of_account, ?BusinessEntity $businessEntity = null): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

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
                function ($attribute, $value, $fail) use ($chart_of_account) {
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
        $this->authorize('viewAny', BusinessEntity::class);
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
        $journalLines = (int) ($account->journal_lines_count ?? 0);
        $children = (int) ($account->child_accounts_count ?? 0);
        $depreciation = (int) ($account->assets_as_depreciation_account_count ?? 0);

        return [
            'id' => $account->id,
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'account_type' => $account->account_type,
            'account_category' => $account->account_category,
            'parent_account_id' => $account->parent_account_id,
            'description' => $account->description,
            'is_active' => (bool) $account->is_active,
            'is_system_account' => $account->isSystemAccount(),
            'journal_lines_count' => $journalLines,
            'can_delete' => $journalLines === 0 && $children === 0 && $depreciation === 0,
            'update_url' => route('chart-of-accounts.update', $account),
            'destroy_url' => route('chart-of-accounts.destroy', $account),
        ];
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

    private function validateNewAccount(Request $request): void
    {
        $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'account_code'),
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:'.implode(',', array_keys(ChartOfAccount::$accountTypes)),
            'account_category' => ['required', 'string', 'max:50', Rule::in(array_keys(ChartOfAccount::$accountCategories))],
            'parent_account_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $parent = ChartOfAccount::find($value);
                    if ($parent && ! $parent->is_active) {
                        $fail(__('Cannot assign an inactive account as parent.'));
                    }
                },
            ],
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
        ]);
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
