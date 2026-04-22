<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'content',
        'reminder_date',
        'repeat_type',
        'repeat_end_date',
        'next_due_date',
        'business_entity_id',
        'asset_id',
        'category',
        'notes',
        'is_completed',
        'completed_at',
        'priority',
        'status',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reminder_date' => 'datetime',
        'repeat_end_date' => 'datetime',
        'next_due_date' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    /**
     * Get the user that owns the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Scope a query to only include active reminders.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope a query to only include upcoming reminders.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('next_due_date', '>', now());
    }

    /**
     * Scope a query to only include reminders due within the next X days.
     */
    public function scopeDueWithinDays($query, $days)
    {
        return $query->whereBetween('next_due_date', [now(), now()->addDays($days)]);
    }

    /**
     * Due before today (calendar) or due from start of today through the next X days.
     * Used by the dashboard reminders widget; does not alter {@see scopeDueWithinDays}.
     */
    public function scopeDueOverdueOrWithinDays(Builder $query, int $days): Builder
    {
        $todayStart = now()->startOfDay();
        $windowEnd = now()->addDays($days)->endOfDay();

        return $query->where(function (Builder $q) use ($todayStart, $windowEnd) {
            $q->whereDate('next_due_date', '<', $todayStart)
                ->orWhereBetween('next_due_date', [$todayStart, $windowEnd]);
        });
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_due_date', '<', now())
                    ->where('is_completed', false);
    }

    public function scopeForBusinessEntity($query, $businessEntityId)
    {
        return $query->where('business_entity_id', $businessEntityId);
    }

    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function complete()
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->save();

        if ($this->repeat_type !== 'none') {
            $this->createNextReminder();
        }
    }

    public function extend($days = 3)
    {
        $this->next_due_date = $this->next_due_date->addDays($days);
        $this->save();
    }

    protected function createNextReminder()
    {
        if ($this->repeat_end_date && $this->next_due_date > $this->repeat_end_date) {
            return;
        }

        $nextDueDate = match($this->repeat_type) {
            'monthly' => $this->next_due_date->addMonth(),
            'quarterly' => $this->next_due_date->addMonths(3),
            'annual' => $this->next_due_date->addYear(),
            default => null
        };

        if ($nextDueDate) {
            $newReminder = $this->replicate(['is_completed', 'completed_at']);
            $newReminder->reminder_date = $nextDueDate;
            $newReminder->next_due_date = $nextDueDate;
            $newReminder->save();
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reminder) {
            if (!$reminder->next_due_date) {
                $reminder->next_due_date = $reminder->reminder_date;
            }
        });
    }
}
