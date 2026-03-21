<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'asset_id', 'name', 'email', 'phone', 'address',
        'move_in_date', 'lease_duration', 'lease_duration_value', 'lease_duration_unit',
        'lease_expiry_date', 'lease_expiry_reminder_days', 'rent_amount', 'rent_frequency',
        'move_out_date', 'notes',
        'is_real_estate_managed', 'real_estate_business_entity_id',
    ];

    protected $casts = [
        'move_in_date' => 'datetime',
        'move_out_date' => 'datetime',
        'lease_expiry_date' => 'date',
        'lease_duration_value' => 'integer',
        'lease_expiry_reminder_days' => 'integer',
        'rent_amount' => 'decimal:2',
        'is_real_estate_managed' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function realEstateCompany()
    {
        return $this->belongsTo(BusinessEntity::class, 'real_estate_business_entity_id');
    }
}
