<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceCategory extends Model
{
    protected $fillable = [
        'compliance_year_record_id',
        'title',
        'sort_order',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_system'  => 'boolean',
        ];
    }

    public function yearRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceYearRecord::class, 'compliance_year_record_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ComplianceDocumentFile::class, 'compliance_category_id');
    }
}
