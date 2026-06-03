---
name: safe-bulk-content
description: Perform bulk post/page content operations safely via LW Site Manager. Use when the user asks to bulk edit, trash, publish, or reorganize many posts or pages.
enable_prompt: false
enable_agentic: true
---

# Safe bulk content operations

Bulk content changes are easy to get wrong. Always preview before mutating.

## Steps

1. `site-manager/list-posts` (or `site-manager/list-pages`) with the user's filter to get the exact set of ids.
2. Show the user the list (titles + ids) and get explicit confirmation of the target set.
3. Only then call `site-manager/bulk-posts` with the confirmed ids and action.
4. For deletes, prefer trash over permanent delete; mention `site-manager/restore-post` exists.

## Rules

- Never run `site-manager/bulk-posts` with a `publish` or `delete` action without showing the affected ids first.
- If the set is larger than ~50 items, confirm again — bulk mistakes scale.
