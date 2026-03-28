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
     * Primary portal administrator (config/admin.php). Used for user creation and grace-period exceptions.
     */
    public function isPrimaryAdministrator(): bool
    {
        $configured = strtolower(trim((string) config('admin.email')));

        return $configured !== ''
            && strcasecmp(strtolower(trim((string) $this->email)), $configured) === 0;
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
}
