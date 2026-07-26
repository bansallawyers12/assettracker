<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\EncryptsAttributes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, EncryptsAttributes {
        EncryptsAttributes::setAttribute as setEncryptedAttribute;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_hash',
        'email_verified_at',
        'is_active',
        'password',
        'phone',
        'address',
        'two_factor_secret',
        'two_factor_backup_codes',
        'two_factor_enabled',
        'logins_without_two_factor_count',
        'password_changed_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be encrypted.
     * This overrides the trait's default empty array.
     *
     * @var array
     */
    protected $encrypted = [
        'email',
        'phone',
        'address',
        'two_factor_secret',
        'two_factor_backup_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'logins_without_two_factor_count' => 'integer',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Override setAttribute to compute a deterministic email_hash alongside
     * the encrypted email, so Auth::attempt() can perform DB lookups without
     * decrypting every row.
     */
    public function setAttribute($key, $value): mixed
    {
        if ($key === 'email' && !empty($value) && !$this->isAlreadyEncrypted($value)) {
            $this->attributes['email_hash'] = hash_hmac('sha256', strtolower(trim((string) $value)), config('app.key'));
        }

        return $this->setEncryptedAttribute($key, $value);
    }

    /**
     * Whether TOTP 2FA is fully enabled (flag + secret present).
     */
    public function hasFullyEnabledTwoFactor(): bool
    {
        return $this->two_factor_enabled && filled($this->two_factor_secret);
    }

    /**
     * Record a successful full login (after password, and after 2FA when enrolled).
     *
     * Uses a targeted query update so unrelated in-memory dirty attributes
     * (e.g. after consuming a backup code) are not written back.
     */
    public function recordLogin(?string $ip): void
    {
        $ip = is_string($ip) ? trim($ip) : null;
        if ($ip === '') {
            $ip = null;
        }

        $timestamp = now();

        static::query()->whereKey($this->getKey())->update([
            'last_login_at' => $timestamp,
            'last_login_ip' => $ip,
        ]);

        $this->forceFill([
            'last_login_at' => $timestamp,
            'last_login_ip' => $ip,
        ]);
        $this->syncOriginalAttributes(['last_login_at', 'last_login_ip']);
    }

    /**
     * Primary portal administrator (config/admin.php). Used for user creation and grace-period exceptions.
     */
    public function isPrimaryAdministrator(): bool
    {
        $configured = strtolower(trim((string) config('admin.email')));

        return $configured !== ''
            && strcasecmp(strtolower(trim((string) $this->email)), $configured) === 0;
    }

    /**
     * Whether the account may sign in. Missing `is_active` (e.g. before migrations) is treated as active.
     */
    public function isAccountActive(): bool
    {
        if (! array_key_exists('is_active', $this->getAttributes())) {
            return true;
        }

        return (bool) $this->getAttribute('is_active');
    }

    public function contactLists()
    {
        return $this->hasMany(ContactList::class);
    }

    /**
     * Get the emails associated with the user.
     */
    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Get the email drafts created by the user.
     */
    public function emailDrafts()
    {
        return $this->hasMany(EmailDraft::class);
    }

    /**
     * Get the business entities owned by the user.
     */
    public function businessEntities()
    {
        return $this->hasMany(BusinessEntity::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'created_by');
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function realEstateCompanies()
    {
        return $this->hasMany(RealEstateCompany::class);
    }

    /**
     * Hard-delete is only safe when the user owns no shared portfolio or ledger data.
     * Prefer deactivate when related records exist.
     */
    public function canBeDeleted(): bool
    {
        return $this->deleteBlockedReason() === null;
    }

    public function deleteBlockedReason(): ?string
    {
        if ($this->hasRelatedDeleteBlocker('business_entities_count', 'businessEntities')) {
            return __('This user cannot be deleted because they own business entities. Reassign those entities or deactivate the user instead.');
        }

        if ($this->hasRelatedDeleteBlocker('journal_entries_count', 'journalEntries')) {
            return __('This user cannot be deleted because they created journal entries. Deactivate the user instead.');
        }

        if ($this->hasRelatedDeleteBlocker('real_estate_companies_count', 'realEstateCompanies')) {
            return __('This user cannot be deleted because they own real estate company records. Reassign or remove those records, or deactivate the user instead.');
        }

        if ($this->hasRelatedDeleteBlocker('notes_count', 'notes')) {
            return __('This user cannot be deleted because they authored notes. Deactivate the user instead.');
        }

        if ($this->hasRelatedDeleteBlocker('reminders_count', 'reminders')) {
            return __('This user cannot be deleted because they own reminders. Deactivate the user instead.');
        }

        return null;
    }

    /**
     * @param  non-empty-string  $countAttribute
     * @param  non-empty-string  $relation
     */
    private function hasRelatedDeleteBlocker(string $countAttribute, string $relation): bool
    {
        if (array_key_exists($countAttribute, $this->getAttributes())) {
            return (int) $this->getAttribute($countAttribute) > 0;
        }

        return $this->{$relation}()->exists();
    }
}
