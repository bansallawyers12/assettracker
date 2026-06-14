<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Services\CarReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarReportController extends Controller
{
    public function __construct(
        protected CarReportService $carReportService
    ) {}

    public function carRegister(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Asset::class);

        $entityIds = $this->resolveReportEntityIds($request);

        if ($entityIds === null) {
            return redirect()
                ->route('financial-reports.car-register', $request->except('entity_ids'))
                ->with('error', 'Choose at least one entity, or select "All reporting entities".');
        }

        $report = $this->carReportService->carRegister(
            $entityIds === [] ? null : $entityIds
        );

        $businessEntities = BusinessEntity::forFinancialReports()->orderBy('legal_name')->get();
        $formsScope = $request->input('scope') === 'selected' ? 'selected' : 'all';
        $formsEntityIds = $formsScope === 'selected' ? ($entityIds ?? []) : [];

        return view('car-reports.car-register', compact(
            'report',
            'businessEntities',
            'formsScope',
            'formsEntityIds'
        ));
    }

    /**
     * Resolve which entity IDs to include in the report.
     * Returns null when "selected" scope was chosen but no valid entities were ticked.
     *
     * @return array<int>|null
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
}
