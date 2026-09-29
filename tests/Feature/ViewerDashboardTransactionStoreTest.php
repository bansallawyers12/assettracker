<?php

use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Models\ChartOfAccount;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows viewers to store a dashboard transaction', function () {
    $this->seed(ChartOfAccountSeeder::class);

    $viewer = User::factory()->viewer()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Viewer Record Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'viewer-record@example.test',
        'phone_number' => '0400000001',
        'user_id' => $viewer->id,
    ]);
    $bank = BankAccount::create([
        'business_entity_id' => $entity->id,
        'bank_name' => 'Test Bank',
        'bsb' => '123456',
        'account_number' => '12345678',
        'account_name' => 'Operating',
        'account_purpose' => BankAccount::PURPOSE_GENERAL,
    ]);
    $account = ChartOfAccount::query()->where('is_active', true)->firstOrFail();

    $response = $this->actingAs($viewer)->post(route('business-entities.transactions.store', $entity), [
        'date' => '2026-09-29',
        'payment_status' => 'paid',
        'paid_at' => '2026-09-29',
        'payment_channel' => Transaction::PAYMENT_CHANNEL_BANK_ACCOUNT,
        'bank_account_id' => $bank->id,
        'paid_by_select' => 'be:'.$entity->id,
        'lines' => [
            [
                'direction' => 'expense',
                'chart_of_account_id' => $account->id,
                'amount' => '50.00',
                'description' => 'Council rates',
                'gst_basis' => 'none',
            ],
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($response->status())->not->toBe(403);
});

it('still blocks viewers from entity update mutations including transaction save', function () {
    $viewer = User::factory()->viewer()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Viewer Edit Block Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '2 Test Street',
        'registered_email' => 'viewer-edit-block@example.test',
        'phone_number' => '0400000002',
        'user_id' => $viewer->id,
    ]);

    expect(Gate::forUser($viewer)->allows('recordTransaction', $entity))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('update', $entity))->toBeFalse();
});

it('authorizes dashboard store via recordTransaction not update', function () {
    $controller = file_get_contents(app_path('Http/Controllers/BusinessEntityController.php'));

    expect($controller)->toContain("authorize('recordTransaction', \$businessEntity)")
        ->and($controller)->toContain("authorize('recordTransaction', \$targetEntity)")
        ->and($controller)->toContain("authorize('update', \$bookingEntity)");
});
