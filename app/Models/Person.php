<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\EncryptsAttributes;

class Person extends Model
{
    use EncryptsAttributes;

    protected $table = 'persons'; // Explicitly set table name

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'tfn', // Use existing tax_file_number column
        'abn', // New column
        'phone_number',
        'address',
        'identification_number',
        'nationality',
        'status',
        'ssn',
        'passport_number',
        'drivers_license',
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encrypted = [
        'email',
        'tfn',
        'phone_number',
        'address',
        'identification_number',
        'ssn',
        'passport_number',
        'drivers_license',
    ];

    public function businessEntities()
    {
        return $this->belongsToMany(BusinessEntity::class, 'entity_person')
            ->withPivot(['role', 'appointment_date', 'resignation_date', 'role_status', 'shares_percentage', 'authority_level', 'asic_updated', 'asic_due_date'])
            ->withTimestamps();
    }

    public function entityPersons()
    {
        return $this->hasMany(EntityPerson::class, 'person_id');
    }
}