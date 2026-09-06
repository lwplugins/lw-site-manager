=== LW Site Manager ===
Contributors: lwplugins
Tags: site-manager, maintenance, ai, rest-api, abilities
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.4.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress Site Manager using the Abilities API - Full site maintenance via AI/REST.

== Description ==

LW Site Manager is a comprehensive WordPress site management plugin built on the WordPress Abilities API. It provides a native, AI-ready alternative to MainWP for managing your WordPress site.

= Features =

* **Updates Management** - Check and apply updates for core, plugins, and themes
* **Plugin Management** - Install, activate, deactivate, and delete plugins
* **Theme Management** - Install, activate, and delete themes
* **Content Management** - Full CRUD for posts, pages, and custom post types
* **Taxonomy Management** - Manage categories, tags, and custom taxonomies
* **User Management** - Create, update, and manage users
* **Media Management** - Upload and manage media files
* **Comments Management** - Moderate and manage comments
* **Backup & Restore** - Create and restore site backups
* **Health & Diagnostics** - Monitor site health and PHP errors
* **Database Maintenance** - Optimize, cleanup, and repair database
* **Cache Management** - Flush object cache, page cache, and OPcache
* **Settings Management** - Read and update WordPress options
* **WooCommerce Integration** - Comprehensive store management when WooCommerce is active (see below)

= WooCommerce Abilities (when WooCommerce is active) =

* **Products** - List, get, create, update, delete, duplicate; stock updates; variations; product categories; bulk actions
* **Orders (CRUD)** - List, get, create, update, delete; status changes; order notes; refunds; bulk actions
* **Order line items** - Add, update quantity/price, or remove products on existing orders
* **Order coupons & fees** - Apply / remove coupons; add / remove custom fees
* **Order shipping** - Set or replace shipping line; remove shipping; force totals recalculation
* **Order payment workflow** - Mark orders paid (with optional silent flag), re-send order emails (6 templates), generate pay-for-order URL
* **Reports** - Sales, top sellers, order totals, revenue stats with period comparison, low stock products

All order modification abilities reject `cancelled` / `refunded` / `failed` orders with HTTP 409, and support `recalculate=false` for chained operations.

= AI Integration =

This plugin is designed for AI agent integration via:

* **Built-in MCP server** (since 1.2.0) - connect Claude, ChatGPT, or any MCP client directly to your site
* **REST API** - any AI can call abilities via the WordPress Abilities REST endpoints
* **Skills** (since 1.2.0) - bundled SKILL.md playbooks that guide the agent through common tasks
* **Agentic Loops** - AI decides which abilities to call

= MCP Server (since 1.2.0) =

LW Site Manager ships its own Model Context Protocol (MCP) server, built on the official WordPress MCP Adapter. It exposes the plugin's abilities as MCP tools and surfaces the bundled Skills catalog, so an MCP client can discover and run them directly.

* **Endpoint:** `https://YOUR-SITE/wp-json/mcp/lw-site-manager`
* **Enabled by default (since 1.3.0).** Toggle it in the admin: **LW Plugins → AI / MCP → "the built-in MCP server" → Save**. It still requires an administrator application password to connect.
* **Admin-only.** Access requires the `manage_options` capability at the connection level, and every ability still enforces its own capability. Use an **administrator** account's application password.
* **Domain-locked.** The server records the site URL when enabled and automatically disables itself if the domain changes (e.g. a staging clone or migration). Re-enable it from the same page after an intentional move.
* **Authentication:** WordPress Application Passwords over HTTP Basic (the same scheme as the REST API).

Setup steps:

1. Create an Application Password for an administrator: **Users → Profile → Application Passwords**.
2. Enable the server: **LW Plugins → AI / MCP**, tick the checkbox, **Save**. The page then shows a ready-to-paste `.mcp.json` snippet pre-filled with your endpoint.
3. Add the server to your MCP client. For Claude Code, put this in your project's `.mcp.json` (replace the host and the Basic credentials):

