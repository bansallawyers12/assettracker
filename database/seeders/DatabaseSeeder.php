<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed email templates
        \App\Models\EmailTemplate::create([
            'name' => 'Welcome Email',
            'subject' => 'Welcome to Our Service',
            'description' => 'Dear [Name],\n\nWelcome to our service! We are excited to have you on board.\n\nBest regards,\n[Your Company]',
            'user_id' => 1,
        ]);

        \App\Models\EmailTemplate::create([
            'name' => 'Follow-up Email',
            'subject' => 'Following Up on Our Discussion',
            'description' => 'Hi [Name],\n\nI hope this email finds you well. I wanted to follow up on our recent discussion.\n\nPlease let me know if you have any questions.\n\nBest regards,\n[Your Name]',
            'user_id' => 1,
        ]);

        \App\Models\EmailTemplate::create([
            'name' => 'Meeting Request',
            'subject' => 'Meeting Request - [Topic]',
            'description' => 'Hello [Name],\n\nI would like to schedule a meeting to discuss [topic].\n\nPlease let me know your availability for next week.\n\nBest regards,\n[Your Name]',
            'user_id' => 1,
        ]);
    }
}
