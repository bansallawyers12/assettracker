<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendEmailVerificationNotificationUnlessMailFails
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $e) {
            report($e);
        }
    }
}
