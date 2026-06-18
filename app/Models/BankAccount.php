<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use App\Traits\EncryptsAttributes;

class BankAccount extends Model
{
    use EncryptsAttributes;

    public const PURPOSE_GENERAL = 'general';
    public const PURPOSE_LOAN = 'loan';
    public const PURPOSE_LOAN_REPAYMENT = 'loan_repayment';
    public const PURPOSE_OFFSET = 'offset';
    public const PURPOSE_RENT_RECEIVING = 'rent_receiving';
    public const PURPOSE_RENT_PAYING = 'rent_paying';

    public const PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_LOAN,
        self::PURPOSE_LOAN_REPAYMENT,
        self::PURPOSE_OFFSET,
        self::PURPOSE_RENT_RECEIVING,
        self::PURPOSE_RENT_PAYING,
    ];

    public const ENTITY_PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_LOAN,
        self::PURPOSE_OFFSET,
        self::PURPOSE_RENT_RECEIVING,
        self::PURPOSE_RENT_PAYING,
    ];

    /** Purposes eligible for the asset “Rent Paid Into Account” picker. */
    public const RENT_RECEIVING_PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_RENT_RECEIVING,
    ];

    /** Entity-scoped purposes usable for bank import and statement matching. */
    public const ENTITY_OPERATING_PURPOSES = [
        self::PURPOSE_GENERAL,
        self::PURPOSE_RENT_RECEIVING,
        self::PURPOSE_RENT_PAYING,
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

    public const BANK_OTHER = '__other__';

    /** @var list<string> */
    public const AUSTRALIAN_BANKS = [
        'ANZ',
        'Bank of Melbourne',
        'Bank of Queensland',
        'BankSA',
        'Bankwest',
        'Bendigo Bank',
        'Citibank Australia',
        'Commonwealth Bank',
        'HSBC Australia',
        'ING',
        'Macquarie Bank',
        'NAB',
        'St.George Bank',
        'Suncorp Bank',
        'UBank',
        'Up Bank',
        'Westpac',
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

    public static function resolveBankNameFromFormInput(?string $select, ?string $other): string
    {
        if ($select === self::BANK_OTHER) {
            return trim((string) $other);
        }

        if ($select !== null && $select !== '') {
            return $select;
        }

        return '';
    }

    public static function isKnownBank(?string $bankName): bool
    {
        return $bankName !== null
            && $bankName !== ''
            && in_array($bankName, self::AUSTRALIAN_BANKS, true);
    }

    public static function purposeLabel(string $purpose): string
    {
        return match ($purpose) {
            self::PURPOSE_GENERAL        => 'General',
            self::PURPOSE_LOAN           => 'Loan',
            self::PURPOSE_LOAN_REPAYMENT => 'Loan repayment',
            self::PURPOSE_OFFSET         => 'Offset',
            self::PURPOSE_RENT_RECEIVING => 'Rent receiving',
            self::PURPOSE_RENT_PAYING    => 'Rent paying',
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
     * Stable key for grouping accounts by holder (one holder may have many accounts).
     */
    public function holderGroupKey(): string
    {
        return match ($this->holder_type) {
            self::HOLDER_ENTITY => 'entity:'.$this->holder_entity_id,
            self::HOLDER_PERSON => 'person:'.$this->holder_person_id,
            self::HOLDER_OTHER  => 'other:'.md5((string) $this->holder_other),
            default             => 'unassigned',
        };
    }

    /**
     * URL to create another account for the same holder.
     */
    public static function createUrlForHolder(
        string $holderType,
        int $holderId,
        ?int $businessEntityId = null,
        ?string $purpose = null
    ): string {
        $params = ['holder_type' => $holderType];

        if ($holderType === self::HOLDER_ENTITY) {
            $params['holder_entity_id'] = $holderId;
        } elseif ($holderType === self::HOLDER_PERSON) {
            $params['holder_person_id'] = $holderId;
        }

        if ($purpose !== null && $purpose !== '') {
            $params['purpose'] = $purpose;
        }

        if ($businessEntityId !== null) {
            return route('business-entities.bank-accounts.create', $businessEntityId).'?'.http_build_query($params);
        }

        return route('bank-accounts.create').'?'.http_build_query($params);
    }

    /**
     * @param  Collection<int, self>  $accounts
     * @return list<array{key: string, label: string, type: string, holder_id: int|null, accounts: Collection<int, self>, create_url: string}>
     */
    public static function groupedByHolder(Collection $accounts, ?int $defaultBusinessEntityId = null): array
    {
        $groups = [];

        foreach ($accounts as $account) {
            $key = $account->holderGroupKey();
            if (! isset($groups[$key])) {
                $holderId = match ($account->holder_type) {
                    self::HOLDER_ENTITY => $account->holder_entity_id,
                    self::HOLDER_PERSON => $account->holder_person_id,
                    default => null,
                };

                $groups[$key] = [
                    'key' => $key,
                    'label' => $account->holder_type ? $account->holderLabel() : 'Unassigned holder',
                    'type' => $account->holder_type ?? '',
                    'holder_id' => $holderId !== null ? (int) $holderId : null,
                    'accounts' => collect(),
                    'create_url' => ($account->holder_type && $holderId > 0)
                        ? self::createUrlForHolder(
                            $account->holder_type,
                            (int) $holderId,
                            $defaultBusinessEntityId ?? $account->business_entity_id
                        )
                        : route('bank-accounts.create'),
                ];
            }

            $groups[$key]['accounts']->push($account);
        }

        uasort($groups, fn (array $a, array $b) => strnatcasecmp($a['label'], $b['label']));

        return array_values($groups);
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
        return in_array($this->account_purpose, self::ENTITY_OPERATING_PURPOSES, true)
            && (int) $this->business_entity_id === (int) $entity->id;
    }

    public function canUseForTransaction(BusinessEntity $entity, ?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if ((int) $this->business_entity_id === (int) $entity->id) {
            return in_array($this->account_purpose, self::ENTITY_OPERATING_PURPOSES, true);
        }

        return $this->isPortfolioWide()
            && (int) $this->user_id === (int) $userId
            && $this->account_purpose === self::PURPOSE_LOAN_REPAYMENT;
    }

    public function isValidForAssetRole(BusinessEntity $entity, string $role, ?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if ($role === self::ROLE_RENT_COLLECTION) {
            return in_array($this->account_purpose, self::RENT_RECEIVING_PURPOSES, true)
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
                ->whereIn('account_purpose', self::RENT_RECEIVING_PURPOSES)
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
