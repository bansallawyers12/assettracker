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

    // Roles on the asset_bank_account pivot
    public const ROLE_LOAN = 'loan';
    public const ROLE_LOAN_REPAYMENT = 'loan_repayment';
    public const ROLE_OFFSET = 'offset';
    public const ROLE_RENT_COLLECTION = 'rent_collection';

    public const ASSET_ROLES = [
        self::ROLE_LOAN,
        self::ROLE_LOAN_REPAYMENT,
        self::ROLE_OFFSET,
        self::ROLE_RENT_COLLECTION,
    ];

    // Account holder types
    public const HOLDER_ENTITY = 'entity';
    public const HOLDER_PERSON = 'person';
    public const HOLDER_OTHER  = 'other';

    public const HOLDER_TYPES = [
        self::HOLDER_ENTITY,
        self::HOLDER_PERSON,
        self::HOLDER_OTHER,
    ];

    protected $fillable = [
        'business_entity_id',
        'user_id',
        'bank_name',
        'bsb',
        'account_number',
        'account_name',
        'account_purpose',
        'holder_type',
        'holder_entity_id',
        'holder_person_id',
        'holder_other',
    ];

    protected $encrypted = [
        'account_number',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── BSB helpers ──────────────────────────────────────────────────────────

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

    /**
     * Mask an account number for display (e.g. ****6789).
     */
    public static function maskAccountNumber(?string $accountNumber, int $visibleDigits = 4): ?string
    {
        if ($accountNumber === null || trim($accountNumber) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $accountNumber);
        if ($digits === '') {
            return '****';
        }

        if (strlen($digits) <= $visibleDigits) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', 4).substr($digits, -$visibleDigits);
    }

    public function maskedAccountNumber(): ?string
    {
        return self::maskAccountNumber($this->account_number);
    }

    // ── Label helpers ─────────────────────────────────────────────────────────

    public static function purposeLabel(string $purpose): string
    {
        return match ($purpose) {
            self::PURPOSE_GENERAL       => 'General',
            self::PURPOSE_LOAN          => 'Loan',
            self::PURPOSE_LOAN_REPAYMENT => 'Loan repayment',
            self::PURPOSE_OFFSET        => 'Offset',
            default => ucfirst(str_replace('_', ' ', $purpose)),
        };
    }

    public static function holderTypeLabel(string $type): string
    {
        return match ($type) {
            self::HOLDER_ENTITY => 'Entity',
            self::HOLDER_PERSON => 'Person',
            self::HOLDER_OTHER  => 'Other',
            default => ucfirst($type),
        };
    }

    /**
     * Resolved display name for who holds this account at the bank.
     */
    public function holderLabel(): string
    {
        return match ($this->holder_type) {
            self::HOLDER_ENTITY => $this->holderEntity?->legal_name ?? '—',
            self::HOLDER_PERSON => $this->holderPersonFullName(),
            self::HOLDER_OTHER  => $this->holder_other ?? '—',
            default             => '—',
        };
    }

    public function holderPersonFullName(): string
    {
        if (! $this->holderPerson) {
            return '—';
        }
        return trim(($this->holderPerson->first_name ?? '').' '.($this->holderPerson->last_name ?? ''));
    }

    /**
     * Label shown in pickers and the portfolio index.
     * "Account Name — Holder (BSB)"
     */
    public function displayLabel(): string
    {
        $label  = $this->account_name ?: $this->bank_name;
        $bsb    = self::formatBsb($this->bsb);
        $holder = $this->holderLabel();

        if ($holder !== '—') {
            return "{$label} — {$holder} ({$bsb})";
        }
        return "{$label} ({$bsb})";
    }

    /**
     * Route to edit this account (portfolio-wide or entity-scoped).
     */
    public function editRoute(): string
    {
        if ($this->isPortfolioWide()) {
            return route('bank-accounts.edit', $this);
        }

        return route('business-entities.bank-accounts.edit', [$this->business_entity_id, $this]);
    }

    // ── Scope helpers ─────────────────────────────────────────────────────────

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
            return $this->account_purpose === self::PURPOSE_GENERAL;
        }

        return $this->isPortfolioWide()
            && (int) $this->user_id === (int) $userId
            && $this->account_purpose === self::PURPOSE_LOAN_REPAYMENT;
    }

    public function isValidForAssetRole(BusinessEntity $entity, string $role, ?int $userId = null): bool
    {
        $userId ??= auth()->id();

        // Rent collection: any general account belonging to any of the user's entities
        if ($role === self::ROLE_RENT_COLLECTION) {
            return $this->account_purpose === self::PURPOSE_GENERAL
                && $this->businessEntity !== null
                && (int) $this->businessEntity->user_id === (int) $userId;
        }

        // Loan repayment: portfolio-wide account owned by this user
        if ($role === self::ROLE_LOAN_REPAYMENT) {
            return $this->account_purpose === self::PURPOSE_LOAN_REPAYMENT
                && $this->isPortfolioWide()
                && (int) $this->user_id === (int) $userId;
        }

        // Loan / Offset: entity-scoped, purpose must match role
        return $this->account_purpose === $role
            && (int) $this->business_entity_id === (int) $entity->id;
    }

    // ── Query scopes ──────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereHas('businessEntity', fn (Builder $eq) => $eq->where('user_id', $userId));
        });
    }

    /**
     * Accounts available for a given role picker on an asset form.
     *
     * loan / offset          → entity-scoped, matching purpose
     * loan_repayment         → portfolio-wide, matching purpose
     * rent_collection        → any general account across all user's entities
     */
    public function scopeSelectableForAssetRole(Builder $query, BusinessEntity $entity, string $role): Builder
    {
        if ($role === self::ROLE_RENT_COLLECTION) {
            return $query
                ->where('account_purpose', self::PURPOSE_GENERAL)
                ->whereHas('businessEntity', fn (Builder $q) => $q->where('user_id', auth()->id()));
        }

        if ($role === self::ROLE_LOAN_REPAYMENT) {
            return $query
                ->where('account_purpose', self::PURPOSE_LOAN_REPAYMENT)
                ->whereNull('business_entity_id')
                ->where('user_id', auth()->id());
        }

        // loan / offset
        return $query
            ->where('account_purpose', $role)
            ->where('business_entity_id', $entity->id);
    }

    /** @deprecated Use scopeSelectableForAssetRole */
    public function scopeSelectableForEntity(Builder $query, BusinessEntity $entity, string $purpose): Builder
    {
        $query->where('account_purpose', $purpose);
        if ($purpose === self::PURPOSE_LOAN_REPAYMENT) {
            return $query->whereNull('business_entity_id')->where('user_id', auth()->id());
        }
        return $query->where('business_entity_id', $entity->id);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function holderEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class, 'holder_entity_id');
    }

    public function holderPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'holder_person_id');
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
}
