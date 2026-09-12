<?php

use App\Mcp\Servers\CrmServer;
use App\Mcp\Tools\GetContactTool;
use App\Mcp\Tools\ListOverdueFollowUpsTool;
use App\Mcp\Tools\LogFollowUpTool;
use App\Mcp\Tools\LogNoteTool;
use App\Mcp\Tools\SearchContactsTool;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Person;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function crmMcpEntity(): BusinessEntity
{
    return BusinessEntity::create([
        'legal_name' => 'Acme Holdings Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'acme@example.test',
        'phone_number' => '0400000000',
    ]);
}

function crmMcpPerson(BusinessEntity $entity): Person
{
    $person = Person::query()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.test',
        'phone_number' => '0411111111',
        'status' => 'Active',
    ]);

    EntityPerson::query()->create([
        'business_entity_id' => $entity->id,
        'person_id' => $person->id,
        'role' => 'Director',
        'appointment_date' => now()->toDateString(),
        'role_status' => 'Active',
    ]);

    return $person->fresh();
}

function crmMcpAsset(BusinessEntity $entity): Asset
{
    return Asset::create([
        'business_entity_id' => $entity->id,
        'asset_type' => 'House Rented',
        'name' => '12 Example Street',
        'acquisition_date' => '2025-01-01',
        'acquisition_cost' => 500000,
        'current_value' => 520000,
        'status' => 'Active',
    ]);
}

it('searches persons, entities, and assets', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();
    crmMcpPerson($entity);
    crmMcpAsset($entity);

    CrmServer::actingAs($user)
        ->tool(SearchContactsTool::class, ['query' => 'Acme'])
        ->assertOk()
        ->assertSee('Acme Holdings Pty Ltd');

    CrmServer::actingAs($user)
        ->tool(SearchContactsTool::class, ['query' => 'Jane', 'type' => 'person'])
        ->assertOk()
        ->assertSee('Jane Smith');

    CrmServer::actingAs($user)
        ->tool(SearchContactsTool::class, ['query' => 'Example', 'type' => 'asset'])
        ->assertOk()
        ->assertSee('12 Example Street');
});

it('returns a person with recent notes and reminders', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();
    $person = crmMcpPerson($entity);

    Note::query()->create([
        'content' => 'Called about BAS lodgement',
        'business_entity_id' => $entity->id,
        'user_id' => $user->id,
        'is_reminder' => false,
    ]);

    Reminder::query()->create([
        'title' => 'Follow up Jane',
        'content' => 'Call about the trust deed',
        'reminder_date' => now()->subDay(),
        'next_due_date' => now()->subDay(),
        'repeat_type' => 'none',
        'business_entity_id' => $entity->id,
        'user_id' => $user->id,
    ]);

    CrmServer::actingAs($user)
        ->tool(GetContactTool::class, [
            'record_type' => 'person',
            'id' => $person->id,
        ])
        ->assertOk()
        ->assertSee('Jane Smith')
        ->assertSee('Called about BAS lodgement')
        ->assertSee('Follow up Jane');
});

it('lists overdue follow-ups', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();

    Reminder::query()->create([
        'title' => 'Overdue insurance',
        'content' => 'Pay the premium',
        'reminder_date' => now()->subDays(2),
        'next_due_date' => now()->subDays(2),
        'repeat_type' => 'none',
        'is_completed' => false,
        'business_entity_id' => $entity->id,
        'user_id' => $user->id,
    ]);

    CrmServer::actingAs($user)
        ->tool(ListOverdueFollowUpsTool::class, [])
        ->assertOk()
        ->assertSee('Overdue insurance');
});

it('logs a note against an entity', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();

    CrmServer::actingAs($user)
        ->tool(LogNoteTool::class, [
            'business_entity_id' => $entity->id,
            'content' => 'Discussed refinance options',
        ])
        ->assertOk()
        ->assertSee('Discussed refinance options');

    expect(Note::query()->where('business_entity_id', $entity->id)->value('content'))
        ->toBe('Discussed refinance options');
});

it('logs a follow-up reminder', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();
    $due = now()->addDays(4)->toDateString();

    CrmServer::actingAs($user)
        ->tool(LogFollowUpTool::class, [
            'content' => 'Send signed minutes',
            'reminder_date' => $due,
            'business_entity_id' => $entity->id,
        ])
        ->assertOk()
        ->assertSee('Send signed minutes');

    $reminder = Reminder::query()->sole();

    expect($reminder->content)->toBe('Send signed minutes')
        ->and($reminder->user_id)->toBe($user->id)
        ->and($reminder->business_entity_id)->toBe($entity->id)
        ->and($reminder->next_due_date->toDateString())->toBe($due);
});

it('does not let viewers log notes', function () {
    $user = User::factory()->viewer()->create();
    $entity = crmMcpEntity();

    CrmServer::actingAs($user)
        ->tool(LogNoteTool::class, [
            'business_entity_id' => $entity->id,
            'content' => 'Should be blocked',
        ])
        ->assertHasErrors();

    expect(Note::query()->count())->toBe(0);
});

it('does not let viewers log follow-ups', function () {
    $user = User::factory()->viewer()->create();
    $due = now()->addDay()->toDateString();

    CrmServer::actingAs($user)
        ->tool(LogFollowUpTool::class, [
            'content' => 'Should be blocked',
            'reminder_date' => $due,
        ])
        ->assertHasErrors();

    expect(Reminder::query()->count())->toBe(0);
});

it('attaches an asset follow-up to the asset entity', function () {
    $user = User::factory()->create();
    $entity = crmMcpEntity();
    $asset = crmMcpAsset($entity);
    $due = now()->addDays(2)->toDateString();

    CrmServer::actingAs($user)
        ->tool(LogFollowUpTool::class, [
            'content' => 'Inspect the roof',
            'reminder_date' => $due,
            'asset_id' => $asset->id,
        ])
        ->assertOk()
        ->assertSee('Inspect the roof');

    $reminder = Reminder::query()->sole();

    expect($reminder->asset_id)->toBe($asset->id)
        ->and($reminder->business_entity_id)->toBe($entity->id);
});

it('treats percent signs in search as literal text', function () {
    $user = User::factory()->create();
    crmMcpEntity();

    CrmServer::actingAs($user)
        ->tool(SearchContactsTool::class, ['query' => '%', 'type' => 'entity'])
        ->assertOk()
        ->assertDontSee('Acme Holdings Pty Ltd');
});
