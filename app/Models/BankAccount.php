<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\EncryptsAttributes;

class BankAccount extends Model
{
    use EncryptsAttributes;

    public const PURPOSE_GENERAL = 'general';

    public const PURPOSE_LOAN = 'loan';

    public const PURPOSE_LOAN_REPAYMENT = 'loan_repayment';

    public const PURPOSE_OFFSET = 'offset';

    public const PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_LOAN,
        self::PURPOSE_LOAN_REPAYMENT,
        self::PURPOSE_OFFSET,
    ];

    public const ENTITY_PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_LOAN,
        self::PURPOSE_OFFSET,
    ];

    public const ASSET_ROLES = [
        self::PURPOSE_LOAN,
        self::PURPOSE_LOAN_REPAYMENT,
        self::PURPOSE_OFFSET,
    ];

    protected $fillable = [
        'business_entity_id',
        'user_id',
        'bank_name',
        'bsb',
        'account_number',
        'account_name',
        'account_purpose',
    ];

    protected $encrypted = [
        'account_number',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function normalizeBsb(?string $bsb): ?string
    {
        if ($bsb === null || trim($bsb) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $bsb);

        return $digits !== '' ? $digits : null;
    }

    public static function formatBsb(?string $bsb): ?string
    {
        $digits = self::normalizeBsb($bsb);

        if ($digits === null || strlen($digits) !== 6) {
            return $bsb;
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3);
    }

    public static function purposeLabel(string $purpose): string
    {
        return match ($purpose) {
            self::PURPOSE_GENERAL => 'General',
            self::PURPOSE_LOAN => 'Loan',
            self::PURPOSE_LOAN_REPAYMENT => 'Loan repayment',
            self::PURPOSE_OFFSET => 'Offset',
            default => ucfirst(str_replace('_', ' ', $purpose)),
        };
    }

    public function isPortfolioWide(): bool
    {
        return $this->business_entity_id === null;
    }

    public function canUseForBankImport(BusinessEntity $entity): bool
    {
        return $this->account_purpose === self::PURPOSE_GENERAL
            && (int) $this->business_entity_id === (int) $entity->id;
    }

    public function canUseForTransaction(BusinessEntity $entity, ?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if ((int) $this->business_entity_id === (int) $entity->id) {
            return true;
        }

        return $this->isPortfolioWide()
            && (int) $this->user_id === (int) $userId
            && $this->account_purpose === self::PURPOSE_LOAN_REPAYMENT;
    }

    public function isValidForAssetRole(BusinessEntity $entity, string $role, ?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if ($this->account_purpose !== $role) {
            return false;
        }

        if ($role === self::PURPOSE_LOAN_REPAYMENT) {
            return $this->isPortfolioWide() && (int) $this->user_id === (int) $userId;
        }

        return (int) $this->business_entity_id === (int) $entity->id;
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $inner) use ($userId) {
            $inner->where('user_id', $userId)
                ->orWhereHas('businessEntity', fn (Builder $entityQuery) => $entityQuery->where('user_id', $userId));
        });
    }

    public function scopeSelectableForEntity(Builder $query, BusinessEntity $entity, string $purpose): Builder
    {
        $query->where('account_purpose', $purpose);

        if ($purpose === self::PURPOSE_LOAN_REPAYMENT) {
            return $query->whereNull('business_entity_id')
                ->where('user_id', auth()->id());
        }

        return $query->where('business_entity_id', $entity->id);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankStatementEntries(): HasMany
    {
        return $this->hasMany(BankStatementEntry::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_bank_account')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function displayLabel(): string
    {
        $label = $this->account_name ?: $this->bank_name;

        return $label.' ('.self::formatBsb($this->bsb).')';
    }
}
