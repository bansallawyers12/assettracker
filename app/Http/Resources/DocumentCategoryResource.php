<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'sort_order' => $this->sort_order,
            'asset_id'   => $this->asset_id,
            'documents'  => DocumentSlotResource::collection($this->whenLoaded('documents')),
        ];
    }
}
