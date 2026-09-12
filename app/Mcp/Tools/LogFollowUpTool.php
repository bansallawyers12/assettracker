<?php

namespace App\Mcp\Tools;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a follow-up reminder, optionally linked to a business entity or asset.')]
class LogFollowUpTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return Response::error('Authentication is required.');
        }

        if (! $user->canMutatePortfolio()) {
            return Response::error('You are not allowed to create follow-ups.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'reminder_date' => ['required', 'date', 'after_or_equal:today'],
            'title' => ['nullable', 'string', 'max:255'],
            'repeat_type' => ['nullable', 'in:none,monthly,quarterly,annual'],
            'business_entity_id' => ['nullable', 'integer', BusinessEntity::ruleExistsOperational()],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $entityId = $validated['business_entity_id'] ?? null;
        $assetId = $validated['asset_id'] ?? null;

        if ($assetId !== null) {
            $asset = Asset::query()->find($assetId);
            if ($asset === null) {
                return Response::error('The selected asset was not found.');
            }

            if ($entityId !== null && (int) $asset->business_entity_id !== (int) $entityId) {
                return Response::error('The selected asset does not belong to the selected business entity.');
            }

            $entityId = (int) $asset->business_entity_id;
        }

        if ($entityId !== null) {
            $entity = BusinessEntity::query()->findOrFail($entityId);
            if ($entity->isClosed() || ! $entity->isOperationalEntity()) {
                return Response::error('Follow-ups cannot be added for this entity.');
            }
        }

        $title = trim((string) ($validated['title'] ?? ''));
        if ($title === '') {
            $firstLine = (string) Str::of($validated['content'])->before("\n")->trim();
            $title = $firstLine !== '' ? Str::limit($firstLine, 200) : 'Reminder';
        }

        $due = Carbon::parse($validated['reminder_date']);

        $reminder = new Reminder([
            'title' => $title,
            'content' => $validated['content'],
            'reminder_date' => $due,
            'next_due_date' => $due,
            'repeat_type' => $validated['repeat_type'] ?? 'none',
            'business_entity_id' => $entityId,
            'asset_id' => $assetId,
            'priority' => $validated['priority'] ?? 'medium',
            'user_id' => $user->id,
        ]);
        $reminder->save();

        return Response::structured([
            'id' => $reminder->id,
            'title' => $reminder->title,
            'content' => $reminder->content,
            'due_date' => optional($reminder->next_due_date)?->toDateString(),
            'business_entity_id' => $reminder->business_entity_id,
            'asset_id' => $reminder->asset_id,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()
                ->description('What needs to be followed up.')
                ->required(),
            'reminder_date' => $schema->string()
                ->description('Due date (YYYY-MM-DD), today or later.')
                ->required(),
            'title' => $schema->string()
                ->description('Optional short title. Defaults to the first line of content.'),
            'repeat_type' => $schema->string()
                ->enum(['none', 'monthly', 'quarterly', 'annual'])
                ->description('How often the follow-up repeats.')
                ->default('none'),
            'business_entity_id' => $schema->integer()
                ->description('Optional business entity to attach the follow-up to.'),
            'asset_id' => $schema->integer()
                ->description('Optional asset to attach the follow-up to.'),
            'priority' => $schema->string()
                ->enum(['low', 'medium', 'high'])
                ->default('medium'),
        ];
    }
}
