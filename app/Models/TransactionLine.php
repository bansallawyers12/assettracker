<?php

namespace App\Models;

use App\Support\TransactionCashParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLine extends Model
{
    protected $fillable = [
        'transaction_id',
        'sort_order',
        'transaction_type',
        'amount',
        'gst_basis',
        'gst_amount',
        'gst_status',
        'description',
        'vendor_id',
        'vendor_name',
        'invoice_number',
        'related_entity_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'related_entity_id');
    }

    public function getDirectionAttribute(): string
    {
        return Transaction::directionFromType((string) $this->transaction_type);
    }

    public function getVendorDisplayAttribute(): ?string
    {
        return $this->vendor?->name ?? $this->vendor_name;
    }

    /**
     * @return array{cash: float, net: float, gst: float}
     */
    public function cashParts(): array
    {
        return TransactionCashParts::resolve(
            (float) $this->amount,
            $this->gst_amount !== null ? (float) $this->gst_amount : null,
            $this->gst_basis
        );
    }
}
