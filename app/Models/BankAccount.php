<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\EncryptsAttributes;

class BankAccount extends Model
{
    use EncryptsAttributes;

    protected $fillable = [
        'business_entity_id',
        'bank_name',
        'bsb',
        'account_number',
        'nickname',
        'routing_number',
        'swift_code',
    ];

    /**
     * The attributes that should be encrypted.
     *
     * @var array
     */
    protected $encrypted = [
        'account_number',
        'routing_number',
        'swift_code',
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    public function bankStatementEntries()
{
    return $this->hasMany(BankStatementEntry::class);
}
}