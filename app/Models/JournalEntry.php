<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'business_entity_id',
        'entry_date',
        'reference_number',
        'description',
        'total_debit',
        'total_credit',
        'is_posted',
        'created_by',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source()
    {
        return $this->morphTo();
    }

    /**
     * User-posted journals (manual adjustments and opening balances), excluding system sources.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePostedManual($query)
    {
        return $query->whereNull('source_type')->where('is_posted', true);
    }

    public function isOpeningBalance(): bool
    {
        return str_starts_with((string) ($this->reference_number ?? ''), 'OPEN-');
    }

    public function manualKind(): string
    {
        return $this->isOpeningBalance() ? 'opening_balance' : 'manual';
    }
}
