<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportEntityScope;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Services\CarReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarReportController extends Controller
{
    use ResolvesReportEntityScope;

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
}
