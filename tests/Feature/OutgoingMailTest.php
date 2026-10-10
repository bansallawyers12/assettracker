<?php

use App\Mail\ContactEmail;
use App\Models\BusinessEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends a company email to the typed recipient and cc', function () {
    Mail::fake();

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Placeholder Holdings',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'placeholder@example.invalid',
        'phone_number' => '0400000000',
    ]);

    $this->actingAs($user)
        ->postJson(route('business-entities.send-email', $entity), [
            'to_email' => 'tenant@clientmail.com',
            'cc_email' => 'accounts@clientmail.com',
            'subject' => 'Lease documents',
            'message' => '<p>Please find the documents attached.</p>',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(ContactEmail::class, function (ContactEmail $mail): bool {
        return $mail->hasTo('tenant@clientmail.com')
            && $mail->hasCc('accounts@clientmail.com')
            && $mail->subject === 'Lease documents';
    });
});

it('rejects a company email when the only address is a placeholder', function () {
    Mail::fake();

    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Empty Mail Co',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'placeholder@example.invalid',
        'phone_number' => '0400000000',
    ]);

    $this->actingAs($user)
        ->postJson(route('business-entities.send-email', $entity), [
            'subject' => 'No recipient',
            'message' => 'Hello',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    Mail::assertNothingSent();
});

it('sends inbox mail from the configured account and replies to the selected address', function () {
    Mail::fake();
    config([
        'mail.from.address' => 'bansalproperty01@gmail.com',
        'mail.from.name' => 'Assettracker',
    ]);

    $user = User::factory()->create([
        'email' => 'staff@example.com',
    ]);

    $this->actingAs($user)
        ->postJson(route('emails.send'), [
            'from_email' => 'staff@example.com',
            'to_email' => 'client@clientmail.com',
            'subject' => 'Fwd: Invoice',
            'message' => 'Forwarding the invoice.',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertSent(ContactEmail::class, function (ContactEmail $mail): bool {
        return $mail->hasTo('client@clientmail.com')
            && $mail->envelope()->from === null
            && $mail->hasReplyTo('staff@example.com');
    });
});
