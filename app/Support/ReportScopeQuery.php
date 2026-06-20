<?php

namespace App\Support;

class ReportScopeQuery
{
    /**
     * Build query parameters for report URLs preserving entity scope.
     *
     * @param  array<int>  $entityIds
     * @return array<string, mixed>
     */
    public static function build(string $scope, array $entityIds = [], array $merge = []): array
    {
        $q = array_merge($merge, ['scope' => $scope]);

        if ($scope === 'selected') {
            foreach ($entityIds as $id) {
                $q['entity_ids'][] = (int) $id;
            }
        }

        return $q;
    }
}
