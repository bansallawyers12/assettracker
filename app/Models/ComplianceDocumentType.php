<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceDocumentType extends Model
{
    protected $fillable = [
        'code',
        'label',
        'description',
        'scope',
        'category_group',
        'frequency',
        'asset_types',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'asset_types' => 'array',
            'sort_order'  => 'integer',
            'is_required' => 'boolean',
            'is_active'   => 'boolean',
        ];
    }

    public function files(): HasMany
    {
        return $this->hasMany(ComplianceDocumentFile::class, 'compliance_document_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function appliesToAssetType(?string $assetType): bool
    {
        if ($this->scope !== 'asset') {
            return false;
        }

        if ($this->asset_types === null || $this->asset_types === []) {
            return true;
        }

        return $assetType !== null && in_array($assetType, $this->asset_types, true);
    }
}
