<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceDocumentFileResource extends JsonResource
{
    public function toArray($request): array
    {
        $entityId = $this->yearRecord?->business_entity_id;

        $contentUrl = $this->path && $entityId
            ? route('entities.compliance-files.content', [$entityId, $this->id])
            : null;

        return [
            'id'          => $this->id,
            'type_code'   => $this->type?->code,
            'type_label'  => $this->type?->label,
            'frequency'   => $this->type?->frequency,
            'is_required' => (bool) ($this->type?->is_required ?? false),
            'status'      => $this->status,
            'has_file'    => (bool) $this->path,
            'file_name'   => $this->file_name,
            'due_date'    => $this->due_date?->toDateString(),
            'content_url' => $contentUrl,
            'download_url'=> $contentUrl ? $contentUrl.'?download=1' : null,
        ];
    }
}
