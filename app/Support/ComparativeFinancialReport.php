<?php

namespace App\Support;

use Carbon\Carbon;

class ComparativeFinancialReport
{
    public const COMPARE_NONE = 'none';

    public const COMPARE_PRIOR_YEAR = 'prior_year';

    /**
     * @return array{0: string, 1: string}
     */
    public static function priorYearPeriod(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate)->subYear()->toDateString(),
            Carbon::parse($endDate)->subYear()->toDateString(),
        ];
    }

    public static function priorYearAsOf(string $asOfDate): string
    {
        return Carbon::parse($asOfDate)->subYear()->toDateString();
    }

    public static function periodColumnLabel(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $start->format('j M Y').' – '.$end->format('j M Y');
    }

    public static function asOfColumnLabel(string $asOfDate): string
    {
        return 'As at '.Carbon::parse($asOfDate)->format('j M Y');
    }

    public static function isEnabled(?string $compare): bool
    {
        return $compare === self::COMPARE_PRIOR_YEAR;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $prior
     * @return array<string, mixed>
     */
    public static function attachProfitLossComparison(array $current, array $prior, bool $hideZeroBalances = true): array
    {
        $current['compare'] = self::COMPARE_PRIOR_YEAR;
        $current['comparison'] = [
            'prior_period' => $prior['period'],
            'current_label' => self::periodColumnLabel(
                $current['period']['start_date'],
                $current['period']['end_date']
            ),
            'prior_label' => self::periodColumnLabel(
                $prior['period']['start_date'],
                $prior['period']['end_date']
            ),
        ];

        $current['income'] = self::mergeProfitLossSection(
            $current['income'] ?? ['by_category' => [], 'total' => 0.0],
            $prior['income'] ?? ['by_category' => [], 'total' => 0.0],
            'income',
            $hideZeroBalances
        );
        $current['expenses'] = self::mergeProfitLossSection(
            $current['expenses'] ?? ['by_category' => [], 'total' => 0.0],
            $prior['expenses'] ?? ['by_category' => [], 'total' => 0.0],
            'expense',
            $hideZeroBalances
        );

        $currentNet = (float) ($current['net_profit'] ?? 0);
        $priorNet = (float) ($prior['net_profit'] ?? 0);
        $current['prior_net_profit'] = $priorNet;
        $current['net_profit_variance'] = round($currentNet - $priorNet, 2);

        return $current;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $prior
     * @return array<string, mixed>
     */
    public static function attachBalanceSheetComparison(array $current, array $prior): array
    {
        $current['compare'] = self::COMPARE_PRIOR_YEAR;
        $current['comparison'] = [
            'prior_as_of_date' => $prior['as_of_date'],
            'current_label' => self::asOfColumnLabel($current['as_of_date']),
            'prior_label' => self::asOfColumnLabel($prior['as_of_date']),
        ];

        $current['assets'] = self::mergeBalanceSheetSection(
            $current['assets'] ?? ['by_category' => [], 'total' => 0.0],
            $prior['assets'] ?? ['by_category' => [], 'total' => 0.0]
        );
        $current['liabilities'] = self::mergeBalanceSheetSection(
            $current['liabilities'] ?? ['by_category' => [], 'total' => 0.0],
            $prior['liabilities'] ?? ['by_category' => [], 'total' => 0.0]
        );
        $current['equity'] = self::mergeBalanceSheetSection(
            $current['equity'] ?? ['by_category' => [], 'total' => 0.0],
            $prior['equity'] ?? ['by_category' => [], 'total' => 0.0]
        );

        $current['prior_total_assets'] = (float) ($prior['total_assets'] ?? 0);
        $current['total_assets_variance'] = round(
            (float) ($current['total_assets'] ?? 0) - $current['prior_total_assets'],
            2
        );
        $current['prior_total_liabilities_equity'] = (float) ($prior['total_liabilities_equity'] ?? 0);
        $current['total_liabilities_equity_variance'] = round(
            (float) ($current['total_liabilities_equity'] ?? 0) - $current['prior_total_liabilities_equity'],
            2
        );

        return $current;
    }

    /**
     * @param  array<string, mixed>  $currentSection
     * @param  array<string, mixed>  $priorSection
     * @return array<string, mixed>
     */
    private static function mergeProfitLossSection(
        array $currentSection,
        array $priorSection,
        string $type,
        bool $hideZeroBalances
    ): array {
        $isIncome = $type === 'income';
        $mergedCategories = [];
        $categoryKeys = array_unique(array_merge(
            array_keys($currentSection['by_category'] ?? []),
            array_keys($priorSection['by_category'] ?? [])
        ));

        foreach ($categoryKeys as $catKey) {
            $currentCat = $currentSection['by_category'][$catKey] ?? null;
            $priorCat = $priorSection['by_category'][$catKey] ?? null;
            $label = $currentCat['label'] ?? $priorCat['label'] ?? (string) $catKey;

            $rowsByAccount = [];

            foreach ((($currentCat ?? [])['accounts'] ?? []) as $row) {
                $id = (int) $row['account']->id;
                $rowsByAccount[$id] = [
                    'account' => $row['account'],
                    'balance' => (float) ($row['balance'] ?? 0),
                    'prior_balance' => 0.0,
                ];
            }

            foreach ((($priorCat ?? [])['accounts'] ?? []) as $row) {
                $id = (int) $row['account']->id;
                if (! isset($rowsByAccount[$id])) {
                    $rowsByAccount[$id] = [
                        'account' => $row['account'],
                        'balance' => 0.0,
                        'prior_balance' => (float) ($row['balance'] ?? 0),
                    ];
                } else {
                    $rowsByAccount[$id]['prior_balance'] = (float) ($row['balance'] ?? 0);
                }
            }

            $accounts = [];
            $subtotalCurrent = 0.0;
            $subtotalPrior = 0.0;

            foreach ($rowsByAccount as $row) {
                $currentDisplay = self::profitLossDisplayAmount($row['balance'], $isIncome);
                $priorDisplay = self::profitLossDisplayAmount($row['prior_balance'], $isIncome);

                if ($hideZeroBalances && $currentDisplay < 0.00001 && $priorDisplay < 0.00001) {
                    continue;
                }

                $row['variance'] = round($currentDisplay - $priorDisplay, 2);
                $accounts[] = $row;
                $subtotalCurrent += (float) $row['balance'];
                $subtotalPrior += (float) $row['prior_balance'];
            }

            usort($accounts, fn (array $a, array $b) => strcmp(
                (string) $a['account']->account_code,
                (string) $b['account']->account_code
            ));

            if ($accounts === [] && $hideZeroBalances) {
                continue;
            }

            $mergedCategories[$catKey] = [
                'label' => $label,
                'accounts' => $accounts,
                'subtotal' => $subtotalCurrent,
                'prior_subtotal' => $subtotalPrior,
                'subtotal_variance' => round(
                    self::profitLossDisplayAmount($subtotalCurrent, $isIncome)
                    - self::profitLossDisplayAmount($subtotalPrior, $isIncome),
                    2
                ),
            ];
        }

        $totalCurrent = (float) ($currentSection['total'] ?? 0);
        $totalPrior = (float) ($priorSection['total'] ?? 0);

        return [
            'by_category' => $mergedCategories,
            'total' => $totalCurrent,
            'prior_total' => $totalPrior,
            'total_variance' => round(
                self::profitLossDisplayAmount($totalCurrent, $isIncome)
                - self::profitLossDisplayAmount($totalPrior, $isIncome),
                2
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $currentSection
     * @param  array<string, mixed>  $priorSection
     * @return array<string, mixed>
     */
    private static function mergeBalanceSheetSection(array $currentSection, array $priorSection): array
    {
        $mergedCategories = [];
        $categoryKeys = array_unique(array_merge(
            array_keys($currentSection['by_category'] ?? []),
            array_keys($priorSection['by_category'] ?? [])
        ));

        foreach ($categoryKeys as $catKey) {
            $currentCat = $currentSection['by_category'][$catKey] ?? null;
            $priorCat = $priorSection['by_category'][$catKey] ?? null;
            $label = $currentCat['label'] ?? $priorCat['label'] ?? (string) $catKey;

            $rowsByKey = [];

            foreach ((($currentCat ?? [])['accounts'] ?? []) as $row) {
                $key = self::balanceSheetRowKey($row);
                $rowsByKey[$key] = array_merge($row, [
                    'balance' => (float) ($row['balance'] ?? 0),
                    'prior_balance' => 0.0,
                    '_prior_bank_breakdown' => null,
                ]);
            }

            foreach ((($priorCat ?? [])['accounts'] ?? []) as $row) {
                $key = self::balanceSheetRowKey($row);
                if (! isset($rowsByKey[$key])) {
                    $rowsByKey[$key] = array_merge($row, [
                        'balance' => 0.0,
                        'prior_balance' => (float) ($row['balance'] ?? 0),
                        'bank_breakdown' => null,
                        '_prior_bank_breakdown' => $row['bank_breakdown'] ?? null,
                    ]);
                } else {
                    $rowsByKey[$key]['prior_balance'] = (float) ($row['balance'] ?? 0);
                    $rowsByKey[$key]['_prior_bank_breakdown'] = $row['bank_breakdown'] ?? null;
                }
            }

            $accounts = [];
            $subtotalCurrent = 0.0;
            $subtotalPrior = 0.0;

            foreach ($rowsByKey as $row) {
                $currentBalance = (float) ($row['balance'] ?? 0);
                $priorBalance = (float) ($row['prior_balance'] ?? 0);
                $priorBankBreakdown = $row['_prior_bank_breakdown'] ?? null;
                unset($row['_prior_bank_breakdown']);
                if (($row['bank_breakdown'] ?? null) !== null || $priorBankBreakdown !== null) {
                    $row['bank_breakdown'] = self::mergeBankBreakdowns(
                        $row['bank_breakdown'] ?? null,
                        $priorBankBreakdown
                    );
                }

                if (abs($currentBalance) < 0.00001 && abs($priorBalance) < 0.00001) {
                    continue;
                }

                $row['variance'] = round($currentBalance - $priorBalance, 2);
                $accounts[] = $row;
                $subtotalCurrent += $currentBalance;
                $subtotalPrior += $priorBalance;
            }

            usort($accounts, function (array $a, array $b) {
                $codeA = ($a['is_computed'] ?? false) ? 'zzz' : (string) ($a['account']->account_code ?? '');
                $codeB = ($b['is_computed'] ?? false) ? 'zzz' : (string) ($b['account']->account_code ?? '');

                return strcmp($codeA, $codeB);
            });

            if ($accounts === []) {
                continue;
            }

            $mergedCategories[$catKey] = [
                'label' => $label,
                'accounts' => $accounts,
                'subtotal' => $subtotalCurrent,
                'prior_subtotal' => $subtotalPrior,
                'subtotal_variance' => round($subtotalCurrent - $subtotalPrior, 2),
            ];
        }

        $totalCurrent = (float) ($currentSection['total'] ?? 0);
        $totalPrior = (float) ($priorSection['total'] ?? 0);

        return [
            'by_category' => $mergedCategories,
            'total' => $totalCurrent,
            'prior_total' => $totalPrior,
            'total_variance' => round($totalCurrent - $totalPrior, 2),
        ];
    }

    /**
     * @param  array{accounts?: list<array<string, mixed>>, unattributed?: float}|null  $current
     * @param  array{accounts?: list<array<string, mixed>>, unattributed?: float}|null  $prior
     * @return array{accounts: list<array<string, mixed>>, unattributed: float, prior_unattributed: float}
     */
    private static function mergeBankBreakdowns(?array $current, ?array $prior): array
    {
        $rows = [];

        foreach ($current['accounts'] ?? [] as $account) {
            $id = (int) $account['account_id'];
            $rows[$id] = array_merge($account, [
                'balance' => (float) ($account['balance'] ?? 0),
                'prior_balance' => 0.0,
            ]);
        }

        foreach ($prior['accounts'] ?? [] as $account) {
            $id = (int) $account['account_id'];
            if (! isset($rows[$id])) {
                $rows[$id] = array_merge($account, [
                    'balance' => 0.0,
                    'prior_balance' => (float) ($account['balance'] ?? 0),
                ]);
            } else {
                $rows[$id]['prior_balance'] = (float) ($account['balance'] ?? 0);
            }
        }

        usort($rows, fn (array $a, array $b) => strcmp(
            (string) ($a['label'] ?? ''),
            (string) ($b['label'] ?? '')
        ));

        return [
            'accounts' => array_values($rows),
            'unattributed' => (float) ($current['unattributed'] ?? 0),
            'prior_unattributed' => (float) ($prior['unattributed'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function balanceSheetRowKey(array $row): string
    {
        if ($row['is_computed'] ?? false) {
            return 'computed:'.($row['label'] ?? '');
        }

        return 'account:'.(int) $row['account']->id;
    }

    public static function profitLossDisplayAmount(float $balance, bool $isIncome): float
    {
        return $isIncome ? abs($balance) : $balance;
    }

    public static function formatVariance(float $variance, bool $parenthesesForNegative = false): string
    {
        if (abs($variance) < 0.00001) {
            return '0.00';
        }

        $formatted = number_format(abs($variance), 2);

        if ($variance > 0) {
            return '+'.$formatted;
        }

        if ($parenthesesForNegative) {
            return '('.$formatted.')';
        }

        return '-'.$formatted;
    }
}
