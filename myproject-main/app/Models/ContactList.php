<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_entity_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'phone_no',
        'mobile_no',
        'address',
        'zip_code'
    ];

    /**
     * Get the business entity that owns the contact list.
     */
    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }
} 