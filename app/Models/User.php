<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
