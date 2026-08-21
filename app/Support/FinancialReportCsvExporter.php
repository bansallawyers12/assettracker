<?php

namespace App\Support;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportCsvExporter
{
    public const SUPPORTING_ENTRIES_EPOCH = '1970-01-01';

    public function __construct(private FinancialReportService $financialReportService) {}

    /**
     * @param  array<string, mixed>  $report
     */
    public function profitLoss(array $report): StreamedResponse
    {
        $start = Carbon::parse($report['period']['start_date'])->toDateString();
        $end = Carbon::parse($report['period']['end_date'])->toDateString();
        $filename = 'profit-loss-'.$start.'-to-'.$end.'.csv';
        $comparing = ComparativeFinancialReport::isEnabled($report['compare'] ?? null);

        return $this->streamDownload($filename, function ($out) use ($report, $start, $end, $comparing) {
            $this->writeMeta($out, 'Profit & Loss', $report, [
                'Period' => ComparativeFinancialReport::periodColumnLabel($start, $end),
            ]);

            if ($comparing) {
                $this->writeRow($out, ['Compare', $report['comparison']['prior_label'] ?? 'Prior year']);
            }

            $this->writeBlank($out);
            $this->writeSection($out, 'Statement');

            if ($comparing) {
                $this->writeRow($out, [
                    'Section',
                    'Category',
                    'Account code',
                    'Account name',
                    $report['comparison']['current_label'] ?? 'Current',
                    $report['comparison']['prior_label'] ?? 'Prior',
                    '$ Change',
                ]);
            } else {
                $this->writeRow($out, ['Section', 'Category', 'Account code', 'Account name', 'Amount']);
            }

            $this->writeProfitLossSection($out, 'Income', $report['income'] ?? [], true, $comparing);
            $this->writeProfitLossTotals($out, 'Total Income', $report['income'] ?? [], true, $comparing);

            $this->writeProfitLossSection($out, 'Expenses', $report['expenses'] ?? [], false, $comparing);
            $this->writeProfitLossTotals($out, 'Total Expenses', $report['expenses'] ?? [], false, $comparing);

            $net = (float) ($report['net_profit'] ?? 0);
            $priorNet = (float) ($report['prior_net_profit'] ?? 0);
            $variance = (float) ($report['net_profit_variance'] ?? ($net - $priorNet));
            $label = $net >= 0 ? 'Net Profit' : 'Net Loss';

            if ($comparing) {
                $this->writeRow($out, [
                    $label,
                    '',
                    '',
                    '',
                    $this->formatMoney($net, parenthesesForNegative: true),
                    $this->formatMoney($priorNet, parenthesesForNegative: true),
                    ComparativeFinancialReport::formatVariance($variance, true),
                ]);
            } else {
                $this->writeRow($out, [$label, '', '', '', $this->formatMoney($net, parenthesesForNegative: true)]);
            }

            $this->writeEntityBreakdownProfitLoss($out, $report);

            $accountIds = $this->profitLossAccountIds($report);
            $this->writeSupportingEntries(
                $out,
                $report,
                $start,
                $end,
                $accountIds,
                'Supporting entries (current period)'
            );

            if ($comparing) {
                [$priorStart, $priorEnd] = isset($report['comparison']['prior_period']['start_date'], $report['comparison']['prior_period']['end_date'])
                    ? [
                        Carbon::parse($report['comparison']['prior_period']['start_date'])->toDateString(),
                        Carbon::parse($report['comparison']['prior_period']['end_date'])->toDateString(),
                    ]
                    : ComparativeFinancialReport::priorYearPeriod($start, $end);

                $this->writeSupportingEntries(
                    $out,
                    $report,
                    $priorStart,
                    $priorEnd,
                    $accountIds,
                    'Supporting entries (prior period)'
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function balanceSheet(array $report): StreamedResponse
    {
        $asOf = Carbon::parse($report['as_of_date'])->toDateString();
        $filename = 'balance-sheet-as-at-'.$asOf.'.csv';
        $comparing = ComparativeFinancialReport::isEnabled($report['compare'] ?? null);

        return $this->streamDownload($filename, function ($out) use ($report, $asOf, $comparing) {
            $this->writeMeta($out, 'Balance Sheet', $report, [
                'As at' => Carbon::parse($asOf)->format('j M Y'),
            ]);

            if ($comparing) {
                $this->writeRow($out, ['Compare', $report['comparison']['prior_label'] ?? 'Prior year']);
            }

            $this->writeBlank($out);
            $this->writeSection($out, 'Statement');

            if ($comparing) {
                $this->writeRow($out, [
                    'Section',
                    'Category',
                    'Account code',
                    'Account name',
                    $report['comparison']['current_label'] ?? 'Current',
                    $report['comparison']['prior_label'] ?? 'Prior',
                    'Movement',
                ]);
            } else {
                $this->writeRow($out, ['Section', 'Category', 'Account code', 'Account name', 'Amount (debit - credit)']);
            }

            $this->writeBalanceSheetSection($out, 'Assets', $report['assets'] ?? [], $comparing);
            $this->writeBalanceSheetTotalRow($out, 'Total Assets', (float) ($report['total_assets'] ?? 0), (float) ($report['prior_total_assets'] ?? 0), (float) ($report['total_assets_variance'] ?? 0), $comparing);

            $this->writeBalanceSheetSection($out, 'Liabilities', $report['liabilities'] ?? [], $comparing);
            $this->writeBalanceSheetTotalRow(
                $out,
                'Total Liabilities',
                (float) (($report['liabilities']['total'] ?? 0)),
                (float) (($report['liabilities']['prior_total'] ?? 0)),
                (float) (($report['liabilities']['total_variance'] ?? 0)),
                $comparing
            );

            $this->writeBalanceSheetSection($out, 'Equity', $report['equity'] ?? [], $comparing);
            $this->writeBalanceSheetTotalRow(
                $out,
                'Total Equity',
                (float) (($report['equity']['total'] ?? 0)),
                (float) (($report['equity']['prior_total'] ?? 0)),
                (float) (($report['equity']['total_variance'] ?? 0)),
                $comparing
            );

            $this->writeBalanceSheetTotalRow(
                $out,
                'Total Liabilities & Equity',
                (float) ($report['total_liabilities_equity'] ?? 0),
                (float) ($report['prior_total_liabilities_equity'] ?? 0),
                (float) ($report['total_liabilities_equity_variance'] ?? 0),
                $comparing
            );

            $this->writeEntityBreakdownBalanceSheet($out, $report);

            $accountIds = $this->balanceSheetAccountIds($report);
            $this->writeSupportingEntries(
                $out,
                $report,
                self::SUPPORTING_ENTRIES_EPOCH,
                $asOf,
                $accountIds,
                'Supporting entries (all posted lines through as-at date)'
            );

            if ($comparing) {
                $priorAsOf = Carbon::parse(
                    $report['comparison']['prior_as_of_date']
                        ?? ComparativeFinancialReport::priorYearAsOf($asOf)
                )->toDateString();
                $this->writeSupportingEntries(
                    $out,
                    $report,
                    self::SUPPORTING_ENTRIES_EPOCH,
                    $priorAsOf,
                    $accountIds,
                    'Supporting entries (all posted lines through prior as-at date)'
                );
            }
        });
    }

    /**
     * @param  callable(resource): void  $callback
     */
    private function streamDownload(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM helps Excel open CSV correctly.
            fwrite($out, "\xEF\xBB\xBF");
            $callback($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $report
     * @param  array<string, string>  $extra
     */
    private function writeMeta($out, string $title, array $report, array $extra = []): void
    {
        $this->writeRow($out, ['Report', $title]);
        $this->writeRow($out, ['Generated', now()->toDateTimeString()]);
        $this->writeRow($out, ['Entity scope', $this->entityScopeLabel($report)]);

        foreach ($extra as $key => $value) {
            $this->writeRow($out, [$key, $value]);
        }

        $this->writeRow($out, [
            'Note',
            'Amounts come from posted journal entries. Supporting entries list the journal lines behind each statement account (including bank account and type when known). Balance sheet supporting entries include all history through the as-at date. When Compare is enabled, both current and prior periods are included.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function entityScopeLabel(array $report): string
    {
        $entities = collect($report['business_entities'] ?? []);
        $count = $entities->count();

        if (($report['is_consolidated'] ?? false) || $count > 1) {
            // Avoid enormous CSV cells when exporting many entities.
            if ($count > 8) {
                return 'Consolidated — '.$count.' reporting entities';
            }

            return 'Consolidated — '.$entities->pluck('legal_name')->implode(', ');
        }

        $entity = $report['business_entity'] ?? $entities->first();

        return $entity?->legal_name ?? 'Unknown';
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $section
     */
    private function writeProfitLossSection($out, string $sectionLabel, array $section, bool $isIncome, bool $comparing): void
    {
        foreach ($section['by_category'] ?? [] as $catGroup) {
            foreach ($catGroup['accounts'] ?? [] as $row) {
                $account = $row['account'];
                $current = ComparativeFinancialReport::profitLossDisplayAmount((float) ($row['balance'] ?? 0), $isIncome);
                $prior = ComparativeFinancialReport::profitLossDisplayAmount((float) ($row['prior_balance'] ?? 0), $isIncome);
                $variance = (float) ($row['variance'] ?? ($current - $prior));

                if ($comparing) {
                    $this->writeRow($out, [
                        $sectionLabel,
                        $catGroup['label'] ?? '',
                        $account->account_code,
                        $account->account_name,
                        number_format($current, 2, '.', ''),
                        number_format($prior, 2, '.', ''),
                        ComparativeFinancialReport::formatVariance($variance),
                    ]);
                } else {
                    $this->writeRow($out, [
                        $sectionLabel,
                        $catGroup['label'] ?? '',
                        $account->account_code,
                        $account->account_name,
                        number_format($current, 2, '.', ''),
                    ]);
                }
            }

            $subCurrent = ComparativeFinancialReport::profitLossDisplayAmount((float) ($catGroup['subtotal'] ?? 0), $isIncome);
            $subPrior = ComparativeFinancialReport::profitLossDisplayAmount((float) ($catGroup['prior_subtotal'] ?? 0), $isIncome);
            $subVariance = (float) ($catGroup['subtotal_variance'] ?? ($subCurrent - $subPrior));

            if ($comparing) {
                $this->writeRow($out, [
                    $sectionLabel,
                    'Total '.($catGroup['label'] ?? ''),
                    '',
                    '',
                    number_format($subCurrent, 2, '.', ''),
                    number_format($subPrior, 2, '.', ''),
                    ComparativeFinancialReport::formatVariance($subVariance),
                ]);
            } else {
                $this->writeRow($out, [
                    $sectionLabel,
                    'Total '.($catGroup['label'] ?? ''),
                    '',
                    '',
                    number_format($subCurrent, 2, '.', ''),
                ]);
            }
        }
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $section
     */
    private function writeProfitLossTotals($out, string $label, array $section, bool $isIncome, bool $comparing): void
    {
        $current = ComparativeFinancialReport::profitLossDisplayAmount((float) ($section['total'] ?? 0), $isIncome);
        $prior = ComparativeFinancialReport::profitLossDisplayAmount((float) ($section['prior_total'] ?? 0), $isIncome);
        $variance = (float) ($section['total_variance'] ?? ($current - $prior));

        if ($comparing) {
            $this->writeRow($out, [
                $label,
                '',
                '',
                '',
                number_format($current, 2, '.', ''),
                number_format($prior, 2, '.', ''),
                ComparativeFinancialReport::formatVariance($variance),
            ]);
        } else {
            $this->writeRow($out, [$label, '', '', '', number_format($current, 2, '.', '')]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $section
     */
    private function writeBalanceSheetSection($out, string $sectionLabel, array $section, bool $comparing): void
    {
        foreach ($section['by_category'] ?? [] as $catGroup) {
            foreach ($catGroup['accounts'] ?? [] as $row) {
                $isComputed = (bool) ($row['is_computed'] ?? false);
                $code = $isComputed ? '' : (string) ($row['account']->account_code ?? '');
                $name = $isComputed
                    ? (string) ($row['label'] ?? 'Computed')
                    : (string) ($row['account']->account_name ?? '');
                $current = (float) ($row['balance'] ?? 0);
                $prior = (float) ($row['prior_balance'] ?? 0);
                $variance = (float) ($row['variance'] ?? ($current - $prior));

                if ($comparing) {
                    $this->writeRow($out, [
                        $sectionLabel,
                        $catGroup['label'] ?? '',
                        $code,
                        $name,
                        $this->formatSigned($current),
                        $this->formatSigned($prior),
                        ComparativeFinancialReport::formatVariance($variance),
                    ]);
                } else {
                    $this->writeRow($out, [
                        $sectionLabel,
                        $catGroup['label'] ?? '',
                        $code,
                        $name,
                        $this->formatSigned($current),
                    ]);
                }

                foreach ($row['bank_breakdown']['accounts'] ?? [] as $bankRow) {
                    $bankName = trim(
                        (string) ($bankRow['label'] ?? '')
                        .' · '
                        .(string) ($bankRow['purpose'] ?? '')
                    );
                    $bankCurrent = (float) ($bankRow['balance'] ?? 0);
                    $bankPrior = (float) ($bankRow['prior_balance'] ?? 0);

                    $cells = [
                        $sectionLabel,
                        $catGroup['label'] ?? '',
                        '',
                        $bankName,
                        $this->formatSigned($bankCurrent),
                    ];
                    if ($comparing) {
                        $cells[] = $this->formatSigned($bankPrior);
                        $cells[] = ComparativeFinancialReport::formatVariance($bankCurrent - $bankPrior);
                    }
                    $this->writeRow($out, $cells);
                }

                if (isset($row['bank_breakdown'])) {
                    $unattributed = (float) ($row['bank_breakdown']['unattributed'] ?? 0);
                    $priorUnattributed = (float) ($row['bank_breakdown']['prior_unattributed'] ?? 0);
                    if (abs($unattributed) >= 0.005 || ($comparing && abs($priorUnattributed) >= 0.005)) {
                        $cells = [
                            $sectionLabel,
                            $catGroup['label'] ?? '',
                            '',
                            'Unallocated / reconciliation difference',
                            $this->formatSigned($unattributed),
                        ];
                        if ($comparing) {
                            $cells[] = $this->formatSigned($priorUnattributed);
                            $cells[] = ComparativeFinancialReport::formatVariance(
                                $unattributed - $priorUnattributed
                            );
                        }
                        $this->writeRow($out, $cells);
                    }
                }
            }

            $subCurrent = (float) ($catGroup['subtotal'] ?? 0);
            $subPrior = (float) ($catGroup['prior_subtotal'] ?? 0);
            $subVariance = (float) ($catGroup['subtotal_variance'] ?? ($subCurrent - $subPrior));

            if ($comparing) {
                $this->writeRow($out, [
                    $sectionLabel,
                    'Total '.($catGroup['label'] ?? ''),
                    '',
                    '',
                    $this->formatSigned($subCurrent),
                    $this->formatSigned($subPrior),
                    ComparativeFinancialReport::formatVariance($subVariance),
                ]);
            } else {
                $this->writeRow($out, [
                    $sectionLabel,
                    'Total '.($catGroup['label'] ?? ''),
                    '',
                    '',
                    $this->formatSigned($subCurrent),
                ]);
            }
        }
    }

    /**
     * @param  resource  $out
     */
    private function writeBalanceSheetTotalRow(
        $out,
        string $label,
        float $current,
        float $prior,
        float $variance,
        bool $comparing
    ): void {
        if ($comparing) {
            $this->writeRow($out, [
                $label,
                '',
                '',
                '',
                $this->formatSigned($current),
                $this->formatSigned($prior),
                ComparativeFinancialReport::formatVariance($variance),
            ]);
        } else {
            $this->writeRow($out, [$label, '', '', '', $this->formatSigned($current)]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $report
     */
    private function writeEntityBreakdownProfitLoss($out, array $report): void
    {
        $breakdown = $report['entity_breakdown'] ?? null;
        $entities = $breakdown['entities'] ?? collect();
        if (! ($report['is_consolidated'] ?? false) || $entities->isEmpty()) {
            return;
        }

        $this->writeBlank($out);
        $this->writeSection($out, 'By entity');
        $this->writeRow($out, ['Entity', 'Income', 'Expenses', 'Net profit / loss']);

        foreach ($entities as $entity) {
            $col = $breakdown['columns'][$entity->id] ?? [];
            $this->writeRow($out, [
                $entity->legal_name,
                number_format((float) ($col['income'] ?? 0), 2, '.', ''),
                number_format((float) ($col['expenses'] ?? 0), 2, '.', ''),
                $this->formatMoney((float) ($col['net_profit'] ?? 0), parenthesesForNegative: true),
            ]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $report
     */
    private function writeEntityBreakdownBalanceSheet($out, array $report): void
    {
        $breakdown = $report['entity_breakdown'] ?? null;
        $entities = $breakdown['entities'] ?? collect();
        if (! ($report['is_consolidated'] ?? false) || $entities->isEmpty()) {
            return;
        }

        $this->writeBlank($out);
        $this->writeSection($out, 'By entity');
        $this->writeRow($out, ['Entity', 'Bank / cash', 'Total assets', 'Total liabilities & equity']);

        foreach ($entities as $entity) {
            $col = $breakdown['columns'][$entity->id] ?? [];
            $bank = $col['bank_cash'] ?? null;
            $this->writeRow($out, [
                $entity->legal_name,
                $bank === null ? '' : $this->formatSigned((float) $bank),
                $this->formatSigned((float) ($col['total_assets'] ?? 0)),
                $this->formatSigned((float) ($col['total_liabilities_equity'] ?? 0)),
            ]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array<string, mixed>  $report
     * @param  list<int>  $accountIds
     */
    private function writeSupportingEntries(
        $out,
        array $report,
        string $startDate,
        string $endDate,
        array $accountIds,
        string $sectionTitle = 'Supporting entries'
    ): void {
        $this->writeBlank($out);
        $this->writeSection($out, $sectionTitle);
        $this->writeRow($out, [
            'Account code',
            'Account name',
            'Date',
            'Reference',
            'Description',
            'Entity',
            'Bank account',
            'Type',
            'Debit',
            'Credit',
            'Running balance',
        ]);

        if ($accountIds === []) {
            $this->writeRow($out, ['(No supporting account lines for this report)']);

            return;
        }

        $entityIds = collect($report['business_entities'] ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($entityIds === []) {
            $this->writeRow($out, ['(No entities in scope for supporting entries)']);

            return;
        }

        $txReport = $this->financialReportService->generateAccountTransactions(
            $entityIds,
            $startDate,
            $endDate,
            $accountIds
        );

        $wroteAny = false;

        foreach ($txReport['accounts'] ?? [] as $block) {
            $account = $block['account'] ?? null;
            if ($account === null) {
                continue;
            }

            $wroteAny = true;
            $opening = (float) ($block['opening_balance'] ?? 0);

            $this->writeRow($out, [
                $account->account_code,
                $account->account_name,
                $startDate,
                '',
                'Opening balance',
                '',
                '',
                '',
                '',
                '',
                number_format($opening, 2, '.', ''),
            ]);

            foreach ($block['lines'] ?? [] as $line) {
                $debit = $line['debit'] ?? null;
                $credit = $line['credit'] ?? null;

                $this->writeRow($out, [
                    $account->account_code,
                    $account->account_name,
                    $this->formatLineDate($line['date'] ?? null),
                    (string) ($line['reference'] ?? ''),
                    (string) ($line['description'] ?? ''),
                    (string) ($line['entity_name'] ?? $line['booking_entity_name'] ?? ''),
                    (string) ($line['bank_account'] ?? ''),
                    (string) ($line['transaction_type'] ?? ''),
                    $debit !== null ? number_format((float) $debit, 2, '.', '') : '',
                    $credit !== null ? number_format((float) $credit, 2, '.', '') : '',
                    number_format((float) ($line['running_balance'] ?? 0), 2, '.', ''),
                ]);
            }
        }

        if (! $wroteAny) {
            $this->writeRow($out, ['(No posted journal activity for these accounts in this period)']);
        }
    }

    private function formatLineDate(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        $raw = trim((string) $date);
        if ($raw === '') {
            return '';
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return $raw;
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<int>
     */
    private function profitLossAccountIds(array $report): array
    {
        $ids = [];
        foreach (['income', 'expenses'] as $sectionKey) {
            foreach ($report[$sectionKey]['by_category'] ?? [] as $catGroup) {
                foreach ($catGroup['accounts'] ?? [] as $row) {
                    if (isset($row['account']->id)) {
                        $ids[] = (int) $row['account']->id;
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<int>
     */
    private function balanceSheetAccountIds(array $report): array
    {
        $ids = [];
        foreach (['assets', 'liabilities', 'equity'] as $sectionKey) {
            foreach ($report[$sectionKey]['by_category'] ?? [] as $catGroup) {
                foreach ($catGroup['accounts'] ?? [] as $row) {
                    if (($row['is_computed'] ?? false) || ! isset($row['account']->id)) {
                        continue;
                    }
                    $ids[] = (int) $row['account']->id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  resource  $out
     * @param  list<string|int|float|null>  $cells
     */
    private function writeRow($out, array $cells): void
    {
        fputcsv($out, array_map(fn ($v) => $v === null ? '' : (string) $v, $cells));
    }

    /**
     * @param  resource  $out
     */
    private function writeBlank($out): void
    {
        fputcsv($out, []);
    }

    /**
     * @param  resource  $out
     */
    private function writeSection($out, string $title): void
    {
        $this->writeRow($out, ['SECTION', $title]);
    }

    private function formatSigned(float $value): string
    {
        if (abs($value) < 0.00001) {
            return '0.00';
        }

        $formatted = number_format(abs($value), 2, '.', '');

        return $value > 0 ? '+'.$formatted : '-'.$formatted;
    }

    private function formatMoney(float $value, bool $parenthesesForNegative = false): string
    {
        if (abs($value) < 0.00001) {
            return '0.00';
        }

        $formatted = number_format(abs($value), 2, '.', '');
        if ($value < 0 && $parenthesesForNegative) {
            return '('.$formatted.')';
        }

        return $value < 0 ? '-'.$formatted : $formatted;
    }
}
