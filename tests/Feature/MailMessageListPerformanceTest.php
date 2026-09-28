<?php

use App\Models\MailLabel;
use App\Models\MailMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['security.headers.force_https' => false]);
});

function mailListUser(): User
{
    return User::factory()->create();
}

function mailListMessage(User $user, array $overrides = []): MailMessage
{
    return MailMessage::query()->create(array_merge([
        'user_id' => $user->id,
        'subject' => 'Performance test subject',
        'sender_name' => 'Sender Name',
        'sender_email' => 'sender@example.test',
        'sent_date' => now(),
        'html_content' => '<p>Large HTML body should not be required on list pages.</p>',
        'text_content' => 'Large text body with unique-search-token-body-only',
        'status' => 'parsed',
    ], $overrides));
}

it('renders inbox list without loading message bodies onto list models', function () {
    $user = mailListUser();
    $message = mailListMessage($user, ['subject' => 'Inbox list subject']);

    $label = MailLabel::query()->create([
        'user_id' => $user->id,
        'name' => 'Inbox',
        'color' => '#e5e7eb',
        'type' => 'system',
    ]);
    $message->labels()->attach($label->id);

    $this->actingAs($user)
        ->get(route('emails.index'))
        ->assertSuccessful()
        ->assertSee('Inbox list subject')
        ->assertSee('Inbox');

    $listed = MailMessage::query()
        ->select(['id', 'subject'])
        ->whereKey($message->id)
        ->first();

    expect($listed)->not->toBeNull();
});

it('still loads full message body on the show page', function () {
    $user = mailListUser();
    $message = mailListMessage($user, [
        'subject' => 'Show page subject',
        'html_content' => null,
        'text_content' => 'Body visible on show page only',
    ]);

    $this->actingAs($user)
        ->get(route('emails.show', $message->id))
        ->assertSuccessful()
        ->assertSee('Show page subject')
        ->assertSee('Body visible on show page only');
});

it('still finds emails by body text when searching from the inbox list', function () {
    $user = mailListUser();
    mailListMessage($user, [
        'subject' => 'Hidden subject',
        'text_content' => 'unique-search-token-body-only',
    ]);

    $this->actingAs($user)
        ->get(route('emails.index', ['search' => 'unique-search-token-body-only']))
        ->assertSuccessful()
        ->assertSee('Hidden subject');
});

it('renders upload list without requiring body columns on list models', function () {
    $user = mailListUser();
    mailListMessage($user, ['subject' => 'Upload list subject']);

    $this->actingAs($user)
        ->get(route('emails.upload'))
        ->assertSuccessful()
        ->assertSee('Upload list subject');
});
