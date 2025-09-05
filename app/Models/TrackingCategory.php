<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingCategory extends Model
{
    protected $fillable = [
        'business_entity_id',
        'name',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function subCategories()
    {
        return $this->hasMany(TrackingSubCategory::class)->orderBy('sort_order');
    }

    public function activeSubCategories()
    {
        return $this->hasMany(TrackingSubCategory::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
