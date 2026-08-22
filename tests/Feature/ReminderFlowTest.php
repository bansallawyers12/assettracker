<?php

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('stores a reminder from the dashboard form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reminders.store'), [
            'content' => 'Pay insurance premium',
            'reminder_date' => now()->addDays(3)->toDateString(),
            'repeat_type' => 'none',
        ])
        ->assertRedirect(route('bills-tasks.index'))
        ->assertSessionHas('success');

    $reminder = Reminder::query()->sole();

    expect($reminder->content)->toBe('Pay insurance premium')
        ->and($reminder->user_id)->toBe($user->id)
        ->and($reminder->title)->toBe('Pay insurance premium')
        ->and($reminder->next_due_date->toDateString())->toBe(now()->addDays(3)->toDateString());
});

it('shows a reminder and can complete or extend it', function () {
    $user = User::factory()->create();
    $dueDate = now()->addDays(5);

    $reminder = Reminder::query()->create([
        'title' => 'Renew lease',
        'content' => 'Contact tenant about renewal',
        'reminder_date' => $dueDate,
        'next_due_date' => $dueDate,
        'repeat_type' => 'none',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('reminders.show', $reminder))
        ->assertSuccessful()
        ->assertSee('Renew lease')
        ->assertSee('Contact tenant about renewal');

    $this->actingAs($user)
        ->post(route('reminders.extend', $reminder), ['days' => 3])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($reminder->fresh()->next_due_date->toDateString())
        ->toBe($dueDate->copy()->addDays(3)->toDateString());

    $this->actingAs($user)
        ->post(route('reminders.complete', $reminder))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($reminder->fresh()->is_completed)->toBeTrue();
});
