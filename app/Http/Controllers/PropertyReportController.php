<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Services\PropertyReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyReportController extends Controller
{
    public function __construct(
        protected PropertyReportService $propertyReportService
    ) {}

    public function show(BusinessEntity $businessEntity, Asset $asset, Request $request): View
    {
        $this->ensureAssetBelongsToBusinessEntity($businessEntity, $asset);
        $this->authorize('view', $asset);

        if (! in_array($asset->asset_type, Asset::LEASABLE_ASSET_TYPES, true)) {
            abort(404, 'Financial reports are only available for property assets.');
        }

        $basis = $request->get('basis', PropertyReportService::BASIS_CASH);
        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfYear()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfYear()))->toDateString();

        $report = $this->propertyReportService->propertyProfitLoss($asset, $startDate, $endDate, $basis);

        return view('property-reports.financials', compact('report', 'businessEntity', 'asset', 'basis', 'startDate', 'endDate'));
    }

    public function portfolio(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $entityIds = $this->resolveReportEntityIds($request);
        if ($entityIds === null) {
            return redirect()
                ->route('portfolio.index', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select “All reporting entities”.');
        }

        $basis = $request->get('basis', PropertyReportService::BASIS_CASH);
        $startDate = Carbon::parse($request->get('start_date', now()->copy()->startOfYear()))->toDateString();
        $endDate = Carbon::parse($request->get('end_date', now()->copy()->endOfYear()))->toDateString();
        $showDisposed = $request->boolean('show_disposed');

        $report = $this->propertyReportService->portfolio(
            $entityIds === [] ? null : $entityIds,
            $startDate,
            $endDate,
            $basis,
            $showDisposed
        );

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? ($entityIds ?? []) : [];

        return view('property-reports.portfolio', compact(
            'report',
            'businessEntities',
            'basis',
            'startDate',
            'endDate',
            'showDisposed',
            'formsScope',
            'formsEntityIds'
        ));
    }

    /**
     * @return array<int>|null null = invalid selected scope with no entities
     */
    protected function resolveReportEntityIds(Request $request): ?array
    {
        $allowed = BusinessEntity::forFinancialReports()
            ->orderBy('legal_name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($allowed === []) {
            return [];
        }

        if ($request->input('scope') === 'selected') {
            $requested = array_values(array_unique(array_map('intval', (array) $request->input('entity_ids', []))));
            $requested = array_values(array_intersect($requested, $allowed));

            return $requested === [] ? null : $requested;
        }

        return $allowed;
    }

    protected function ensureAssetBelongsToBusinessEntity(BusinessEntity $businessEntity, Asset $asset): void
    {
        if ((int) $asset->business_entity_id !== (int) $businessEntity->id) {
            abort(404);
        }
    }
}
