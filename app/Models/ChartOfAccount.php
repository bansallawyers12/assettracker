<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'business_entity_id',
        'account_code',
        'account_name',
        'account_type',
        'account_category',
        'parent_account_id',
        'is_active',
        'description',
        'opening_balance',
        'current_balance'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public static $accountTypes = [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense'
    ];

    public static $accountCategories = [
        'current_asset' => 'Current Asset',
        'fixed_asset' => 'Fixed Asset',
        'intangible_asset' => 'Intangible Asset',
        'current_liability' => 'Current Liability',
        'long_term_liability' => 'Long Term Liability',
        'equity' => 'Equity',
        'operating_income' => 'Operating Income',
        'other_income' => 'Other Income',
        'operating_expense' => 'Operating Expense',
        'other_expense' => 'Other Expense'
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function parentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function childAccounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }
}
