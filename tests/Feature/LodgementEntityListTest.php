<?php

use App\Models\BusinessEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('opens the lodgement report as a collapsed list of entities with pending work', function () {
    $user = User::factory()->create();

    BusinessEntity::create([
        'legal_name' => 'Harbour Test Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'harbour@example.test',
        'phone_number' => '0400000000',
    ]);

    $this->actingAs($user)
        ->get(route('financial-reports.ato-lodgements'))
        ->assertSuccessful()
        ->assertSee('entities have something pending')
        ->assertSee('Entities pending')
        ->assertSee('Open an entity to see what is still outstanding.');
});
