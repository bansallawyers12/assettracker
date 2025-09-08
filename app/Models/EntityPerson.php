<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityPerson extends Model
{
    protected $table = 'entity_person';

    protected $fillable = [
        'business_entity_id',
        'person_id',
        'entity_trustee_id',
        'appointor_entity_id',
        'role',
        'appointment_date',
        'resignation_date',
        'role_status',
        'shares_percentage',
        'authority_level',
        'asic_updated',
        'asic_due_date',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'resignation_date' => 'datetime',
        'asic_due_date' => 'datetime',
        'shares_percentage' => 'decimal:2',
        'asic_updated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // We explicitly avoid defining any unique constraints here
    // to allow one person to have multiple roles in the same entity
    
    /**
     * Boot the model.
     * Disable Laravel's default uniqueness checks.
     */
    public static function boot()
    {
        parent::boot();
        
        // By not defining any unique indexes here, we ensure
        // that a person can have multiple roles in the same entity
    }

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'business_entity_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function trusteeEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'entity_trustee_id');
    }

    public function appointorEntity()
    {
        return $this->belongsTo(BusinessEntity::class, 'appointor_entity_id');
    }

    public function scopeDueWithin15Days($query)
    {
        return $query->where(function ($query) {
            $query->whereBetween('asic_due_date', [now(), now()->addDays(15)])
                  ->where('asic_updated', false)
                  ->where('role_status', 'Active');
        });
    }

    /**
     * Check if this relationship involves an appointor entity.
     */
    public function isAppointorEntity()
    {
        return $this->role === 'Appointor' && !is_null($this->appointor_entity_id);
    }

    /**
     * Check if this relationship involves a person appointor.
     */
    public function isAppointorPerson()
    {
        return $this->role === 'Appointor' && !is_null($this->person_id);
    }

    /**
     * Get the appointor (either person or entity).
     */
    public function getAppointorAttribute()
    {
        if ($this->isAppointorPerson()) {
            return $this->person;
        } elseif ($this->isAppointorEntity()) {
            return $this->appointorEntity;
        }
        return null;
    }
}