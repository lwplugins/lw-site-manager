# Built-in MCP Server

> Since v1.2.0.

## Overview

LW Site Manager ships its own **MCP (Model Context Protocol) server**, built on the official [`wordpress/mcp-adapter`](https://github.com/WordPress/mcp-adapter) library (GPL-2.0-or-later). It lets an MCP client — Claude Code, Claude Desktop, ChatGPT, or any other — connect directly to the site and use the plugin's abilities as tools, plus discover and load the bundled [Skills](skills.md).

It is **additive and backward-compatible**: the existing WordPress Abilities REST surface (`/wp-json/wp-abilities/v1/…`) is untouched, so any prior REST/proxy integration keeps working. If the adapter library is missing (e.g. a source checkout without `vendor/`), the MCP layer is a silent no-op.

| | |
|---|---|
| Endpoint | `https://YOUR-SITE/wp-json/mcp/lw-site-manager` |
| Transport | Streamable HTTP (session via the `Mcp-Session-Id` header) |
| Server id / name | `lw-site-manager` / "LW Site Manager" |
| Default state | **Disabled** |
| Access | Administrator only (`manage_options`) + each ability's own capability |
| Auth | WordPress Application Password over HTTP Basic |

## Enabling

The server is **off by default**. Enable it in the admin:

**LW Plugins → AI / MCP →** tick *"Enable the built-in MCP server"* **→ Save.**

The settings page then shows a ready-to-paste `.mcp.json` snippet pre-filled with your endpoint, and the list of bundled skills.

State is stored in two options:

| Option | Purpose |
|--------|---------|
| `lw_site_manager_mcp_enabled` | `true` when the server is on |
| `lw_site_manager_mcp_domain` | the site host recorded at enable time (domain-lock) |

### Domain-lock

When you enable the server, the current site host is recorded. If the site URL later changes (a clone, a staging copy, a migration), the server **disables itself automatically** and the next request returns `401`. This prevents a copied database from silently exposing an MCP endpoint on another domain. Re-enable it from the same admin page on the new domain.

## Authentication & authorization

Three layers run in order:

1. **WordPress REST auth** — the client sends an Application Password as HTTP Basic; WordPress resolves `current_user`.
2. **Transport gate** — the connection requires `manage_options` (multisite: super admin). Filterable via `lw_site_manager_mcp_capability`. Fail-closed.
3. **Per-ability `permission_callback`** — every ability still enforces its own capability when executed.

Because the transport gate is admin-level, use an **administrator** account's Application Password (Users → Profile → Application Passwords).

```php
// Relax (or tighten) the transport-level capability.
add_filter( 'lw_site_manager_mcp_capability', fn() => 'manage_options' );
```

## Connecting a client

Add the server to your MCP client. For Claude Code, in the project `.mcp.json`:

```json
{
  "mcpServers": {
    "lw-site-manager": {
      "type": "http",
      "url": "https://YOUR-SITE/wp-json/mcp/lw-site-manager",
      "headers": {
        "Authorization": "Basic BASE64(username:application_password)"
      }
    }
  }
}
```

The `Authorization` value is the literal word `Basic`, a space, then the base64 encoding of `username:application_password`.

## What the client sees

The server exposes the adapter's three meta-tools:

| Tool | Purpose |
|------|---------|
| `mcp-adapter/discover-abilities` | List the public abilities **plus** Site Manager usage instructions and the [skills](skills.md) catalog. (Overridden by this plugin to add the instructions field.) |
| `mcp-adapter/get-ability-info` | Get one ability's input/output schema. |
| `mcp-adapter/execute-ability` | Run an ability: `{ "ability_name": "site-manager/…", "parameters": { … } }`. |

Individual abilities are invoked **through** `execute-ability` (not as separate MCP tools). The agent typically calls `discover-abilities` first, then `execute-ability` by name.

## Which abilities are exposed

The adapter only exposes abilities whose meta has `mcp.public = true`. When the server is enabled, LW Site Manager flags **all of its own `site-manager/*` abilities** automatically, via the core `wp_register_ability_args` filter (see `src/Mcp/AbilityExposer.php`). Abilities from other plugins are left alone — they opt in by setting `mcp.public` on their own registration (see [extending-abilities.md](extending-abilities.md)). When the server is disabled, nothing is flagged.

## Honest error results

The adapter wraps every ability return as `{ success: true, data: <inner> }`. When the inner value is itself `{ success: false, error: … }`, that would mask a real failure behind an outer success. `src/Mcp/ResultUnwrapper.php` (hooked on `mcp_adapter_tool_call_result`) unwraps that shape so logical failures surface as proper MCP errors, preserving any structured detail.

## Components

```
src/Mcp/
├── Bootstrap.php        # boots the adapter when enabled + present; no-op otherwise
├── Server.php           # brands the default server (id/route/name) + endpoint() helper
├── Toggle.php           # enable state + domain-lock
├── TransportGuard.php   # transport capability gate (manage_options + filter)
├── AbilityExposer.php   # flags site-manager/* as mcp.public when enabled
├── DiscoverAbility.php  # overrides discover-abilities to add instructions + skill catalog
└── ResultUnwrapper.php  # surfaces inner {success:false} errors
```

## Requirements

* WordPress 6.9+ (Abilities API)
* PHP 8.2+
* `wordpress/mcp-adapter` (shipped in the release ZIP's `vendor/`; installed via Composer for source checkouts)

## See also

* [Skills](skills.md) — the bundled playbooks surfaced through this server
* [Extending with skills](extending-skills.md) — add your own skills from another plugin
* [Extending with abilities](extending-abilities.md) — add your own abilities
