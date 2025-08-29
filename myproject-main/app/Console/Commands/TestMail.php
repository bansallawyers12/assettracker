<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class TestMail extends Command
{
    protected $signature = 'mail:test';
    protected $description = 'Test the mail system';

    public function handle()
    {
        try {
            $user = User::first();
            if (!$user) {
                $this->error('No user found in the database');
                return;
            }

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->notify(new \App\Notifications\TwoFactorCode($code));

            $this->info('Test email sent successfully to: ' . $user->email);
        } catch (\Exception $e) {
            $this->error('Error sending email: ' . $e->getMessage());
        }
    }
} 