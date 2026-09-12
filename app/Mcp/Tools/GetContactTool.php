<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\CrmActivityFeed;
use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Person;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get a person, business entity, or asset plus recent notes, reminders, mail, documents, transactions, and invoices.')]
#[IsReadOnly]
class GetContactTool extends Tool
{
    public function __construct(private CrmActivityFeed $activity) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();
        if ($user === null) {
            return Response::error('Authentication is required.');
        }

        $validated = $request->validate([
            'record_type' => ['required', 'in:person,entity,asset'],
            'id' => ['required', 'integer', 'min:1'],
            'activity_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['activity_limit'] ?? 20);

        return match ($validated['record_type']) {
            'person' => $this->person((int) $validated['id'], $limit),
            'entity' => $this->entity((int) $validated['id'], $limit),
            'asset' => $this->asset((int) $validated['id'], $limit),
        };
    }

    private function person(int $id, int $limit): Response|ResponseFactory
    {
        $person = Person::query()->with(['businessEntities', 'entityPersons.businessEntity'])->find($id);
        if ($person === null) {
            return Response::error('Person not found.');
        }

        $entityIds = $person->businessEntities->modelKeys();

        return Response::structured([
            'record_type' => 'person',
            'person' => [
                'id' => $person->id,
                'name' => $person->displayName(),
                'email' => $person->email,
                'phone' => $person->phone_number,
                'status' => $person->status,
                'address' => $person->address,
            ],
            'roles' => $person->entityPersons->map(fn ($role): array => [
                'entity_id' => $role->business_entity_id,
                'entity' => $role->businessEntity?->legal_name,
                'role' => $role->role,
                'role_status' => $role->role_status,
            ])->values()->all(),
            'activity' => $this->activity->for($entityIds, [], $person->email, $limit),
        ]);
    }

    private function entity(int $id, int $limit): Response|ResponseFactory
    {
        $entity = BusinessEntity::query()->with(['persons.person', 'assets'])->find($id);
        if ($entity === null) {
            return Response::error('Business entity not found.');
        }

        $assetIds = $entity->assets->modelKeys();

        return Response::structured([
            'record_type' => 'entity',
            'entity' => [
                'id' => $entity->id,
                'legal_name' => $entity->legal_name,
                'trading_name' => $entity->trading_name,
                'entity_type' => $entity->entity_type,
                'status' => $entity->status,
                'phone_number' => $entity->phone_number,
                'registered_email' => $entity->registered_email,
                'is_closed' => $entity->isClosed(),
            ],
            'people' => $entity->persons->map(fn ($role): array => [
                'person_id' => $role->person_id,
                'name' => $role->person?->displayName(),
                'role' => $role->role,
                'role_status' => $role->role_status,
            ])->values()->all(),
            'assets' => $entity->assets->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_type' => $asset->asset_type,
                'status' => $asset->status,
            ])->values()->all(),
            'activity' => $this->activity->for([$entity->id], $assetIds, null, $limit),
        ]);
    }

    private function asset(int $id, int $limit): Response|ResponseFactory
    {
        $asset = Asset::query()->with('businessEntity')->find($id);
        if ($asset === null) {
            return Response::error('Asset not found.');
        }

        $entityId = $asset->business_entity_id;

        return Response::structured([
            'record_type' => 'asset',
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_type' => $asset->asset_type,
                'status' => $asset->status,
                'address' => $asset->address,
                'business_entity_id' => $entityId,
                'business_entity' => $asset->businessEntity?->legal_name,
            ],
            'activity' => $this->activity->for(
                [],
                [$asset->id],
                null,
                $limit,
            ),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'record_type' => $schema->string()
                ->enum(['person', 'entity', 'asset'])
                ->description('The kind of record to load.')
                ->required(),
            'id' => $schema->integer()
                ->description('The record id.')
                ->required(),
            'activity_limit' => $schema->integer()
                ->description('Maximum activity items to return (1-50).')
                ->default(20),
        ];
    }
}
