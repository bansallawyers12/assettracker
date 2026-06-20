<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceYearRecord extends Model
{
    protected $fillable = [
        'business_entity_id',
        'asset_id',
        'fy_start_date',
        'fy_end_date',
        'notes',
        'locked_at',
        'locked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fy_start_date' => 'date',
            'fy_end_date' => 'date',
            'locked_at' => 'datetime',
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

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ComplianceDocumentFile::class, 'compliance_year_record_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ComplianceCategory::class, 'compliance_year_record_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
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
