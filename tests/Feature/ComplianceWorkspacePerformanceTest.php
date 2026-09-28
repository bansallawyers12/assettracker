<?php

use App\Models\BusinessEntity;
use App\Models\User;
use App\Services\ComplianceYearService;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['security.headers.force_https' => false]);
});

function complianceWorkspaceEntity(): BusinessEntity
{
    return BusinessEntity::query()->create([
        'legal_name' => 'Compliance Workspace Pty Ltd',
        'entity_type' => 'Company',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'compliance@example.test',
        'phone_number' => '0400000000',
    ]);
}

it('returns compliance workspace json for an entity', function () {
    $user = User::factory()->create();
    $entity = complianceWorkspaceEntity();

    $this->actingAs($user)
        ->getJson(route('entities.compliance.workspace', $entity))
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonStructure([
            'workspace' => [
                'year_record_id',
                'categories',
                'completeness',
                'available_years',
            ],
        ]);
});

it('includes content urls on compliance files without loading year records per file', function () {
    $user = User::factory()->create();
    $entity = complianceWorkspaceEntity();

    $payload = $this->actingAs($user)
        ->getJson(route('entities.compliance.workspace', $entity))
        ->assertSuccessful()
        ->json('workspace');

    $firstFile = collect($payload['categories'] ?? [])
        ->flatMap(fn (array $category) => $category['files'] ?? [])
        ->first();

    expect($firstFile)->not->toBeNull()
        ->and($firstFile)->toHaveKey('content_url');
});

it('skips reprovisioning when compliance categories already exist', function () {
    $entity = complianceWorkspaceEntity();
    $service = app(ComplianceYearService::class);
    $fyStart = FinancialYear::currentStart()->toDateString();

    $first = $service->findOrCreateYearRecord($entity, null, $fyStart);
    $category = $first->categories->first();
    expect($category)->not->toBeNull();

    $file = $category->files->first();
    expect($file)->not->toBeNull();
    $fileId = $file->id;
    $file->delete();

    $second = $service->findOrCreateYearRecord($entity, null, $fyStart);
    $reloadedFile = $second->categories->flatMap->files->firstWhere('id', $fileId);

    expect($reloadedFile)->toBeNull();
});
