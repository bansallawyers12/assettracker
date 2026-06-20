<?php

namespace App\Http\Resources;

use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceYearWorkspaceResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ComplianceYearService $yearService */
        $yearService = app(ComplianceYearService::class);

        $categories = $this->categories
            ->sortBy(fn ($cat) => [$cat->sort_order, $cat->id])
            ->values();

        return [
            'entity_id'       => $this->business_entity_id,
            'asset_id'        => $this->asset_id,
            'fy_start'        => $this->fy_start_date?->toDateString(),
            'fy_end'          => $this->fy_end_date?->toDateString(),
            'fy_label'        => FinancialYear::label($this->fy_start_date),
            'locked'          => $this->isLocked(),
            'notes'           => $this->notes,
            'completeness'    => $yearService->completeness($this->resource),
            'available_years' => $yearService->listAvailableYears(),
            'categories'      => ComplianceCategoryResource::collection($categories)->resolve(),
        ];
    }
}
