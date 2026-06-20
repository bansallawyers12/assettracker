<?php

namespace App\Support;

use App\Models\BusinessEntity;
use Illuminate\Support\Collection;

class ReportEntityScopeLabel
{
    /**
     * Human-readable scope for report headers and breadcrumbs.
     */
    public static function format(
        string $scope,
        array $selectedIds,
        Collection $allReportingEntities,
        ?string $allLabel = null,
    ): string {
        $allLabel ??= 'All reporting entities';

        if ($scope !== 'selected') {
            $count = $allReportingEntities->count();

            return $count > 0 ? "{$allLabel} ({$count})" : $allLabel;
        }

        $normalizedIds = array_values(array_unique(array_map('intval', $selectedIds)));
        $selected = $allReportingEntities->filter(
            fn (BusinessEntity $e) => in_array((int) $e->id, $normalizedIds, true)
        )->values();
        $count = $selected->count();

        if ($count === 0) {
            return 'Selected entities';
        }

        if ($count === 1) {
            return $selected->first()->reportPickerLabel();
        }

        $names = $selected->take(2)->map(fn (BusinessEntity $e) => $e->reportPickerLabel())->implode(', ');

        if ($count > 2) {
            return "{$count} entities: {$names}, +".($count - 2).' more';
        }

        return "{$count} entities: {$names}";
    }
}
