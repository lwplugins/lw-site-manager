# Design: Built-in MCP server + bundled Skills for LW Site Manager

- **Date:** 2026-06-03
- **Target plugin:** `lw-site-manager`
- **Target version:** 1.1.28 → **1.2.0** (minor, additive)
- **Status:** approved design, pending implementation plan
- **Source of inspiration:** a proven MCP + Skills architecture pattern for WordPress (ideas/architecture only — no third-party code reused)

## Goal

Make `lw-site-manager` self-contained for AI/MCP clients by adding two additive subsystems, following a proven MCP + Skills pattern:

1. A **built-in MCP server** that serves the plugin's WordPress abilities over the Model Context Protocol at `/wp-json/mcp/lw-site-manager`, using the official `wordpress/mcp-adapter` library.
2. A **bundled, static Skills library** (`skills/<slug>/SKILL.md`) surfaced to agents through the MCP discover step and a `site-manager/skill-get` ability.

Both are **fully backward-compatible**: every existing ability and the WordPress Abilities REST surface (`/wp-json/wp-abilities/v1/…`) remain untouched.

## Non-goals (v1)

- No user-authored skills (no CPT, no admin CRUD). The source registry is built to accept a user-CPT source later without breaking changes.
- No agent-facing skill mutation abilities (`skill-write/edit/delete`).
- No legacy MCP alias server (the plugin never had an MCP route, so there is nothing to preserve).
- No change to the external `mcp.lwplugins.com` proxy or the `/wp-json/wp-abilities/v1/` surface.

## Backward-compatibility guarantees

- All existing `site-manager/*` abilities and their permission callbacks are unchanged.
- `/wp-json/wp-abilities/v1/…` keeps working exactly as today → the `mcp.lwplugins.com` proxy and the `wp-demo` MCP server are unaffected.
- The new `/wp-json/mcp/lw-site-manager` route and the new `site-manager/skill-get` ability are purely additive.
- **Graceful degradation:** if the `WP\MCP\Core\McpAdapter` class is absent (e.g. someone installs a source ZIP without `vendor/`), the MCP subsystem is a no-op and the plugin behaves exactly as it does today. This mirrors the existing autoloader-missing notice pattern in `lw-site-manager.php:33-46`.
- Plugin requirements unchanged: PHP ≥ 8.2, WP ≥ 6.9. (`wordpress/mcp-adapter` requires PHP ≥ 7.4, so no conflict.)

## Dependency

Verified on Packagist (2026-06-03):

- `wordpress/mcp-adapter` — **v0.5.0**, WordPress AI Team, **GPL-2.0-or-later**, PSR-4 `WP\MCP\` → `includes/`, requires `php ^7.4 || ^8.0`, `wordpress/php-mcp-schema ^0.1.0`, `ext-json`.
- `wordpress/php-mcp-schema` — v0.1.1 (transitive), GPL-2.0-or-later.

License is GPL-2.0-or-later → matches the plugin and is WordPress.org-safe.

Action: `composer require wordpress/mcp-adapter:^0.5` and pin to the exact version (pre-1.0 ⇒ API may shift). `vendor/` already ships in the release ZIP (`.distignore` keeps it), so no packaging change is needed beyond verifying the new vendor files are included.

## Architecture

### Decisions locked in

| Topic | Decision |
|---|---|
| Scope | Both MCP server **and** Skills. |
| MCP endpoint | Canonical only: `/wp-json/mcp/lw-site-manager`. No legacy alias. |
| MCP auth | Two layers: WP REST-auth (application password / Basic) → **transport gate `manage_options`** (multisite: super admin), relaxable via `lw_site_manager_mcp_capability` filter → **then each ability's existing `permission_callback` still runs**. |
| MCP ability scope | Own `site-manager/*` auto-exposed; other plugins opt in via `meta.mcp.public = true` or the `lw_site_manager_mcp_abilities` filter. |
| MCP safety | Option `lw_site_manager_mcp_enabled`, **default OFF**; production-suspect warning on enable; domain stored on enable and **auto-disable when the site domain changes**. |
| Skills v1 | **Bundled static library only** (`skills/<slug>/SKILL.md`, read-only). Registry ready for a future user-CPT source. |
| Skill discovery | Own `site-manager/discover-abilities` ability passing instructions through `lw_site_manager_discover_instructions` filter; `Catalog` prepends the skill list. Independent of adapter internals. |

### Component layout (PSR-4, namespace `LightweightPlugins\SiteManager\`, root `src/`)

```
src/Mcp/
  Bootstrap.php        // wire hooks; hard no-op if WP\MCP\Core\McpAdapter is missing
  Server.php           // register the MCP server on mcp_adapter_init + branding (server_id/route/name = lw-site-manager)
  Discovery.php        // collect ability names to expose: own site-manager/* + opt-in (meta.mcp.public / filter)
  TransportGuard.php   // transport_permission_callback: manage_options|super-admin + lw_site_manager_mcp_capability filter
  ResultUnwrapper.php  // mcp_adapter_tool_call_result filter: unwrap inner {success:false,...} so logical errors surface
  Toggle.php           // lw_site_manager_mcp_enabled option + domain-lock + production-suspect detection
  DiscoverAbility.php  // site-manager/discover-abilities ability; runs instructions through the filter

src/Skills/
  Bootstrap.php        // wire hooks
  Parser.php           // frontmatter parse() + normalize_slug() + render_skill_md() (original implementation)
  Sources.php          // filter-based registry: registry()/find()/all()/discoverable(); lw_site_manager_skill_sources filter
  BuiltInSource.php    // load bundled skills/<slug>/SKILL.md via glob('*/SKILL.md'), memoized per request
  Catalog.php          // render + inject the "## Available Skills" markdown block into discover instructions
  SkillGetAbility.php  // site-manager/skill-get ability (readonly, idempotent); slug → full rendered SKILL.md
  PromptAbilities.php  // optional: register skill-prompt-{slug} abilities (meta.mcp.type=prompt) for enable_prompt skills

