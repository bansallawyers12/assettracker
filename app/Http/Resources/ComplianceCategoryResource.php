<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $files = $this->files->sortBy(fn ($f) => [
            $f->type?->sort_order ?? 999,
            $f->type?->id ?? $f->id,
        ])->values();

        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'sort_order' => $this->sort_order,
            'is_system'  => (bool) $this->is_system,
            'files'      => ComplianceDocumentFileResource::collection($files)->resolve(),
        ];
    }
}
