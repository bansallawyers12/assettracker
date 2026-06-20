<?php

namespace App\Support;

use App\Models\BusinessEntity;
use Illuminate\Support\Collection;

/**
 * Stateless resolver for report entity scope (testable without HTTP/controllers).
 */
class ReportEntityScopeResolver
{
    /**
     * @param  array<int>  $allowedIds
     * @return array<int>|null null when selected scope has no valid entities
     */
    public static function resolve(?string $scope, array $requestedIds, array $allowedIds): ?array
    {
        if ($allowedIds === []) {
            return [];
        }

        if ($scope === 'selected') {
            $requested = array_values(array_unique(array_map('intval', $requestedIds)));
            $requested = array_values(array_intersect($requested, $allowedIds));

            return $requested === [] ? null : $requested;
        }

        return $allowedIds;
    }

    /**
     * @param  array<int>  $resolvedEntityIds
     * @return array{forms_scope: string, forms_entity_ids: array<int>}
     */
    public static function formState(?string $scope, array $resolvedEntityIds): array
    {
        if ($scope === 'selected') {
            return [
                'forms_scope' => 'selected',
                'forms_entity_ids' => $resolvedEntityIds,
            ];
        }

        return [
            'forms_scope' => 'all',
            'forms_entity_ids' => [],
        ];
    }
}
