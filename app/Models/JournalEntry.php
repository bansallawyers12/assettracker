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
        'reverses_journal_entry_id',
        'voided_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'voided_at' => 'datetime',
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

    public function reverses()
    {
        return $this->belongsTo(self::class, 'reverses_journal_entry_id');
    }

    public function reversedBy()
    {
        return $this->hasMany(self::class, 'reverses_journal_entry_id');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isReversal(): bool
    {
        return $this->reverses_journal_entry_id !== null;
    }

    public function hasBeenOffset(): bool
    {
        if ($this->relationLoaded('reversedBy')) {
            return $this->reversedBy->isNotEmpty();
        }

        if (array_key_exists('reversed_by_count', $this->attributes)) {
            return (int) $this->reversed_by_count > 0;
        }

        return $this->reversedBy()->exists();
    }

    public function canEdit(): bool
    {
        return $this->source_type === null
            && $this->is_posted
            && ! $this->isReversal()
            && ! $this->isVoided()
            && ! $this->hasBeenOffset();
    }

    public function canReverse(): bool
    {
        return $this->canEdit();
    }

    public function canVoid(): bool
    {
        return $this->canReverse() && ! $this->isReversal();
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
