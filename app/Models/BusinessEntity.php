<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'trust_establishment_date' => 'date',
        'trust_deed_date' => 'date',
        'trust_vesting_date' => 'date',
        'asic_renewal_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
        return $this->hasMany(\App\Models\Document::class, 'business_entity_id');
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

}