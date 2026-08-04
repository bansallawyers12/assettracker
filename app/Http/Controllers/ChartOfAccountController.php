<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Support\TableSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function index(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $tableSort = TableSort::resolve($request, ['code', 'name', 'type', 'category', 'journal_lines', 'status'], 'code', 'asc');

        $query = ChartOfAccount::query()->withCount('journalLines');
        $tableSort->applyToQuery($query, [
            'code' => 'account_code',
            'name' => 'account_name',
            'type' => 'account_type',
            'category' => 'account_category',
            'journal_lines' => 'journal_lines_count',
            'status' => 'is_active',
        ], 'code');

        $accounts = $query->get();

        return view('chart-of-accounts.index', compact('accounts', 'tableSort'));
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

    public function create(): \Illuminate\View\View
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $parentAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('chart-of-accounts.create', compact('parentAccounts'));
    }

    public function store(Request $request, ?BusinessEntity $businessEntity = null): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

        $this->validateNewAccount($request);

        ChartOfAccount::create([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'account_category' => $request->account_category,
            'parent_account_id' => $request->parent_account_id,
            'description' => $request->description,
            'opening_balance' => $request->opening_balance ?? 0,
            'current_balance' => $request->opening_balance ?? 0,
        ]);

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account created successfully.');
    }

    public function edit(ChartOfAccount $chart_of_account): \Illuminate\View\View
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $parentAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('id', '!=', $chart_of_account->id)
            ->orderBy('account_code')
            ->get();

        $chartOfAccount = $chart_of_account;

        return view('chart-of-accounts.edit', compact('chartOfAccount', 'parentAccounts'));
    }

    public function update(Request $request, ChartOfAccount $chart_of_account, ?BusinessEntity $businessEntity = null): \Illuminate\Http\RedirectResponse
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
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:' . implode(',', array_keys(ChartOfAccount::$accountTypes)),
            'account_category' => ['required', 'string', 'max:50', Rule::in(array_keys(ChartOfAccount::$accountCategories))],
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

        $chart_of_account->update(array_merge($request->only([
            'account_code',
            'account_name',
            'account_type',
            'account_category',
            'parent_account_id',
            'description',
        ]), [
            'is_active' => $request->boolean('is_active'),
        ]));

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account updated successfully.');
    }

    public function destroy(ChartOfAccount $chart_of_account, ?BusinessEntity $businessEntity = null): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);
        if ($businessEntity) {
            $this->authorize('update', $businessEntity);
            $this->ensureNotClosed($businessEntity);
        }

        if ($chart_of_account->journalLines()->exists()) {
            return redirect()->route('chart-of-accounts.index')
                ->with('error', 'Cannot delete account with existing journal entries. Deactivate instead.');
        }

        if ($chart_of_account->childAccounts()->exists()) {
            return redirect()->route('chart-of-accounts.index')
                ->with('error', 'Cannot delete an account that has sub-accounts. Reassign or remove sub-accounts first.');
        }

        if ($chart_of_account->assetsAsDepreciationAccount()->exists()) {
            return redirect()->route('chart-of-accounts.index')
                ->with('error', 'Cannot delete an account linked as a depreciation account on one or more assets.');
        }

        $chart_of_account->delete();

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Chart of account deleted successfully.');
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
            'account_type' => 'required|in:' . implode(',', array_keys(ChartOfAccount::$accountTypes)),
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
