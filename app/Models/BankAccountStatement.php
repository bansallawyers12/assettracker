<?php

namespace App\Models;

use App\Support\DocumentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountStatement extends Model
{
    protected $fillable = [
        'bank_account_id',
        'statement_period_start',
        'statement_period_end',
        'opening_balance',
        'closing_balance',
        'file_name',
        'path',
        'filetype',
        'file_size',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'statement_period_start' => 'date',
            'statement_period_end' => 'date',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (BankAccountStatement $statement): void {
            $statement->deleteStoredFile();
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periodLabel(): string
    {
        return $this->statement_period_start->format('d M Y')
            .' – '
            .$this->statement_period_end->format('d M Y');
    }

    public function formattedBalance(?string $field): string
    {
        $value = $this->{$field};

        if ($value === null) {
            return '—';
        }

        return '$'.number_format((float) $value, 2);
    }

    public function deleteStoredFile(): void
    {
        if ($this->path && DocumentStorage::exists($this->path)) {
            DocumentStorage::delete($this->path);
        }
    }

    public function belongsToBankAccount(BankAccount $bankAccount): bool
    {
        return (int) $this->bank_account_id === (int) $bankAccount->id;
    }
}
