<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'account_category',
        'parent_account_id',
        'is_active',
        'description',
        'opening_balance',
        'current_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Active accounts for reconciliation / journal pickers.
     *
     * @return Collection<int, self>
     */
    public static function activeForSelect(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);
    }

    /**
     * Active income accounts (legacy helper; invoice lines use activeForSelect).
     *
     * @return Collection<int, self>
     */
    public static function activeIncomeForSelect(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->where('account_type', 'income')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);
    }

    public static $accountTypes = [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'income' => 'Income',
        'expense' => 'Expense',
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
        'other_expense' => 'Other Expense',
    ];

    /**
     * Codes whose code / type / category must not change (posting / report lookups).
     * Always includes report_accounts plus any explicit system_account_codes extras.
     *
     * @return list<string>
     */
    public static function systemAccountCodes(): array
    {
        $codes = array_merge(
            array_values(config('financial.report_accounts', [])),
            config('financial.system_account_codes', [])
        );

        $normalized = array_map(
            static fn (mixed $code): string => trim((string) $code),
            $codes
        );

        return array_values(array_unique(array_filter(
            $normalized,
            static fn (string $code): bool => $code !== ''
        )));
    }

    public function isSystemAccount(): bool
    {
        return in_array(trim((string) $this->account_code), self::systemAccountCodes(), true);
    }

    /**
     * Short UI hint for where an account lands on P&L or the balance sheet.
     *
     * @return array<string, string>
     */
    public static function reportPlacementHints(): array
    {
        return [
            'current_asset' => 'Appears on Balance Sheet → Current assets',
            'fixed_asset' => 'Appears on Balance Sheet → Fixed assets',
            'intangible_asset' => 'Appears on Balance Sheet → Non-current assets',
            'current_liability' => 'Appears on Balance Sheet → Current liabilities',
            'long_term_liability' => 'Appears on Balance Sheet → Long-term liabilities',
            'equity' => 'Appears on Balance Sheet → Equity',
            'operating_income' => 'Appears on Profit & Loss → Operating income',
            'other_income' => 'Appears on Profit & Loss → Other income',
            'operating_expense' => 'Appears on Profit & Loss → Operating expenses',
            'other_expense' => 'Appears on Profit & Loss → Other expenses',
        ];
    }

    public static function reportPlacementHint(?string $category): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return self::reportPlacementHints()[$category] ?? null;
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

    public function assetsAsDepreciationAccount()
    {
        return $this->hasMany(Asset::class, 'depreciation_account_id');
    }
}
