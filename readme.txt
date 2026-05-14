=== LW Site Manager ===
Contributors: lwplugins
Tags: site-manager, maintenance, ai, rest-api, abilities
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.1.27
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

* **REST API** - Any AI can call abilities via HTTP
* **MCP Adapter** - Claude, GPT can use abilities as tools
* **Agentic Loops** - AI decides which abilities to call

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

= Does this work with WooCommerce? =

Yes — and the WooCommerce coverage is comprehensive. Beyond product and order CRUD, you can edit existing orders end-to-end: add or remove line items, apply or remove coupons, add custom fees, change shipping, recalculate totals, mark orders paid, re-send order emails, and generate pay-for-order URLs. Requires WooCommerce 7.0 or higher.

= Is this a MainWP alternative? =

Yes, LW Site Manager provides similar functionality to MainWP but uses the native WordPress Abilities API instead of custom endpoints.

== Screenshots ==

1. Site health check results
2. Update management interface
3. Backup creation options

== Changelog ==

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
