<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'chart_of_account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'reference',
        'tracking_category_id',
        'tracking_sub_category_id'
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2'
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function trackingCategory()
    {
        return $this->belongsTo(TrackingCategory::class);
    }

    public function trackingSubCategory()
    {
        return $this->belongsTo(TrackingSubCategory::class);
    }
}
