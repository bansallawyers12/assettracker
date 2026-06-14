<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitmentPayment extends Model
{
    public const PAYMENT_TYPES = ['Deposit', 'Progress', 'Balance', 'Interest', 'Other'];

    protected $fillable = [
        'commitment_id',
        'amount',
        'paid_at',
        'payment_type',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }
}
