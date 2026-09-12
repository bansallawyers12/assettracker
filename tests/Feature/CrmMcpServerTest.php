<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('registers the crm mcp web endpoint', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => $route->uri() === 'mcp/crm');

    expect($routes)->not->toBeEmpty()
        ->and($routes->contains(fn ($route) => in_array('POST', $route->methods(), true)))->toBeTrue()
        ->and($routes->first(fn ($route) => in_array('POST', $route->methods(), true))?->gatherMiddleware())
        ->toContain('auth:sanctum')
        ->toContain(EnsureAccountActive::class)
        ->toContain('throttle:mcp');
});

it('rejects unauthenticated mcp requests', function () {
    $this->postJson('/mcp/crm', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
        'params' => [
            'protocolVersion' => '2025-03-26',
            'capabilities' => (object) [],
            'clientInfo' => [
                'name' => 'pest',
                'version' => '1.0.0',
            ],
        ],
    ])->assertUnauthorized();
});

it('allows sanctum authenticated mcp initialize requests', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mcp')->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/crm', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'pest',
                    'version' => '1.0.0',
                ],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('result.serverInfo.name', 'CRM Server');
});

it('rejects mcp requests from deactivated accounts', function () {
    $user = User::factory()->create(['is_active' => false]);
    $token = $user->createToken('mcp')->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/crm', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'pest',
                    'version' => '1.0.0',
                ],
            ],
        ])
        ->assertUnauthorized();
});
