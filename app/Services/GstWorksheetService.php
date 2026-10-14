<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GstWorksheetService
{
    public const LAYOUT_QUARTERLY = 'quarterly';

    public const LAYOUT_ANNUAL = 'annual';

    public function __construct(private FinancialReportService $financialReportService) {}

    /**
     * @param  array<int>  $entityIds
     * @return array<string, mixed>
     */
    public function generate(array $entityIds, Carbon $fyStart, bool $includeUnregistered, ?string $requestedLayout): array
    {
        $period = FinancialYear::forDate($fyStart);
        $periods = $this->periods($period['start'], $period['end']);

        $scoped = BusinessEntity::query()
            ->whereIn('id', $entityIds)
            ->orderBy('legal_name')
            ->get();

        $layout = $this->resolveLayout($scoped, $requestedLayout);

        $displayed = $includeUnregistered
            ? $scoped
            : $scoped->filter(fn (BusinessEntity $entity) => $entity->isGstRegistered())->values();

        $entityRows = [];
        foreach ($displayed as $entity) {
            $entityRows[] = $this->entityRow($entity, $periods);
        }

        $portfolio = count($entityRows) > 1
            ? $this->portfolio($entityRows, $periods)
            : null;

        return [
            'layout' => $layout,
            'include_unregistered' => $includeUnregistered,
            'financial_year' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->copy()->startOfDay()->toDateString(),
                'label' => FinancialYear::label($period['start']),
            ],
            'periods' => $periods,
            'visible_period_keys' => $layout === self::LAYOUT_ANNUAL
                ? ['fy']
                : ['q1', 'q2', 'q3', 'q4', 'fy'],
            'rows' => $this->rowDefinitions($displayed),
            'entities' => $entityRows,
            'portfolio' => $portfolio,
            'business_entities' => $scoped,
            'is_consolidated' => $scoped->count() > 1,
            'business_entity' => $scoped->count() === 1 ? $scoped->first() : null,
        ];
    }

    /**
     * View columns follow the requested layout when it is quarterly or annual.
     * Otherwise one shared lodgement cycle opens in that cycle, and mixed cycles
     * open quarterly. Monthly lodgers use the quarterly cycle.
     *
     * @param  Collection<int, BusinessEntity>  $entities
     */
    public function resolveLayout(Collection $entities, ?string $requested): string
    {
        if ($requested === self::LAYOUT_QUARTERLY || $requested === self::LAYOUT_ANNUAL) {
            return $requested;
        }

        $cycles = $entities
            ->map(fn (BusinessEntity $entity) => $entity->effectiveBasReportingFrequency())
            ->unique()
            ->values();

        if ($cycles->count() === 1 && $cycles->first() === self::LAYOUT_ANNUAL) {
            return self::LAYOUT_ANNUAL;
        }

        return self::LAYOUT_QUARTERLY;
    }

    /**
     * @param  list<array{key: string, label: string, start: string, end: string}>  $periods
     * @return array<string, mixed>
     */
    private function entityRow(BusinessEntity $entity, array $periods): array
    {
        $figures = [];
        foreach ($periods as $period) {
            $figures[$period['key']] = $this->figuresFor($entity, $period['start'], $period['end']);
        }

        $frequency = $entity->effectiveBasReportingFrequency();

        return [
            'entity' => $entity,
            'gst_registered' => $entity->isGstRegistered(),
            'lodgement_frequency' => $frequency,
            'lodgement_label' => $frequency === self::LAYOUT_ANNUAL ? 'Lodges annually' : 'Lodges quarterly',
            'periods' => $figures,
        ];
    }

    /**
     * @return array<string, float|null>
     */
    private function figuresFor(BusinessEntity $entity, string $start, string $end): array
    {
        $activity = $this->financialReportService->paidTransactionGstActivity((int) $entity->id, $start, $end);
        $payg = $this->financialReportService->paidPaygAmount((int) $entity->id, $start, $end);

        if ($entity->isGstRegistered()) {
            $net = round($activity['collected'] - $activity['credits'], 2);

            return [
                'sales_ex_gst' => $activity['sales_ex_gst'],
                'purchases_ex_gst' => $activity['purchases_ex_gst'],
                'gst_collected' => $activity['collected'],
                'gst_credits' => $activity['credits'],
                'net_gst' => $net,
                'payg_paid' => $payg,
                'total_bas' => round($net + $payg, 2),
                'gst_recorded_warning' => null,
            ];
        }

        $recorded = round($activity['collected'] + $activity['credits'], 2);

        return [
            'sales_ex_gst' => $activity['sales_ex_gst'],
            'purchases_ex_gst' => $activity['purchases_ex_gst'],
            'gst_collected' => null,
            'gst_credits' => null,
            'net_gst' => null,
            'payg_paid' => $payg,
            'total_bas' => null,
            'gst_recorded_warning' => $recorded > 0 ? $recorded : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entityRows
     * @param  list<array{key: string, label: string, start: string, end: string}>  $periods
     * @return array{periods: array<string, array<string, float|null>>}
     */
    private function portfolio(array $entityRows, array $periods): array
    {
        $totals = [];

        foreach ($periods as $period) {
            $key = $period['key'];
            $bucket = [
                'sales_ex_gst' => 0.0,
                'purchases_ex_gst' => 0.0,
                'gst_collected' => 0.0,
                'gst_credits' => 0.0,
                'net_gst' => 0.0,
                'payg_paid' => 0.0,
                'total_bas' => 0.0,
                'gst_recorded_warning' => 0.0,
            ];
            $hasRegisteredGst = false;
            $hasWarning = false;

            foreach ($entityRows as $row) {
                $figures = $row['periods'][$key];
                $bucket['sales_ex_gst'] += (float) $figures['sales_ex_gst'];
                $bucket['purchases_ex_gst'] += (float) $figures['purchases_ex_gst'];
                $bucket['payg_paid'] += (float) $figures['payg_paid'];

                if ($row['gst_registered']) {
                    $hasRegisteredGst = true;
                    $bucket['gst_collected'] += (float) $figures['gst_collected'];
                    $bucket['gst_credits'] += (float) $figures['gst_credits'];
                    $bucket['net_gst'] += (float) $figures['net_gst'];
                    $bucket['total_bas'] += (float) $figures['total_bas'];
                }

                if ($figures['gst_recorded_warning'] !== null) {
                    $hasWarning = true;
                    $bucket['gst_recorded_warning'] += (float) $figures['gst_recorded_warning'];
                }
            }

            foreach (['sales_ex_gst', 'purchases_ex_gst', 'payg_paid'] as $metric) {
                $bucket[$metric] = round($bucket[$metric], 2);
            }

            if ($hasRegisteredGst) {
                foreach (['gst_collected', 'gst_credits', 'net_gst', 'total_bas'] as $metric) {
                    $bucket[$metric] = round($bucket[$metric], 2);
                }
            } else {
                $bucket['gst_collected'] = null;
                $bucket['gst_credits'] = null;
                $bucket['net_gst'] = null;
                $bucket['total_bas'] = null;
            }

            $bucket['gst_recorded_warning'] = $hasWarning
                ? round($bucket['gst_recorded_warning'], 2)
                : null;

            $totals[$key] = $bucket;
        }

        return ['periods' => $totals];
    }

    /**
     * @param  Collection<int, BusinessEntity>  $displayed
     * @return list<array{key: string, label: string, warning?: bool}>
     */
    private function rowDefinitions(Collection $displayed): array
    {
        $rows = [
            ['key' => 'sales_ex_gst', 'label' => 'Sales (ex GST)'],
            ['key' => 'purchases_ex_gst', 'label' => 'Purchases (ex GST)'],
            ['key' => 'gst_collected', 'label' => 'GST collected (1A)'],
            ['key' => 'gst_credits', 'label' => 'GST credits (1B)'],
            ['key' => 'net_gst', 'label' => 'Net GST payable or refundable'],
            ['key' => 'payg_paid', 'label' => 'PAYG paid'],
            ['key' => 'total_bas', 'label' => 'Total BAS (net GST + PAYG)'],
        ];

        if ($displayed->contains(fn (BusinessEntity $entity) => ! $entity->isGstRegistered())) {
            $rows[] = [
                'key' => 'gst_recorded_warning',
                'label' => 'GST recorded on transactions',
                'warning' => true,
            ];
        }

        return $rows;
    }

    /**
     * Australian financial-year quarters, then the full year.
     *
     * @return list<array{key: string, label: string, start: string, end: string}>
     */
    private function periods(Carbon $start, Carbon $end): array
    {
        $periods = [];
        $cursor = $start->copy()->startOfDay();

        foreach (['q1', 'q2', 'q3', 'q4'] as $index => $key) {
            $quarterStart = $cursor->copy();
            $quarterEnd = $cursor->copy()->addMonths(3)->subDay();
            $periods[] = [
                'key' => $key,
                'label' => 'Q'.($index + 1).' '.$quarterStart->format('M').'–'.$quarterEnd->format('M'),
                'start' => $quarterStart->toDateString(),
                'end' => $quarterEnd->toDateString(),
            ];
            $cursor->addMonths(3);
        }

        $periods[] = [
            'key' => 'fy',
            'label' => 'FY total',
            'start' => $start->copy()->startOfDay()->toDateString(),
            'end' => $end->copy()->startOfDay()->toDateString(),
        ];

        return $periods;
    }
}
