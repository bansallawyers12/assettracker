<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $code)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {   //dd($this->code);
        return (new MailMessage)
            ->subject('Your Login Verification Code')
            ->greeting('Hello!')
            ->line('Your login verification code is:')
            ->line('<h1 style="font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 5px; color: #4F46E5;">' . $this->code . '</h1>')
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not request this code, please ignore this email.')
            ->salutation('Thank you for using our service!');
    }
} 