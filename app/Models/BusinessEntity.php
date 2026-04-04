<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BusinessEntity extends Model
{
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
        'user_id',
        'status',
        'exclude_from_financial_reports',
    ];

    protected $casts = [
        'trust_establishment_date' => 'date',
        'trust_deed_date' => 'date',
        'trust_vesting_date' => 'date',
        'asic_renewal_date' => 'datetime',
        'exclude_from_financial_reports' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Your operating companies/trusts (excludes tenancy/property-manager contacts).
     */
    public function scopeOperationalEntities($query)
    {
        return $query->where('exclude_from_financial_reports', false);
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

    /**
     * Exists rule limited to operating entities (excludes tenancy/property-manager contacts).
     */
    public static function ruleExistsOperational(string $column = 'id'): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('business_entities', $column)->where('exclude_from_financial_reports', false);
    }

    /**
     * Appointor company for a trust (operating entity, not a trust record).
     */
    public static function ruleExistsOperationalAppointorCompany(): \Illuminate\Validation\Rules\Exists
    {
        return self::ruleExistsOperational()
            ->where('entity_type', '!=', 'Trust');
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

    public function persons()
    {
        return $this->hasMany(EntityPerson::class, 'business_entity_id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
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
