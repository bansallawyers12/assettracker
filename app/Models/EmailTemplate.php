<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'subject',
        'description',
        'user_id',
    ];

    /**
     * Get the user that owns the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the email drafts that use this template.
     */
    public function emailDrafts(): HasMany
    {
        return $this->hasMany(EmailDraft::class);
    }

    /**
     * Scope a query to only include templates for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the template with variables replaced.
     */
    public function getProcessedSubjectAttribute(): string
    {
        return $this->replaceVariables($this->subject);
    }

    /**
     * Get the template body with variables replaced.
     */
    public function getProcessedDescriptionAttribute(): string
    {
        return $this->replaceVariables($this->description);
    }

    /**
     * Replace common variables in template text.
     */
    private function replaceVariables(string $text): string
    {
        $variables = [
            '[Name]' => '{{recipient_name}}',
            '[Your Name]' => '{{sender_name}}',
            '[Your Company]' => '{{company_name}}',
            '[Date]' => '{{current_date}}',
        ];

        return str_replace(array_keys($variables), array_values($variables), $text);
    }
}