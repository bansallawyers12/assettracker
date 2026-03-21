<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateCompanyContact extends Model
{
    protected $fillable = [
        'real_estate_company_id',
        'contact_person_name',
        'email',
        'phone',
    ];

    public function realEstateCompany(): BelongsTo
    {
        return $this->belongsTo(RealEstateCompany::class, 'real_estate_company_id');
    }
}
