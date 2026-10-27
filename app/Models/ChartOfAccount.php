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
     * Active accounts for reconciliation / journal / invoice pickers.
     *
     * @return Collection<int, self>
     */
    public static function activeForSelect(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type', 'account_category']);
    }

    /**
     * Accounts offered when coding a statement line.
     * Loan ledgers omit Bank/Cash and Long Term Loans: those are the statement’s own side.
     *
     * @return Collection<int, self>
     */
    public static function activeForStatementCreate(bool $loanLedger): Collection
    {
        $accounts = static::activeForSelect();

        if (! $loanLedger) {
            return $accounts;
        }

        $excluded = self::loanStatementExcludedCodes();

        return $accounts
            ->reject(fn (self $account): bool => in_array(trim((string) $account->account_code), $excluded, true))
            ->values();
    }

    /**
     * @return list<string>
     */
    public static function loanStatementExcludedCodes(): array
    {
        return [
            (string) config('financial.report_accounts.bank_cash', '1100'),
            (string) config('financial.report_accounts.long_term_loans', '4000'),
        ];
    }

    /**
     * Active chart accounts for dashboard allocation pickers (full active chart).
     *
     * @return Collection<int, self>
     */
    public static function activePnlForSelect(): Collection
    {
        return static::activeForSelect();
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

    /**
     * SPA row payload. Display count may be entity-scoped; delete safety always uses firm-wide usage.
     *
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
    public function toSpaPayload(?int $journalLinesCount = null, ?int $firmJournalLinesForDelete = null): array
    {
        $displayJournalLines = $journalLinesCount ?? (int) ($this->journal_lines_count ?? 0);
        $deleteJournalLines = $firmJournalLinesForDelete ?? $displayJournalLines;
        $children = (int) ($this->child_accounts_count ?? 0);
        $depreciation = (int) ($this->assets_as_depreciation_account_count ?? 0);

        return [
            'id' => $this->id,
            'account_code' => $this->account_code,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'account_category' => $this->account_category,
            'parent_account_id' => $this->parent_account_id,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_system_account' => $this->isSystemAccount(),
            'journal_lines_count' => $displayJournalLines,
            'can_delete' => $deleteJournalLines === 0 && $children === 0 && $depreciation === 0,
            'update_url' => route('chart-of-accounts.update', $this),
            'destroy_url' => route('chart-of-accounts.destroy', $this),
        ];
    }
}
