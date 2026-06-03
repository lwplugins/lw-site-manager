# Skills

> Since v1.2.0.

## What is a skill?

A **skill** is a `SKILL.md` playbook — a slug, a one-line description, and a markdown body of instructions — that an AI agent loads *before* starting a task. Where an [ability](abilities/README.md) is a single callable operation, a skill is procedural knowledge: "when the user asks X, call these abilities in this order, with these safety rules."

Skills are surfaced to agents through the [built-in MCP server](mcp-server.md):

1. **Discovery** — the skill catalog (slug + description only) is injected into the `discover-abilities` result.
2. **Loading** — the agent calls the `site-manager/skill-get` ability with a slug to pull the full playbook into context.
3. **Prompt mode** *(optional)* — a skill can also be exposed as a native MCP prompt.

The agent flow:

```
discover-abilities  →  sees the catalog (slug + description)
  →  matches a skill to the user request
  →  skill-get { slug }  →  full SKILL.md enters context
  →  works through the playbook, calling abilities as instructed
```

## Bundled skills

The plugin ships a small starter library under `skills/`:

| Slug | Purpose |
|------|---------|
| `site-health-triage` | Diagnose a site (health-check, error-log, updates) read-first. |
| `woocommerce-order-ops` | Inspect and safely modify WooCommerce orders (lookups, status, refunds). |
| `safe-bulk-content` | Preview-before-mutate bulk post/page operations. |

## SKILL.md format

Each skill lives in its own directory: `skills/<slug>/SKILL.md`. The directory name is the slug (sanitized; ≤ 60 chars). Frontmatter is parsed leniently:

```markdown
---
name: WooCommerce Order Operations
description: One-line trigger description shown in the catalog. Use when the user asks to look up, refund, or change an order.
enable_prompt: false
enable_agentic: true
---

# Playbook body

The full markdown instructions returned by skill-get.
```

| Field | Default | Meaning |
|-------|---------|---------|
| `name` | the slug | Human-readable title. |
| `description` | — | One line shown in the discovery catalog. **Required** for discovery. |
| `enable_agentic` | `true` | Whether the skill appears in the discovery catalog. |
| `enable_prompt` | `false` | Whether the skill is also exposed as a native MCP prompt. |

A skill must have a non-empty `description` and body to be discoverable. Files larger than 1 MB are skipped.

## Loading: `site-manager/skill-get`

One ability serves every source. Input `{ "slug": "site-health-triage" }`; output:

```json
{
  "found": true,
  "slug": "site-health-triage",
  "name": "site-health-triage",
  "description": "…",
  "content": "<the full re-rendered SKILL.md>",
  "source": "built-in"
}
```

An unknown slug returns `{ "found": false }` (never an error). The lookup tolerates a leading `site-manager/` prefix and normalizes the slug the same way stored slugs are normalized, so minor variations still resolve. Gated by `manage_options`.

## Prompt mode

A skill with `enable_prompt: true` is additionally registered as `site-manager/skill-prompt-<slug>` with `mcp.type = prompt`, so the MCP adapter exposes it on `prompts/list` / `prompts/get`. The user can then invoke it as a native prompt from the client (e.g. Claude Code's prompt menu), independent of description-matching.

## Sources

Skills come from a priority-ordered, filter-based registry (`src/Skills/Sources.php`). Out of the box there is one source — the bundled library (`built-in`, priority 10). Other plugins add their own; see [extending-skills.md](extending-skills.md).

`Sources` query helpers:

| Method | Returns |
|--------|---------|
| `find( $slug )` | First match across sources, priority-ordered (used by `skill-get`). |
| `all()` | Every skill, annotated with its `source` id and `source_label` badge. |
| `discoverable( 'agentic' \| 'prompt' )` | Skills eligible for the catalog / prompt mode. |

## Admin

**LW Plugins → AI / MCP** lists the bundled (and any externally-registered) skills read-only, alongside the MCP toggle and the `.mcp.json` connection snippet.

## Components

```
skills/<slug>/SKILL.md       # the bundled, static library
src/Skills/
├── Bootstrap.php            # wires sources, the discover-instructions filter, and abilities
├── Parser.php               # lenient frontmatter parse + render
├── Sources.php              # filter-based source registry (find / all / discoverable)
├── BuiltInSource.php        # the bundled library (delegates to DirectorySkillSource)
├── DirectorySkillSource.php # reusable directory loader + external registration helper
├── Catalog.php              # renders + injects the "## Available Skills" block
├── SkillGetAbility.php      # the site-manager/skill-get ability
└── PromptAbilities.php      # native MCP prompt registration for enable_prompt skills
```

## See also

* [Built-in MCP server](mcp-server.md)
* [Extending with skills](extending-skills.md) — ship skills from your own plugin
