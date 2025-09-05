<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\EncryptsAttributes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, EncryptsAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'two_factor_secret',
        'two_factor_backup_codes',
        'two_factor_enabled',
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
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
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
