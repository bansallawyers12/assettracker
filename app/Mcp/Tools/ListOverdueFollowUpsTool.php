<?php

namespace App\Mcp\Tools;

use App\Models\Reminder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List overdue follow-up reminders that are not yet completed.')]
#[IsReadOnly]
class ListOverdueFollowUpsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        if ($request->user() === null) {
            return Response::error('Authentication is required.');
        }

        $validated = $request->validate([
            'business_entity_id' => ['nullable', 'integer', 'exists:business_entities,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        $reminders = Reminder::query()
            ->with(['businessEntity:id,legal_name', 'asset:id,name'])
            ->overdue()
            ->when(
                isset($validated['business_entity_id']),
                fn ($query) => $query->where('business_entity_id', $validated['business_entity_id'])
            )
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Reminder $reminder): array => [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'content' => $reminder->content,
                'due_date' => optional($reminder->next_due_date)?->toDateString(),
                'priority' => $reminder->priority,
                'business_entity_id' => $reminder->business_entity_id,
                'business_entity' => $reminder->businessEntity?->legal_name,
                'asset_id' => $reminder->asset_id,
                'asset' => $reminder->asset?->name,
            ])
            ->all();

        return Response::structured([
            'count' => count($reminders),
            'reminders' => $reminders,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'business_entity_id' => $schema->integer()
                ->description('Optional business entity id to filter overdue follow-ups.'),
            'limit' => $schema->integer()
                ->description('Maximum number of reminders to return (1-50).')
                ->default(20),
        ];
    }
}
