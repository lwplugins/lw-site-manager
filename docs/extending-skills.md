# Extending LW Site Manager with Custom Skills

> Since v1.2.0.

## Overview

Just as any plugin can [register its own abilities](extending-abilities.md), any plugin can contribute its own [skills](skills.md) — SKILL.md playbooks that show up in the discovery catalog, load via `site-manager/skill-get`, and (optionally) appear as native MCP prompts. No core modification, no hard dependency.

There are two ways in:

| Approach | Use when |
|----------|----------|
| `DirectorySkillSource::register()` | You ship a folder of `SKILL.md` files (the common case). |
| `lw_site_manager_skill_sources` filter | You generate skills dynamically, or store them somewhere other than a directory. |

## The easy way: a bundled directory

Ship your skills as `<dir>/<slug>/SKILL.md` and register the directory once:

```php
add_action( 'init', function (): void {
    if ( class_exists( '\LightweightPlugins\SiteManager\Skills\DirectorySkillSource' ) ) {
        \LightweightPlugins\SiteManager\Skills\DirectorySkillSource::register(
            'my-plugin',           // unique source id
            'My Plugin',           // badge shown in the catalog
            __DIR__ . '/skills',   // directory holding <slug>/SKILL.md
            20                     // optional priority (built-in is 10)
        );
    }
} );
```

That's it. Your skills now:

* appear in the `discover-abilities` catalog under the **My Plugin** badge,
* are loadable via the existing `site-manager/skill-get` ability (no per-skill ability needed),
* show up as native MCP prompts when a skill sets `enable_prompt: true`,
* and (for `skill-get` / prompt abilities) are auto-flagged `mcp.public` while the MCP server is enabled.

Use the same SKILL.md format as the bundled skills — see [skills.md](skills.md#skillmd-format).

```
your-plugin/
└── skills/
    ├── do-the-thing/SKILL.md
    └── another-task/SKILL.md
```

## The flexible way: the filter

For dynamic skills (generated from the database, an API, user input, etc.), hook the registry filter directly and add a source entry whose `loader` returns skill records:

```php
add_filter( 'lw_site_manager_skill_sources', function ( array $sources ): array {
    $sources['my-plugin'] = [
        'id'       => 'my-plugin',
        'priority' => 20,
        'label'    => 'My Plugin',
        'loader'   => function (): array {
            return [
                [
                    'slug'           => 'do-the-thing',
                    'name'           => 'Do The Thing',
                    'description'    => 'When the user asks to do the thing, follow this.',
                    'content'        => "# Do the thing\n\n1. First…\n2. Then…",
                    'enable_prompt'  => false,
                    'enable_agentic' => true,
                ],
            ];
        },
    ];
    return $sources;
} );
```

### Source entry

| Key | Type | Notes |
|-----|------|-------|
| `id` | string | Unique; used as the registry key. |
| `priority` | int | Lower renders earlier in the catalog. Built-in is 10. |
| `label` | string | Badge shown next to each of your skills. |
| `loader` | callable | Returns a list of skill records (below). Called lazily, possibly several times per request — memoize if expensive. |

### Skill record

| Key | Type | Default | Notes |
|-----|------|---------|-------|
| `slug` | string | — | Unique identifier the agent passes to `skill-get`. |
| `name` | string | slug | Human-readable title. |
| `description` | string | — | One-line catalog entry. Required for discovery. |
| `content` | string | — | The full SKILL.md / playbook body. Required. |
| `enable_agentic` | bool | `true` | Appears in the discovery catalog. |
| `enable_prompt` | bool | `false` | Also exposed as a native MCP prompt. |

## No hard dependency

Both entry points are plain WordPress hooks (or a `class_exists`-guarded helper call). Your plugin works whether or not LW Site Manager is installed:

* The filter simply never fires when Site Manager is inactive.
* The `DirectorySkillSource::register()` call is guarded by `class_exists()`.

No autoload dependency, no required composer package.

## Naming

Prefix skill slugs with something specific to your plugin to avoid collisions across sources, e.g. `lw-seo-audit`, `acme-import-products`. A user-created skill can never shadow a slug already provided by another source.

## See also

* [Skills](skills.md) — the skills system and SKILL.md format
* [Built-in MCP server](mcp-server.md) — how skills reach the agent
* [Extending with abilities](extending-abilities.md) — the parallel ability hooks
