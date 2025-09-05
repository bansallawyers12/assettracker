<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationSchedule extends Model
{
    protected $fillable = [
        'asset_id',
        'financial_year',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'is_posted'
    ];

    protected $casts = [
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'is_posted' => 'boolean'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
