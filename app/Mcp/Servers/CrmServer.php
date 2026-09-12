<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetContactTool;
use App\Mcp\Tools\ListOverdueFollowUpsTool;
use App\Mcp\Tools\LogFollowUpTool;
use App\Mcp\Tools\LogNoteTool;
use App\Mcp\Tools\SearchContactsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('CRM Server')]
#[Version('1.0.0')]
#[Instructions('Exposes CRM capabilities for Asset Tracker over the Model Context Protocol.')]
class CrmServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        SearchContactsTool::class,
        GetContactTool::class,
        ListOverdueFollowUpsTool::class,
        LogNoteTool::class,
        LogFollowUpTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
