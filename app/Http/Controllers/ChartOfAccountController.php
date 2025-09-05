<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\BusinessEntity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    public function index(BusinessEntity $businessEntity = null)
    {
        if ($businessEntity) {
            // Specific business entity view
            $accounts = ChartOfAccount::where('business_entity_id', $businessEntity->id)
                ->orderBy('account_code')
                ->get();
                
            return view('chart-of-accounts.index', compact('businessEntity', 'accounts'));
        } else {
            // Global view for all business entities
            $user = auth()->user();
            $businessEntities = \App\Models\BusinessEntity::where('user_id', $user->id)->get();
            $accounts = collect();
            
            foreach ($businessEntities as $entity) {
                $entityAccounts = ChartOfAccount::where('business_entity_id', $entity->id)
                    ->orderBy('account_code')
                    ->get();
                $accounts = $accounts->merge($entityAccounts);
            }
            
            // Always pass businessEntity (null for global view) to maintain consistency
            return view('chart-of-accounts.index', compact('accounts', 'businessEntities', 'businessEntity'));
        }
    }

    /**
     * Get chart of accounts as JSON for API calls
     */
    public function getAccountsJson(BusinessEntity $businessEntity)
    {
        $accounts = ChartOfAccount::where('business_entity_id', $businessEntity->id)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'account_type' => $account->account_type,
                    'account_category' => $account->account_category
                ];
            });

        return response()->json([
            'success' => true,
            'accounts' => $accounts
        ]);
    }
    
    public function create(BusinessEntity $businessEntity)
    {
        $parentAccounts = ChartOfAccount::where('business_entity_id', $businessEntity->id)
            ->where('is_active', true)
            ->get();
            
        return view('chart-of-accounts.create', compact('businessEntity', 'parentAccounts'));
    }
    
    public function store(Request $request, BusinessEntity $businessEntity)
    {
        $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'account_code')->where('business_entity_id', $businessEntity->id),
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:' . implode(',', array_keys(ChartOfAccount::$accountTypes)),
            'account_category' => 'required|string|max:50',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric'
        ]);
        
        ChartOfAccount::create([
            'business_entity_id' => $businessEntity->id,
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'account_category' => $request->account_category,
            'parent_account_id' => $request->parent_account_id,
            'description' => $request->description,
            'opening_balance' => $request->opening_balance ?? 0,
            'current_balance' => $request->opening_balance ?? 0
        ]);
        
        return redirect()->route('chart-of-accounts.index', $businessEntity)
            ->with('success', 'Account created successfully!');
    }
    
    public function edit(BusinessEntity $businessEntity, ChartOfAccount $chartOfAccount)
    {
        $parentAccounts = ChartOfAccount::where('business_entity_id', $businessEntity->id)
            ->where('is_active', true)
            ->where('id', '!=', $chartOfAccount->id)
            ->get();
            
        return view('chart-of-accounts.edit', compact('businessEntity', 'chartOfAccount', 'parentAccounts'));
    }
    
    public function update(Request $request, BusinessEntity $businessEntity, ChartOfAccount $chartOfAccount)
    {
        $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'account_code')
                    ->where('business_entity_id', $businessEntity->id)
                    ->ignore($chartOfAccount->id),
            ],
            'account_name' => 'required|string|max:255',
            'account_type' => 'required|in:' . implode(',', array_keys(ChartOfAccount::$accountTypes)),
            'account_category' => 'required|string|max:50',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        $chartOfAccount->update($request->all());
        
        return redirect()->route('chart-of-accounts.index', $businessEntity)
            ->with('success', 'Account updated successfully!');
    }
    
    public function destroy(BusinessEntity $businessEntity, ChartOfAccount $chartOfAccount)
    {
        // Check if account has journal lines
        if ($chartOfAccount->journalLines()->count() > 0) {
            return redirect()->route('chart-of-accounts.index', $businessEntity)
                ->with('error', 'Cannot delete account with existing journal entries. Deactivate instead.');
        }
        
        $chartOfAccount->delete();
        
        return redirect()->route('chart-of-accounts.index', $businessEntity)
            ->with('success', 'Account deleted successfully!');
    }
}
