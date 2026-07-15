<?php

namespace App\Models;

use App\Support\FinancialYear;
use App\Traits\EncryptsAttributes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BusinessEntity extends Model
{
    use EncryptsAttributes;

    /**
     * Fields encrypted at rest.
     * Columns must be TEXT (see migration encrypt_business_entity_sensitive_fields).
     * abn and acn are also mirrored to abn_hash / acn_hash for lookups and uniqueness.
     */
    protected $encrypted = ['tfn', 'abn', 'acn', 'corporate_key'];

    protected $fillable = [
        'legal_name',
        'trading_name',
        'entity_type',
        'trust_type',
        'trust_establishment_date',
        'trust_deed_date',
        'trust_deed_reference',
        'trust_vesting_date',
        'trust_vesting_conditions',
        'appointor_person_id',
        'appointor_entity_id',
        'abn',
        'acn',
        'tfn',
        'corporate_key',
        'registered_address',
        'registered_email',
        'phone_number',
        'asic_renewal_date',
        'bas_reporting_frequency',
        'uses_tax_agent',
        'gst_registered',
        'entity_tax_return_required',
        'user_id',
        'status',
        'closed_date',
        'closed_reason',
        'registration_date',
        'exclude_from_financial_reports',
        'abn_hash',
        'acn_hash',
    ];

    protected $hidden = ['abn_hash', 'acn_hash'];

    protected $casts = [
        'trust_establishment_date' => 'date',
        'trust_deed_date' => 'date',
        'trust_vesting_date' => 'date',
        'registration_date' => 'date',
        'closed_date' => 'date',
        'asic_renewal_date' => 'datetime',
        'uses_tax_agent' => 'boolean',
        'gst_registered' => 'boolean',
        'entity_tax_return_required' => 'boolean',
        'exclude_from_financial_reports' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Override setAttribute to maintain HMAC hash columns alongside encrypted abn/acn.
     * The hash is computed from normalised digits (no formatting) so it is stable regardless
     * of how the user enters the value (e.g. "12 345 678 901" vs "12345678901").
     */
    public function setAttribute($key, $value): mixed
    {
        if ($key === 'abn') {
            $this->attributes['abn_hash'] = $this->computeAbnHash($value);
        }

        if ($key === 'acn') {
            $this->attributes['acn_hash'] = $this->computeAcnHash($value);
        }

        return parent::setAttribute($key, $value);
    }

    private function computeAbnHash(mixed $abn): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $abn);

        return $digits !== '' ? hash_hmac('sha256', $digits, config('app.key')) : null;
    }

    private function computeAcnHash(mixed $acn): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $acn);

        return $digits !== '' ? hash_hmac('sha256', $digits, config('app.key')) : null;
    }

    /**
     * Operating entities that are still open (not closed).
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_date');
    }

    /**
     * Entities that have been closed via the close-entity workflow.
     */
    public function scopeClosedEntities($query)
    {
        return $query->whereNotNull('closed_date');
    }

    /**
     * Your operating companies/trusts (excludes tenancy/property-manager contacts and closed entities).
     */
    public function scopeOperationalEntities($query)
    {
        return $query->where('exclude_from_financial_reports', false)->open();
    }

    /**
     * Same as operational entities — included on reports index and in reporting queries.
     */
    public function scopeForFinancialReports($query)
    {
        return $query->operationalEntities();
    }

    public function isTenancyContactOnly(): bool
    {
        return (bool) ($this->attributes['exclude_from_financial_reports'] ?? false);
    }

    public function isOperationalEntity(): bool
    {
        return ! $this->isTenancyContactOnly();
    }

    public function isClosed(): bool
    {
        return $this->closed_date !== null;
    }

    /**
     * Short label for report entity pickers (trading name when set).
     */
    public function reportPickerLabel(): string
    {
        return $this->trading_name ?: $this->legal_name;
    }

    /**
     * Exists rule limited to operating entities (excludes tenancy/property-manager contacts).
     *
     * Use query callbacks instead of ->where(..., false): when the rule is stringified for validation,
     * boolean false is serialized as an empty string and PostgreSQL rejects "" for a boolean column.
     */
    public static function ruleExistsOperational(string $column = 'id'): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('business_entities', $column)->using(
            fn ($query) => $query->where('exclude_from_financial_reports', false)->whereNull('closed_date')
        );
    }

    /**
     * Appointor company for a trust (operating entity, not a trust record).
     */
    public static function ruleExistsOperationalAppointorCompany(): \Illuminate\Validation\Rules\Exists
    {
        return self::ruleExistsOperational()->using(
            fn ($query) => $query->where('entity_type', '!=', 'Trust')
        );
    }

    /**
     * Corporate trustee / company link: any non-trust business entity.
     */
    public static function ruleExistsNonTrustCompany(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('business_entities', 'id')->using(
            fn ($query) => $query->where('entity_type', '!=', 'Trust')
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'business_entity_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'business_entity_id');
    }

    public function commitments()
    {
        return $this->hasMany(Commitment::class, 'business_entity_id');
    }

    public function persons()
    {
        return $this->hasMany(EntityPerson::class, 'business_entity_id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function bankAccountLinks()
    {
        return $this->hasMany(BusinessEntityBankAccount::class);
    }

    /**
     * Bank accounts linked to this entity with one or more purposes (via pivot).
     */
    public function linkedBankAccounts()
    {
        return $this->belongsToMany(BankAccount::class, 'business_entity_bank_account')
            ->withPivot('purpose')
            ->withTimestamps();
    }

    /**
     * Purpose links for the entity bank accounts tab (pivot rows, with legacy fallback).
     *
     * @return \Illuminate\Support\Collection<int, BusinessEntityBankAccount>
     */
    public function bankAccountLinksForDisplay()
    {
        $links = $this->bankAccountLinks()
            ->with([
                'bankAccount' => fn ($query) => $query->withDeleteCounts()->with(['holderEntity', 'holderPerson', 'businessEntity']),
            ])
            ->get();

        if ($links->isNotEmpty()) {
            return $links
                ->sortBy(fn (BusinessEntityBankAccount $link) => $link->bankAccount?->account_name ?? '', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        }

        return $this->bankAccounts()
            ->withDeleteCounts()
            ->with(['holderEntity', 'holderPerson', 'businessEntity'])
            ->whereIn('account_purpose', BankAccount::ENTITY_PURPOSES)
            ->orderBy('account_name')
            ->get()
            ->map(function (BankAccount $account) {
                $link = new BusinessEntityBankAccount([
                    'business_entity_id' => $this->id,
                    'bank_account_id' => $account->id,
                    'purpose' => $account->account_purpose,
                ]);
                $link->setRelation('bankAccount', $account);

                return $link;
            });
    }

    public function heldBankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'holder_entity_id')
            ->where('holder_type', BankAccount::HOLDER_ENTITY);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function reminders()
    {
        return $this->morphMany(Reminder::class, 'reminder');
    }

    /**
     * Get all pending reminders for the business entity.
     */
    public function pendingReminders()
    {
        return $this->reminders()->pending();
    }

    /**
     * Get all overdue reminders for the business entity.
     */
    public function overdueReminders()
    {
        return $this->reminders()->overdue();
    }

    /**
     * Get the contact lists for the business entity.
     */
    public function contactLists()
    {
        return $this->hasMany(ContactList::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'business_entity_id');
    }

    public function documentCategories()
    {
        return $this->hasMany(DocumentCategory::class, 'business_entity_id');
    }

    public function mailMessages()
    {
        return $this->belongsToMany(MailMessage::class, 'business_entity_mail_message');
    }

    /**
     * Get the appointor person for this trust.
     */
    public function appointorPerson()
    {
        return $this->belongsTo(Person::class, 'appointor_person_id');
    }

    /**
     * Get the appointor entity for this trust.
     */
    public function appointorEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'appointor_entity_id');
    }

    /**
     * Get all trustees for this trust (both person and entity trustees).
     */
    public function trustees()
    {
        return $this->hasMany(EntityPerson::class, 'business_entity_id')
            ->where('role', 'Trustee')
            ->where('role_status', 'Active');
    }

    /**
     * Get all beneficiaries for this trust.
     */
    public function beneficiaries()
    {
        return $this->hasMany(EntityPerson::class, 'business_entity_id')
            ->where('role', 'Beneficiary')
            ->where('role_status', 'Active');
    }

    /**
     * Check if this entity is a trust.
     */
    public function isTrust()
    {
        return $this->entity_type === 'Trust';
    }

    /**
     * UI label for registration_date (non-trust entities only).
     */
    public function registrationDateLabel(): string
    {
        return self::registrationDateLabelFor($this->entity_type);
    }

    public static function registrationDateLabelFor(?string $entityType): string
    {
        return match ($entityType) {
            'Company' => 'Registration date',
            'Sole Trader' => 'Commencement date',
            'Partnership' => 'Formation date',
            default => 'Registration date',
        };
    }

    /**
     * When the entity was formed: trust establishment or registration/commencement date.
     */
    public function formationDate(): ?Carbon
    {
        return $this->isTrust()
            ? $this->trust_establishment_date
            : $this->registration_date;
    }

    /**
     * Whether a registration or trust establishment date is stored (not a fallback).
     */
    public function hasExplicitFormationDate(): bool
    {
        return $this->formationDate() !== null;
    }

    /**
     * Formation date for compliance scoping: explicit date, else record created_at.
     */
    public function effectiveFormationDate(): ?Carbon
    {
        return $this->formationDate()?->copy()->startOfDay()
            ?? $this->created_at?->copy()->startOfDay();
    }

    /**
     * FY start of the first financial year in which this entity existed (partial year counts).
     */
    public function firstApplicableFyStart(): ?Carbon
    {
        $formation = $this->effectiveFormationDate();
        if ($formation === null) {
            return null;
        }

        return FinancialYear::forDate($formation)['start'];
    }

    /**
     * Whether ATO/ASIC obligations apply for the given financial year.
     * False when the entity was formed after the FY ended.
     */
    public function complianceAppliesForFinancialYear(Carbon|string $fyStart): bool
    {
        $formation = $this->effectiveFormationDate();
        if ($formation === null) {
            return true;
        }

        $normalizedStart = $fyStart instanceof Carbon
            ? FinancialYear::forDate($fyStart)['start']
            : FinancialYear::forDate(Carbon::parse($fyStart))['start'];
        $fyEnd = FinancialYear::forDate($normalizedStart)['end']->startOfDay();

        return $formation->lte($fyEnd);
    }

    /**
     * Effective BAS frequency: per-entity override, else app config (annual|quarterly|monthly).
     * Monthly falls back to quarterly slots until dedicated monthly types exist.
     */
    public function effectiveBasReportingFrequency(): string
    {
        $value = $this->bas_reporting_frequency;
        if (in_array($value, ['annual', 'quarterly', 'monthly'], true)) {
            return $value === 'monthly' ? 'quarterly' : $value;
        }

        $fallback = config('compliance.bas_mode', 'quarterly');

        return $fallback === 'quarterly' ? 'quarterly' : 'annual';
    }

    public function usesTaxAgent(): bool
    {
        return (bool) $this->uses_tax_agent;
    }

    public function isGstRegistered(): bool
    {
        return $this->gst_registered !== false;
    }

    public function requiresTaxReturn(): bool
    {
        return $this->entity_tax_return_required !== false;
    }

    public function requiresAsicStatement(): bool
    {
        if ($this->entity_type === 'Company') {
            return true;
        }

        // Non-companies only when an ASIC renewal anniversary is configured on the entity.
        return $this->asic_renewal_date !== null;
    }

    public function complianceYearRecords()
    {
        return $this->hasMany(ComplianceYearRecord::class);
    }

    public static function formatAbn(?string $abn): string
    {
        if ($abn === null || $abn === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $abn);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 3).' '.substr($digits, 8, 3);
        }

        return $abn;
    }

    public static function formatAcn(?string $acn): string
    {
        if ($acn === null || $acn === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $acn);
        if (strlen($digits) === 9) {
            return substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 3);
        }

        return $acn;
    }

    public function registeredEmailIsPlaceholder(): bool
    {
        $e = trim((string) $this->registered_email);
        if ($e === '') {
            return true;
        }
        $lower = strtolower($e);

        if (str_contains($lower, 'example.invalid')) {
            return true;
        }

        // Typical import / doc placeholder domains (avoid matching "myexample.com" etc.)
        if (preg_match('/@(example\.(com|org|net|invalid|test)|localhost)\b/i', $e)) {
            return true;
        }

        return false;
    }

    public function phoneNumberIsPlaceholder(): bool
    {
        $raw = trim((string) $this->phone_number);
        if ($raw === '') {
            return true;
        }
        $p = preg_replace('/\D/', '', $raw);
        if ($p === '') {
            return true;
        }

        return (bool) preg_match('/^0+$/', $p);
    }
}
