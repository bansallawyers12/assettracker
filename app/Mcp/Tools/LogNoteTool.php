<?php

namespace App\Mcp\Tools;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Log a note against a business entity, optionally linked to an asset.')]
class LogNoteTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return Response::error('Authentication is required.');
        }

        $validated = $request->validate([
            'business_entity_id' => ['required', 'integer', BusinessEntity::ruleExistsOperational()],
            'content' => ['required', 'string', 'max:1000'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
        ]);

        $entity = BusinessEntity::query()->findOrFail($validated['business_entity_id']);

        if (! $user->can('update', $entity)) {
            return Response::error('You are not allowed to add notes for this entity.');
        }

        if ($entity->isClosed()) {
            return Response::error('This entity is closed, so notes cannot be added. Reopen it from Edit company profile by setting Status to Active.');
        }

        $assetId = $validated['asset_id'] ?? null;
        if ($assetId !== null) {
            $asset = Asset::query()->find($assetId);
            if ($asset === null || (int) $asset->business_entity_id !== (int) $entity->id) {
                return Response::error('The selected asset does not belong to the selected business entity.');
            }
        }

        $note = Note::query()->create([
            'content' => $validated['content'],
            'business_entity_id' => $entity->id,
            'asset_id' => $assetId,
            'user_id' => $user->id,
            'is_reminder' => false,
        ]);

        return Response::structured([
            'id' => $note->id,
            'content' => $note->content,
            'business_entity_id' => $note->business_entity_id,
            'asset_id' => $note->asset_id,
            'created_at' => optional($note->created_at)?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'business_entity_id' => $schema->integer()
                ->description('The business entity to attach the note to.')
                ->required(),
            'content' => $schema->string()
                ->description('The note text.')
                ->required(),
            'asset_id' => $schema->integer()
                ->description('Optional asset id belonging to the same entity.'),
        ];
    }
}
