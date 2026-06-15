<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commitment extends Model
{
    public const TYPES = ['Property', 'Car', 'Other'];

    public const STATUSES = ['Active', 'Settled', 'Cancelled'];

    protected $fillable = [
        'business_entity_id',
        'commitment_type',
        'name',
        'contract_price',
        'contract_date',
        'settlement_date',
        'status',
        'notes',
        'asset_id',
    ];

    protected $casts = [
        'contract_price' => 'decimal:2',
        'contract_date' => 'date',
        'settlement_date' => 'date',
    ];

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CommitmentPayment::class)->orderBy('paid_at')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeForOperationalEntities(Builder $query): Builder
    {
        return $query->whereHas('businessEntity', fn (Builder $q) => $q->operationalEntities());
    }

    public function scopeSettlementDueWithinDays(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('settlement_date')
            ->whereDate('settlement_date', '<=', now()->addDays($days)->toDateString());
    }

    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->contract_price - $this->total_paid);
    }

    public function isEditable(): bool
    {
        return $this->status === 'Active';
    }

    public function defaultAssetType(): string
    {
        return match ($this->commitment_type) {
            'Car' => 'Car',
            'Property' => 'House Owned',
            default => 'Real Estate',
        };
    }
}
