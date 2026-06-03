---
name: site-health-triage
description: Diagnose a WordPress site's health using LW Site Manager abilities. Use when the user asks why the site is slow, broken, erroring, or "what's wrong with my site".
enable_prompt: false
enable_agentic: true
---

# Site health triage

You are diagnosing a WordPress site through LW Site Manager abilities. Work read-first; never run a destructive ability during triage.

## Steps

1. Call `site-manager/health-check` for the overall report (PHP/WP versions, disk, cron, https).
2. Call `site-manager/error-log` to read recent PHP errors. Quote the most recent fatal/parse errors verbatim.
3. Call `site-manager/check-updates` to list pending core/plugin/theme updates — outdated components are a common cause.
4. If updates were recently applied, call `site-manager/list-plugins` and look for unexpectedly deactivated plugins.

## Rules

- Report findings with the exact ability output; do not speculate beyond it.
- Recommend fixes, but do NOT call `site-manager/update-*`, `site-manager/cleanup-database`, or any backup/restore ability unless the user explicitly approves.
