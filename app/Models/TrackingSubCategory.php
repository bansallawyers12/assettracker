<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingSubCategory extends Model
{
    protected $fillable = [
        'tracking_category_id',
        'name',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    public function trackingCategory()
    {
        return $this->belongsTo(TrackingCategory::class);
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
