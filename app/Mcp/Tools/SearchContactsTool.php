<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\CrmRecordSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search persons, business entities, and assets by name, email, phone, address, or registration number.')]
#[IsReadOnly]
class SearchContactsTool extends Tool
{
    public function __construct(private CrmRecordSearch $search) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($request->user() === null) {
            return Response::error('Authentication is required.');
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'type' => ['nullable', 'in:all,person,entity,asset'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $results = $this->search->search(
            $validated['query'],
            $validated['type'] ?? 'all',
            (int) ($validated['limit'] ?? 20),
        );

        return Response::structured([
            'count' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Name, email, phone, address, or other identifying text to search for.')
                ->required(),
            'type' => $schema->string()
                ->enum(['all', 'person', 'entity', 'asset'])
                ->description('Limit results to persons, business entities, or assets.')
                ->default('all'),
            'limit' => $schema->integer()
                ->description('Maximum number of results to return (1-50).')
                ->default(20),
        ];
    }
}