`{`
`  "mcpServers": {`
`    "lw-site-manager": {`
`      "type": "http",`
`      "url": "https://YOUR-SITE/wp-json/mcp/lw-site-manager",`
`      "headers": { "Authorization": "Basic BASE64(username:application_password)" }`
`    }`
`  }`
`}`

The `Authorization` value is the literal word `Basic`, a space, then the base64 encoding of `username:application_password`.

Once connected, the client's `discover-abilities` tool returns the available abilities plus the Skills catalog; `execute-ability` runs any ability by name; and `skill-get` loads a full playbook. The existing `/wp-json/wp-abilities/v1/` REST surface is unchanged, so previous integrations keep working.

= Part of LW Plugins =

LW Site Manager is part of the [LW Plugins](https://lwplugins.com) family - lightweight plugins for WordPress with no bloat, no upsells, and no tracking.

== Installation ==

= Via Composer =

`composer require lwplugins/lw-site-manager`

= Manual Installation =

1. Download the plugin from GitHub
2. Upload to the `/wp-content/plugins/lw-site-manager` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= Requirements =

* PHP 8.2 or higher
* WordPress 6.9 or higher (requires Abilities API)
* WooCommerce 7.0 or higher (only if you use the WooCommerce abilities)

== Frequently Asked Questions ==

= What is the WordPress Abilities API? =

The WordPress Abilities API is a new feature in WordPress 6.9 that allows plugins to register standardized capabilities that can be executed via REST API, PHP, or JavaScript.

= How do I authenticate API requests? =

Use WordPress Application Passwords. Go to Users → Your Profile → Application Passwords to create one. Then use Basic Auth with your username and app password.

= How do I connect an MCP client (Claude, ChatGPT, etc.)? =

Since 1.2.0 the plugin includes a built-in MCP server, on by default since 1.3.0 (admin-only; toggle it under **LW Plugins → AI / MCP**). Create an administrator Application Password, then point your MCP client at `https://YOUR-SITE/wp-json/mcp/lw-site-manager` using HTTP Basic auth. The settings page generates a ready-to-paste `.mcp.json` snippet. See "MCP Server (since 1.2.0)" in the Description for full steps.

= Why does my MCP connection suddenly return 401 after a migration? =

The MCP server is domain-locked: it disables itself automatically when the site URL changes, as a safety measure for cloned/staging copies. Re-enable it from **LW Plugins → AI / MCP** on the new domain.

= Can another plugin add its own abilities or skills? (developers) =

Yes — LW Site Manager is built to be extended, and other plugins can plug into both layers.

**Abilities:** hook the `lw_site_manager_register_abilities` action (you receive the shared `PermissionManager`) to register your own abilities, and `lw_site_manager_register_categories` for ability categories. They are exposed over the same REST and MCP surfaces (set `meta.mcp.public = true` on your ability to expose it via the built-in MCP server).

**Skills (since 1.2.0):** the easiest way is to ship a `skills/<slug>/SKILL.md` directory in your plugin and register it in one call:

`add_action( 'init', function () {`
`    if ( class_exists( '\\LightweightPlugins\\SiteManager\\Skills\\DirectorySkillSource' ) ) {`
`        \\LightweightPlugins\\SiteManager\\Skills\\DirectorySkillSource::register(`
`            'my-plugin', 'My Plugin', __DIR__ . '/skills'`
`        );`
`    }`
`} );`

Your skills then appear in the discovery catalog (under your own badge), are loadable via `site-manager/skill-get`, and — when a skill's frontmatter sets `enable_prompt: true` — as native MCP prompts, exactly like the built-in skills. For full control (dynamic skills, a non-directory source), hook the `lw_site_manager_skill_sources` filter directly and add an entry of the shape `[ 'id' => …, 'priority' => …, 'label' => …, 'loader' => callable ]`, where the loader returns skill records (`slug`, `name`, `description`, `content`, `enable_prompt`, `enable_agentic`).

= Does this work with WooCommerce? =

Yes — and the WooCommerce coverage is comprehensive. Beyond product and order CRUD, you can edit existing orders end-to-end: add or remove line items, apply or remove coupons, add custom fees, change shipping, recalculate totals, mark orders paid, re-send order emails, and generate pay-for-order URLs. Requires WooCommerce 7.0 or higher.

= Is this a MainWP alternative? =

Yes, LW Site Manager provides similar functionality to MainWP but uses the native WordPress Abilities API instead of custom endpoints.

== Screenshots ==

1. Site health check results
2. Update management interface
3. Backup creation options

== Changelog ==

= 1.4.3 =
* Fix: the release package and Composer dist no longer ship tests, docs or development configuration

= 1.4.2 =
* Fix: Failed plugin/theme installs and activations were reported as successful. They returned a 200 with success:false in the body, which the MCP layer then wrapped into a success envelope — so an AI agent was told the work was done when it had not been. These now return a proper error, over MCP and over REST alike, with the captured PHP errors kept in the error detail.
* Fix: The WooCommerce report totals (orders, customers, products, coupons) returned an empty report instead of an error when WooCommerce was inactive, making "no store" indistinguishable from "no data".
* New: An admin notice when another plugin has loaded an older copy of the MCP Adapter library. WooCommerce bundles v0.3.0 and loads it before ours, and that copy is missing the hook this plugin uses to surface failures — previously this degraded silently.
= 1.4.1 =
* New: WooCommerce product reviews now report their star rating and verified-purchase flag. Previously a review came back as plain text, so an agent asked to moderate reviews could not see what it was moderating.
* Change: list-comments now defaults to regular comments only. Whether product reviews appeared was previously up to chance — WooCommerce hides them, but stops doing so when another plugin (LearnDash, for one) touches the comment query, so the same call returned different results on different sites. Pass type=review for reviews, or type=all for everything.
* Change: comment-counts counts comments and product reviews separately instead of summing them. Reviews now appear under a "reviews" key with the same breakdown.
* Fix: The documented output of the comment abilities matches what they actually return. The schema advertised author_name and avatar_url, but the fields are author and avatar, and agent, replies_count, rating and verified were missing entirely. The comment-counts schema listed fields the ability never returned.
* Fix: The "Settings" button on the LW Plugins overview linked to a non-existent page (admin.php?page=lw-site-manager → 403); it now opens the AI / MCP screen.
* Update: Tested up to WordPress 7.1.

= 1.4.0 =
* Security: Abilities now check permissions against the specific object they act on, not just a general capability. Previously a low-privileged user with an application password could act far beyond their role: a Contributor could permanently delete any post site-wide, read every author's drafts and private posts, and (on WooCommerce stores) list all customer names, emails and phone numbers, read protected order meta and delete orders; an Author could permanently delete any media file; and a user manager or WooCommerce shop manager could reset the administrator's password or promote themselves to administrator. All confirmed fixed and covered by tests.
* Security: The WooCommerce order abilities are gated on the order capability (edit_shop_orders / manage_woocommerce) instead of the generic post capability. Product meta abilities likewise use a product capability.
* Security: Role, level and session-token user meta keys can no longer be written or read through the meta abilities — those keys are the role assignment itself. Change roles with the role parameter of update-user, which now requires the promote_user capability and only accepts roles the caller may actually grant.
* Security: Protected (underscore-prefixed) meta keys are hidden from non-administrators on single-key reads, which previously bypassed the filter entirely, and are no longer included in the get-post response for published posts.
* Fix: bulk-posts and bulk-comments returned a server error whenever any item failed, because the failed list did not match the declared output format.
* Change: delete-media no longer defaults to permanent deletion — items go to the trash unless force is passed. Pass force: true for the old behaviour.
* Change: list-posts no longer defaults to every post status. Callers that relied on receiving other authors' drafts must hold the corresponding capability.
* Fix: delete-theme now rejects a slug that is not an installed theme, so a malformed or hallucinated slug can no longer delete an unrelated directory.

= 1.3.3 =
* Change: The MCP Adapter (wordpress/mcp-adapter) is no longer a hard Composer dependency. It stays bundled in the WordPress.org ZIP, so the built-in MCP server keeps working out of the box; but `composer require lwplugins/lw-site-manager` no longer pulls it in. Since mcp-adapter v0.5 is type:wordpress-plugin, Composer/Bedrock sites were getting a stray "MCP Adapter" plugin installed under web/app/plugins. Composer installs that want the MCP server should add wordpress/mcp-adapter (or the canonical MCP Adapter plugin) themselves — the server no-ops gracefully when it is absent, and the REST / Abilities API is unaffected.

= 1.3.2 =
* Fix: wc-list-orders crashed with a fatal TypeError (HTTP 500 / generic MCP error) whenever a refund fell in the queried range. With HPOS, wc_get_orders() also returns refund objects, which format_order() could not accept. The query is now restricted to the shop_order type, so refunds are excluded and the total / page counts stay correct (#21)

= 1.3.1 =
* Fix: upload-media failed with "cURL error 60: SSL certificate problem" whenever the source URL was served with a self-signed or otherwise untrusted certificate — including when the site was downloading a file from itself. Certificate verification is now skipped for URLs on this site, where there is no man-in-the-middle position to defend against (#18)
* New: A URL on this site that points inside the uploads directory is read straight from disk, with no HTTP request at all. This removes the loopback round-trip and makes uploads immune to TLS, firewall and rate-limit interference. Path traversal is rejected: the resolved path must stay inside the uploads directory
* New: Optional `verify_ssl` input on upload-media for explicit control. Defaults to true for external hosts and false for URLs on this site; set it to false for other hosts only in trusted environments such as staging

= 1.3.0 =
* Change: The built-in MCP server is now enabled by default (previously disabled). It still requires an administrator Application Password to connect and stays domain-locked. Disable it any time under **LW Plugins → AI / MCP**.
* Update: Default-on sites record their domain on first request, so a cloned/migrated copy on a different host still auto-disables the server.
* Fix: The create-page and update-page abilities now set the page template from the `template` input (previously ignored, which forced a separate set-page-template call) (#17)
* Update: Added PHPStan level 5 static analysis to CI; unit tests now run on PHP 8.2–8.5

= 1.2.1 =
* Fix: WooCommerce abilities (top-sellers, low-stock, product-categories, order line-items, product/variation listings) failed for items without a featured image — wp_get_attachment_url() returned false, breaking the string|null image output schema. Missing images now normalize to null.

= 1.2.0 =
* New: Built-in MCP server at /wp-json/mcp/lw-site-manager (disabled by default, admin-only, domain-locked).
* New: Bundled Skills library (site-health-triage, woocommerce-order-ops, safe-bulk-content) surfaced to AI agents via a skill-get ability and the MCP discovery catalog.
* New: AI / MCP settings page with connection snippet and skill list.
* Fix: Admin ParentPage/NoticeManager are now loaded (moved to src/Admin) — Site Manager appears in the LW Plugins menu.

= 1.1.28 =
* Fix: `site-manager/get-user` and `site-manager/list-users` output schema now matches the actual response — removed the never-returned `role` (singular) and the mislabeled `avatar_url` (the field is `avatar`), and added the missing detailed fields `bio`, `posts_count`, `last_login`, and `capabilities`.
* Fix: `posts_count` in the detailed user response is now returned as an integer instead of a numeric string, matching the declared schema.

= 1.1.27 =
* New: Detailed post responses include a `meta` map of custom fields. WordPress internals (`_edit_lock`, `_edit_last`, `_thumbnail_id`) are filtered out; single-value keys are unwrapped and serialized values are auto-unserialized. Enables MCP / AI / REST clients to read CPT-meta-heavy content (form submissions, ACF, etc.) via `site-manager/get-post`. Issue #16.
* New: `lw_site_manager/post_data` filter — shape the full post payload before it leaves the server (add fields, redact meta, per-CPT customization). 3 args: `$data`, `$post`, `$detailed`.
* New: `lw_site_manager/post_meta` filter — narrow the meta map (allowlist, redact secrets) for a given post. 2 args: `$meta`, `$post_id`.

= 1.1.26 =
* New: Order line item management — `wc-add-order-item`, `wc-update-order-item`, `wc-remove-order-item` for editing existing orders
* New: Order coupons & fees — `wc-apply-order-coupon`, `wc-remove-order-coupon`, `wc-add-order-fee`, `wc-remove-order-fee`
* New: Order shipping & recalculation — `wc-set-order-shipping`, `wc-remove-order-shipping`, `wc-recalculate-order`
* New: Order payment workflow — `wc-mark-order-paid` (with `silent` flag), `wc-send-order-email` (6-template whitelist), `wc-get-payment-url`
* New: Global attribute management — `wc-list-attributes`, `wc-get-attribute`, `wc-create-attribute`, `wc-update-attribute`, `wc-delete-attribute`
* New: Attribute terms — `wc-list-attribute-terms`, `wc-create-attribute-term`, `wc-update-attribute-term`, `wc-delete-attribute-term`
* New: Product-attribute bindings — `wc-set-product-attributes`, `wc-add-product-attribute`, `wc-remove-product-attribute` (supports both global pa_* and custom)
* New: Product variations — `wc-generate-variations` (cartesian auto-fill), `wc-create-variation`, `wc-update-variation`, `wc-delete-variation`
* New: WooCommerce meta abilities (HPOS-aware) — `wc-get/set/delete-product-meta`, `wc-get/set/delete-order-meta`
* New: Comment meta abilities — `get-comment-meta`, `set-comment-meta`, `delete-comment-meta`
* New: Inline `meta` field on every create/update across users, terms, comments, WC products, orders, variations, and order line items
* New: `PermissionManager::can_manage_orders()` (`edit_shop_orders` capability) for the new order-modification abilities
* New: Order modification guard — rejects `cancelled` / `refunded` / `failed` orders with HTTP 409
* Update: All order mutations support `recalculate=false` for chained operations followed by a single `wc-recalculate-order`
* Update: `wc-mark-order-paid` accepts a `silent` flag to suppress customer notification emails
* Update: Split monolithic `WooCommerceAbilities.php` (1523 lines) into focused classes under `WooCommerce/`
* Update: `ProductManager::create_product/update_product` switched to `WC_Product::update_meta_data()` instead of raw `update_post_meta()` (HPOS-future-proof)
* Update: README.md now lists all 167 abilities grouped by category

= 1.1.25 =
* New: Order line item management — `wc-add-order-item`, `wc-update-order-item`, `wc-remove-order-item` for editing existing orders
* New: Order coupons & fees — `wc-apply-order-coupon`, `wc-remove-order-coupon`, `wc-add-order-fee`, `wc-remove-order-fee`
* New: Order shipping & recalculation — `wc-set-order-shipping`, `wc-remove-order-shipping`, `wc-recalculate-order`
* New: Order payment workflow — `wc-mark-order-paid` (with `silent` flag), `wc-send-order-email` (6-type whitelist), `wc-get-payment-url`
* New: `PermissionManager::can_manage_orders()` capability check (`edit_shop_orders`) for the new abilities
* New: Order modification guard rejects `cancelled` / `refunded` / `failed` orders with HTTP 409
* Update: All mutations support `recalculate=false` for chained operations
* Update: Split monolithic `WooCommerceAbilities.php` (1523 lines) into focused classes under `WooCommerce/`

= 1.1.24 =
* Update: Cleaned up redundant `'default' => []` entries in ability schemas

= 1.1.23 =
* New: `wc-create-order` ability — create WooCommerce orders on behalf of any customer (customer_id + line_items with product_id/quantity, billing/shipping, payment method, status, notes)
* New: `wc-update-order` ability — update billing/shipping address and customer note on existing orders
* New: `wc-delete-order` ability — trash or permanently delete (force=true) orders via the Abilities API

= 1.1.22 =
* Fixed: upload-media ability no longer returns stale attachment IDs / URLs on rapid consecutive uploads (#11) — the post + post_meta object caches are now flushed before reading the new attachment back, and a hard error is returned if the uploaded file is missing from disk

= 1.1.19 =
* Fix: Graceful error when autoloader is missing (admin notice instead of fatal error)

= 1.1.18 =
* Fixed: wc-list-products status=any now includes private products (#10)

= 1.1.14 =
* Minor fix

= 1.1.13 =
* Updated ParentPage with SVG icon support from registry

= 1.1.12 =
* Minor fix

= 1.1.11 =
* Minor fix

= 1.1.10 =
* Fix admin notice isolation for notices relocated by WordPress core JS

= 1.1.9 =
* Isolate third-party admin notices on LW plugin pages

= 1.1.8 =
* Add fresh POT file and Hungarian (hu_HU) translation

= 1.1.7 =
* New: Central plugin registry from GitHub JSON

= 1.1.6 =
* Added: `featured_image_id` field to list-posts response (fixes #1)
* Added: PHPUnit testing infrastructure with unit and integration tests
* Added: Docker devcontainer for testing environment
* Removed: Self-updater functionality (unnecessary for GitHub releases)
* Fixed: Deprecated `finfo_close()` call for PHP 8.1+ compatibility

= 1.1.5 =
* Fixed: SVG upload support via Abilities API when SVG plugins (Allow SVG, Safe SVG) are active
* Added: MIME type detection in media upload that respects third-party plugin filters

= 1.1.4 =
* Added: Custom taxonomy support in category and tag abilities via taxonomy parameter
* Added: All category/tag abilities now support any taxonomy (not just category/post_tag)

= 1.1.3 =
* Added: Custom taxonomy support in create-post and update-post
* Added: New set-post-terms ability for setting taxonomy terms
* Added: New get-post-terms ability for retrieving taxonomy terms
* Added: All taxonomies now returned in post responses
* Added: PHPCS configuration for code quality
* Fixed: Duplicate array key in TaxonomyAbilities
* Changed: Improved .gitignore configuration

= 1.1.2 =
* Changed: Documentation translated to English

= 1.1.1 =
* Changed: Version bump for documentation updates

= 1.1.0 =
* Changed: Renamed plugin from WP Site Manager to LW Site Manager
* Changed: Moved to LW Plugins organization
* Changed: Updated namespace to LightweightPlugins\SiteManager
* Changed: Requires PHP 8.1+ (was 8.0+)
* Added: LW Plugins unified admin menu integration

= 1.0.6 =
* Added: meta_key and meta_value parameters for list-posts filtering

= 1.0.5 =
* Fixed: Tags input now accepts both integer IDs and string names/slugs

= 1.0.4 =
* Added: Plugin Database Updates abilities
* Added: Documentation for all abilities
* Fixed: Various documentation corrections

= 1.0.3 =
* Added: Self-update functionality from private repository

= 1.0.2 =
* Fixed: Plugin update mechanism improvements

= 1.0.1 =
* Fixed: Initial bug fixes

= 1.0.0 =
* Initial release
* WordPress Abilities API integration
* Full site management capabilities

== Upgrade Notice ==

= 1.1.3 =
Adds custom taxonomy support for posts and custom post types.

= 1.1.0 =
Plugin renamed from WP Site Manager to LW Site Manager. Update your references if needed.
