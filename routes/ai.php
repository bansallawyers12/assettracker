<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Mcp\Servers\CrmServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/crm', CrmServer::class)
    ->middleware(['auth:sanctum', EnsureAccountActive::class, 'throttle:mcp']);
