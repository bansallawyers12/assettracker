<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentSlotResource extends JsonResource
{
    public function toArray($request): array
    {
        $assetId = $this->asset_id;
        $entityId = $this->business_entity_id;

        $baseContentUrl = $this->path
            ? route('business-entities.documents.content', [$entityId, $this->id])
            : null;

        $assetParam = $assetId ? '?asset_id='.$assetId : '';

        return [
            'id'              => $this->id,
            'checklist_label' => $this->checklist_label,
            'type'            => $this->type,
            'description'     => $this->description,
            'has_file'        => (bool) $this->path,
            'file_name'       => $this->file_name,
            'asset_id'        => $assetId,
            'content_url'     => $baseContentUrl ? $baseContentUrl.$assetParam : null,
            'download_url'    => $baseContentUrl ? $baseContentUrl.$assetParam.($assetId ? '&download=1' : '?download=1') : null,
        ];
    }
}