src/Admin/             // FIX the dead includes/Admin/* classes by relocating them here (PSR-4) and wiring them
  ParentPage.php       // moved from includes/Admin/; ParentPage::maybe_register() wired to admin_menu
  NoticeManager.php    // moved from includes/Admin/
  McpSettingsPage.php  // "AI / MCP" submenu: enable toggle, generated .mcp.json snippet, read-only bundled-skill list

skills/                // STATIC bundled library, shipped in the ZIP
  site-health-triage/SKILL.md
  woocommerce-order-ops/SKILL.md
  safe-bulk-content/SKILL.md
```

The two new subsystems are each bootstrapped from the main plugin class (`lw-site-manager.php`) alongside the existing hooks, guarded so a missing adapter never fatals.

### MCP request flow

```
Claude Code .mcp.json (type:http, Basic app-password)
  → POST /wp-json/mcp/lw-site-manager
  → WP REST-auth sets current_user
  → TransportGuard: current_user_can('manage_options') (filterable)   [layer 1]
  → tool call (discover-abilities / get-ability-info / execute-ability)
  → ability.permission_callback (existing per-ability cap)            [layer 2]
  → ResultUnwrapper normalizes {success:false} inner errors
```

### Skills flow

```
discover-abilities  → Catalog prepends "## Available Skills" (slug + description, admin-gated)
  → agent matches a description to the user request
  → site-manager/skill-get { slug }  → Sources::find(slug) → full rendered SKILL.md into context
  → (optional) enable_prompt skills also appear as native MCP prompts via prompts/list + prompts/get
```

### Skill file format (`skills/<slug>/SKILL.md`)

Frontmatter parsed leniently:

```markdown
---
name: woocommerce-order-ops
description: One-line trigger description shown in the catalog. Use when ...
enable_prompt: false      # default false — prompt mode is opt-in
enable_agentic: true      # default true — catalog discovery is primary
---

# Playbook body (full SKILL.md markdown returned by skill-get)
```

- Slug = directory name (`sanitize_title`, ≤60 chars). `name:` is informational.
- A skill is catalog-discoverable only if `description` and body are non-empty and `enable_agentic` is true.
- Bundled v1 starter skills are management-focused: `site-health-triage`, `woocommerce-order-ops`, `safe-bulk-content`. The set is extensible by dropping new directories.

## Admin integration (also fixes a review finding)

The currently-dead `includes/Admin/ParentPage.php` + `NoticeManager.php` (namespace `…\SiteManager\Admin`, never autoloaded because composer maps only `src/`, never `require`d, no `admin_menu` hook) are **relocated to `src/Admin/`** so PSR-4 loads them, and `ParentPage::maybe_register()` is wired into `admin_menu`. Result: lw-site-manager finally appears in the shared "LW Plugins" menu, and gains an "AI / MCP" submenu containing:

- the MCP enable/disable toggle (with the production-suspect confirmation),
- the generated `.mcp.json` client snippet (endpoint + Basic auth header guidance),
- a read-only list of the bundled skills.

## Error handling

- Missing adapter class → MCP Bootstrap returns early; admin notice (reusing the existing notice style) explains how to restore `vendor/`.
- MCP disabled (default) → server is not registered; no `/wp-json/mcp/lw-site-manager` route exists.
- Domain mismatch → Toggle auto-disables and records why; admin notice prompts re-enable.
- `skill-get` unknown slug → `{ found: false }` (never a fatal); empty/missing skills dir → empty catalog, no error.
- Transport gate failure → fail-closed (adapter default), request rejected.

## Testing

- **Unit (PHPUnit, existing harness):** `Parser` (frontmatter edge cases, slug normalization, render round-trip), `BuiltInSource` (glob + memoization + malformed file skip), `Sources` (priority order, `find`, `discoverable` flag defaults), `Discovery` (own + opt-in selection), `Toggle` (enabled state, domain-lock transitions).
- **Integration:** `skill-get` ability returns rendered SKILL.md for a bundled slug and `{found:false}` otherwise; discover instructions contain the catalog only when skills exist and caller is admin.
- **Manual / smoke:** connect Claude Code via generated `.mcp.json` to the demo site, confirm `tools/list` shows `site-manager-*` tools and `skill-get`, confirm a skill loads, confirm a non-admin app-password is rejected at the transport gate.
- All new files pass `composer phpcs`. New code respects the project limits: `declare(strict_types=1)`, PSR-4, ≤200 lines/class, ≤30 lines/method, type declarations.

## Versioning (per .claude/rules/versioning.md)

Bump 1.1.28 → **1.2.0** in all five locations: plugin header `Version:`, `LW_SITE_MANAGER_VERSION`, `readme.txt` Stable tag, `readme.txt` changelog, `CHANGELOG.md`. Add the composer dependency. Run `composer phpcs` before any commit. Do not push without explicit consent.

## Open implementation checks (resolved during planning, not blocking design)

- Confirm the exact `wordpress/mcp-adapter` v0.5.0 API for: server registration hook/signature, transport permission filter name, the tool-result filter name, and whether prompts are first-class — adjust `Server.php`/`ResultUnwrapper.php`/`PromptAbilities.php` accordingly. If the adapter exposes a native instructions filter we prefer our own `discover-abilities` ability anyway (decision above) for stability.
