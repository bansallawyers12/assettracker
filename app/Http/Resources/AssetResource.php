<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'business_entity_id' => $this->business_entity_id,
            'name' => $this->name,
            'asset_type' => $this->asset_type,
            'status' => $this->status,
            'acquisition_date' => $this->acquisition_date?->format('Y-m-d'),
            'acquisition_date_display' => $this->acquisition_date?->format('d/m/Y'),
            'acquisition_cost' => $this->acquisition_cost,
            'current_value' => $this->current_value,
            'description' => $this->description,
            'address' => $this->address,
            'edit_url' => route('entities.assets.form.edit', [$this->business_entity_id, $this->id]),
            'detail_url' => route('entities.assets.detail', [$this->business_entity_id, $this->id]),
            'show_url' => route('business-entities.assets.show', [$this->business_entity_id, $this->id]),
        ];
    }
}
