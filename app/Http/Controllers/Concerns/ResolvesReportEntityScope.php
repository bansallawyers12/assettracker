<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessEntity;
use App\Support\ReportEntityScopeResolver;
use Illuminate\Http\Request;

trait ResolvesReportEntityScope
{
    /**
     * @return array<int>|null null = invalid “selected” with no entities
     */
    protected function resolveReportEntityIds(Request $request): ?array
    {
        $allowed = BusinessEntity::forFinancialReports()
            ->orderBy('legal_name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return ReportEntityScopeResolver::resolve(
            $request->input('scope'),
            (array) $request->input('entity_ids', []),
            $allowed
        );
    }

    /**
     * @param  array<int>  $resolvedEntityIds
     */
    protected function mergeReportFormScope(array $report, Request $request, array $resolvedEntityIds): array
    {
        return array_merge($report, ReportEntityScopeResolver::formState(
            $request->input('scope'),
            $resolvedEntityIds
        ));
    }
}
