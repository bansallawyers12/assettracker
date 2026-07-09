<?php

namespace App\Models;

use App\Services\VendorSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'abn',
        'notes',
    ];

    protected static function booted(): void
    {
        static::updated(function (Vendor $vendor) {
            if ($vendor->wasChanged('name')) {
                app(VendorSyncService::class)->syncLinkedTransactionNames($vendor);
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function orderedForSelect()
    {
        return static::query()->orderBy('name')->get();
    }
}
