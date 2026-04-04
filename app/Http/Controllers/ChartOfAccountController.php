<?php

namespace App\Http\Controllers;

use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $accounts = ChartOfAccount::query()
            ->withCount('journalLines')
            ->orderBy('account_code')
            ->get();

        return view('chart-of-accounts.index', compact('accounts'));
    }

    /**
     * Active accounts as JSON (shared by all business entities).
     */
    public function apiIndex(): JsonResponse
    {
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

    /**
     * @deprecated Use apiIndex — kept for existing URLs that include a business entity segment.
     */
    public function getAccountsJson(BusinessEntity $businessEntity): JsonResponse
    {
        return $this->apiIndex();
    }

    public function create(): \Illuminate\View\View
    {
        $parentAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('chart-of-accounts.create', compact('parentAccounts'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
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
        $parentAccounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->where('id', '!=', $chart_of_account->id)
            ->orderBy('account_code')
            ->get();

        $chartOfAccount = $chart_of_account;

        return view('chart-of-accounts.edit', compact('chartOfAccount', 'parentAccounts'));
    }

    public function update(Request $request, ChartOfAccount $chart_of_account): \Illuminate\Http\RedirectResponse
    {
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

    public function destroy(ChartOfAccount $chart_of_account): \Illuminate\Http\RedirectResponse
    {
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
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
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
