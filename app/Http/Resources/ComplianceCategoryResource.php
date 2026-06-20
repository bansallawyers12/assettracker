<?php

namespace App\Http\Resources;

use App\Services\ComplianceYearService;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var ComplianceYearService $yearService */
        $yearService = app(ComplianceYearService::class);

        $files = $this->files->sortBy(fn ($f) => [
            $f->type?->sort_order ?? 999,
            $f->type?->id ?? $f->id,
        ])->values();

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'sort_order'   => $this->sort_order,
            'is_system'    => (bool) $this->is_system,
            'completeness' => $yearService->categoryCompleteness($this->resource),
            'files'        => ComplianceDocumentFileResource::collection($files)->resolve(),
        ];
    }
}
