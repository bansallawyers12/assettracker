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

        $files = $this->files->sortBy(fn ($f) => [$f->type?->sort_order ?? 0, $f->type?->id ?? 0])->values();

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
            'files'           => ComplianceDocumentFileResource::collection($files),
        ];
    }
}
