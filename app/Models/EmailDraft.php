<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_email',
        'to_email',
        'cc_email',
        'bcc_email',
        'subject',
        'message',
        'attachments',
        'business_entity_id',
        'template_id',
        'scheduled_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the draft.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business entity associated with the draft.
     */
    public function businessEntity(): BelongsTo
    {
        return $this->belongsTo(BusinessEntity::class);
    }

    /**
     * Get the email template used for the draft.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /**
     * Scope a query to only include drafts for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include scheduled drafts.
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->where('scheduled_at', '>', now());
    }

    /**
     * Scope a query to only include drafts ready to be sent.
     */
    public function scopeReadyToSend($query)
    {
        return $query->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now());
    }

    /**
     * Check if the draft is scheduled.
     */
    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_at);
    }

    /**
     * Check if the draft is ready to be sent.
     */
    public function isReadyToSend(): bool
    {
        return $this->isScheduled() && $this->scheduled_at <= now();
    }

    /**
     * Get the formatted scheduled time.
     */
    public function getFormattedScheduledTimeAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : 'Not scheduled';
    }
}
