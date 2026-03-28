<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    protected $fillable = [
        'business_entity_id',
        'asset_id',
        'title',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'document_category_id');
    }

    public function scopeForWorkspace($query, int $businessEntityId, ?int $assetId)
    {
        $query->where('business_entity_id', $businessEntityId);
        if ($assetId === null) {
            return $query->whereNull('asset_id');
        }

        return $query->where('asset_id', $assetId);
    }
}
