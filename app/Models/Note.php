<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'content',
        'business_entity_id',
        'user_id',
        'is_reminder',
        'reminder_date',
        'asset_id',
    ];

    protected $casts = [
        'is_reminder' => 'boolean',
        'reminder_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function businessEntity()
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
