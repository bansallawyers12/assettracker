---
paths:
  - routes/ai.php
---

# Routes

## CRM MCP web endpoint
The CRM MCP server is registered at POST /mcp/crm via Mcp::web in routes/ai.php (auto-loaded by laravel/mcp — do not also register ai.php in bootstrap/app.php). Protect it with auth:sanctum + throttle:mcp. Clients must send Authorization: Bearer <sanctum-token>. The mcp rate limiter lives in RouteServiceProvider.

## CRM MCP token auth and inactive users
POST /mcp/crm is token-only (no web/CSRF group). Middleware order is auth:sanctum, EnsureAccountActive, throttle:mcp. Deactivated users must get JSON 401, not a login redirect. Do not also load routes/ai.php from bootstrap/app.php.
