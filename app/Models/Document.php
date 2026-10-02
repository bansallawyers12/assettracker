<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_entity_id',
        'asset_id',
        'document_category_id',
        'checklist_label',
        'file_name',
        'path',
        'type',
        'description',
        'filetype',
        'file_size',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function documentCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function hasFile(): bool
    {
        return filled($this->path);
    }

    /**
     * App proxy URL for inline preview or download (includes asset_id when scoped).
     */
    public function contentRouteUrl(BusinessEntity $businessEntity, bool $download = false): ?string
    {
        if (! $this->hasFile()) {
            return null;
        }

        $query = [];
        if ($this->asset_id) {
            $query['asset_id'] = $this->asset_id;
        }
        if ($download) {
            $query['download'] = '1';
        }

        $url = route('business-entities.documents.content', [$businessEntity, $this]);

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    public function getFileUrl(): ?string
    {
        if (! $this->path || ! Storage::disk('s3')->exists($this->path)) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl($this->path, now()->addMinutes(5));
    }
}
