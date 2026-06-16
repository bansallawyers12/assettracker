<?php

namespace App\Models;

use App\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory, EncryptsAttributes;

    protected $fillable = [
        'email',
        'password',
        'email_signature',
        'display_name',
        'status',
        'user_id',
        'type',
        'error_message',
    ];

    /**
     * Fields encrypted at rest via EncryptsAttributes.
     * The password column must be TEXT (see migration widen_emails_password_to_text).
     */
    protected $encrypted = ['password'];

    /**
     * Never expose the mailbox password in JSON / API responses.
     */
    protected $hidden = ['password'];
}
