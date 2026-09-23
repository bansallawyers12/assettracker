<?php

use App\Models\BusinessEntity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('opens the lodgement report as a collapsed list of entities with pending work', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-23'));
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
        ->assertSee('entities are missing lodgements')
        ->assertSee('Entities missing lodgements')
        ->assertSee('Tax return')
        ->assertSee('GST')
        ->assertSee('ASIC')
        ->assertSee('Open an entity to see what is still outstanding.')
        ->assertSee('Last 3 years')
        ->assertSee('Outstanding')
        ->assertSee('FY 2024-2025')
        ->assertSee('Today')
        ->assertSee('End of last FY')
        ->assertDontSee('End of month')
        ->assertDontSee('All statuses');
});

it('limits the lodgement report to the selected year preset', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-23'));
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('financial-reports.ato-lodgements', ['years' => 'this']))
        ->assertSuccessful()
        ->assertSee('FY 2026-2027 – 2026-2027');

    $this->actingAs($user)
        ->get(route('financial-reports.ato-lodgements', ['years' => 'all']))
        ->assertSuccessful()
        ->assertSee('FY 2017-2018');
});
