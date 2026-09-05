<?php

use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('renders document checklist actions in a context menu instead of a table column', function () {
    $user = User::factory()->create();
    $entity = BusinessEntity::create([
        'legal_name' => 'Documents Workspace Trust',
        'entity_type' => 'Trust',
        'status' => 'Active',
        'registered_address' => '1 Test Street',
        'registered_email' => 'documents-workspace@example.test',
        'phone_number' => '0400000000',
    ]);

    $category = DocumentCategory::query()->create([
        'business_entity_id' => $entity->id,
        'title' => 'Trust Documents',
        'sort_order' => 0,
    ]);

    Document::query()->create([
        'business_entity_id' => $entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => 'Trust Deed',
        'file_name' => 'trust-deed.pdf',
        'path' => 'documents/trust-deed.pdf',
        'type' => 'other',
        'user_id' => $user->id,
    ]);

    Document::query()->create([
        'business_entity_id' => $entity->id,
        'document_category_id' => $category->id,
        'checklist_label' => 'Change of Trust Name',
        'file_name' => null,
        'path' => null,
        'type' => 'other',
        'user_id' => $user->id,
    ]);

    $html = $this->actingAs($user)
        ->get(route('business-entities.show', $entity))
        ->assertSuccessful()
        ->getContent();

    expect($html)
        ->toContain('documents-workspace')
        ->toContain('Trust Deed')
        ->toContain('Change of Trust Name')
        ->toContain('doc-context-menu')
        ->toContain('doc-ctx-upload')
        ->toContain('Right-click a checklist row')
        ->toContain('data-has-file="1"')
        ->toContain('data-has-file="0"')
        ->not->toContain('doc-col-actions')
        ->not->toContain('>Actions</th>')
        ->not->toContain('doc-action-muted doc-rename-slot');
});
