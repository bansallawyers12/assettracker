<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementEntry extends Model
{
    protected $fillable = [
        'bank_account_id',
        'date',
        'amount',
        'description',
        'transaction_type',
        'transaction_id',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        $meta = is_array($this->meta) ? $this->meta : [];

        return $meta[$key] ?? $default;
    }
}
