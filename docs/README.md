# LW Site Manager — Documentation

LW Site Manager exposes structured site-management operations to AI agents and automation through the WordPress Abilities API, a built-in MCP server, and a bundled skills library.

## Guides

| Doc | What it covers |
|-----|----------------|
| [Built-in MCP server](mcp-server.md) | Enable, connect, authenticate, and secure the `/wp-json/mcp/lw-site-manager` endpoint (since v1.2.0). |
| [Skills](skills.md) | SKILL.md playbooks: the bundled library, format, discovery, `skill-get`, prompt mode (since v1.2.0). |
| [Abilities API](abilities/README.md) | What abilities are and how the Abilities API works. |

## Extending (for plugin developers)

| Doc | What it covers |
|-----|----------------|
| [Extending with abilities](extending-abilities.md) | Register your own abilities via `lw_site_manager_register_abilities`. |
| [Extending with skills](extending-skills.md) | Ship your own skills via `DirectorySkillSource::register()` or the `lw_site_manager_skill_sources` filter. |

## Ability reference

Per-category ability documentation lives under [`abilities/`](abilities/): [maintenance](abilities/maintenance.md), [plugin management](abilities/plugin-management.md), [theme management](abilities/theme-management.md), [posts](abilities/posts.md), [pages](abilities/pages.md), [media](abilities/media.md), [comments](abilities/comments.md), [tags](abilities/tags.md), [user management](abilities/user-management.md), [meta](abilities/meta.md), [settings](abilities/settings.md), [categories](abilities/categories.md), [WooCommerce](abilities/woocommerce.md).

> Note: `docs/` is excluded from the release ZIP (`.distignore`) — it is developer/GitHub documentation, not shipped to sites.
