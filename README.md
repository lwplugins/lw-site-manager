# Lightweight Site Manager

WordPress Site Manager using the Abilities API - A native, AI-ready alternative to MainWP.

**Part of [LW Plugins](https://lwplugins.com) - Lightweight plugins for WordPress.**

## Requirements

- PHP 8.1+
- WordPress 6.9+
- WordPress Abilities API

## Installation

### Via Composer

```bash
composer require lwplugins/lw-site-manager
```

### Manual

1. Download the latest release from GitHub
2. Upload to `wp-content/plugins/lw-site-manager`
3. Activate the plugin in WordPress admin

## Available Abilities

The plugin currently registers **167 abilities**. All slugs use the `site-manager/` namespace and are grouped below by their ability category.

### Core abilities

#### Updates (5)

`check-updates`, `update-core`, `update-plugin`, `update-theme`, `update-all` (with PHP error detection)

#### Plugins (5)

`list-plugins`, `install-plugin`, `activate-plugin`, `deactivate-plugin`, `delete-plugin`

#### Themes (4)

`list-themes`, `install-theme`, `activate-theme`, `delete-theme`

#### Posts (8)

`list-posts`, `get-post`, `create-post`, `update-post`, `delete-post`, `restore-post`, `duplicate-post`, `bulk-posts`

> Plus `get-post-types`, `get-post-terms`, `set-post-terms` (3) for taxonomy/post-type metadata.

#### Pages (10)

`list-pages`, `get-page`, `create-page`, `update-page`, `delete-page`, `restore-page`, `duplicate-page`, `page-hierarchy`, `page-templates`, `set-page-template`, `reorder-pages`

#### Taxonomy (10)

Categories: `list-categories`, `get-category`, `create-category`, `update-category`, `delete-category`
Tags: `list-tags`, `get-tag`, `create-tag`, `update-tag`, `delete-tag`

> Both groups accept a `taxonomy` parameter so they also work on any custom taxonomy.

#### Users (7)

`list-users`, `get-user`, `create-user`, `update-user`, `delete-user`, `get-roles`, `reset-password`

#### Comments (9)

`list-comments`, `get-comment`, `create-comment`, `update-comment`, `delete-comment`, `approve-comment`, `spam-comment`, `bulk-comments`, `comment-counts`

#### Media (5)

`list-media`, `get-media`, `upload-media`, `update-media`, `delete-media`

#### Settings (10)

`get-general-settings`, `update-general-settings`, `get-reading-settings`, `update-reading-settings`, `get-discussion-settings`, `update-discussion-settings`, `get-permalink-settings`, `update-permalink-settings`, `front-page-settings`, `set-homepage`, `set-posts-page`

#### Meta (12)

| Entity | Get | Set | Delete |
|--------|-----|-----|--------|
| Posts | `get-post-meta` | `set-post-meta` | `delete-post-meta` |
| Users | `get-user-meta` | `set-user-meta` | `delete-user-meta` |
| Terms | `get-term-meta` | `set-term-meta` | `delete-term-meta` |
| Comments | `get-comment-meta` | `set-comment-meta` | `delete-comment-meta` |

> Inline `meta` fields are also accepted by all `create-*` / `update-*` abilities for posts, pages, users, terms, comments, and the WooCommerce equivalents below.

#### Backup (6)

`create-backup`, `list-backups`, `backup-status`, `cancel-backup`, `restore-backup`, `delete-backup`

> Backups are processed in chunks via WP-Cron — `create-backup` returns immediately and `backup-status` polls progress.

#### Database (2)

`optimize-database`, `cleanup-database`

#### Cache (1)

`flush-cache` — covers WP object cache, OPcache, and the major page-cache plugins (WP Rocket, W3TC, WP Super Cache, LiteSpeed, WP Fastest Cache, Cache Enabler, Autoptimize, Kinsta, SG Optimizer, Cloudflare WP plugin, Redis, Varnish).

#### Health & diagnostics (2)

`health-check`, `error-log`

#### Plugin database updates (4)

`check-plugin-db-updates`, `get-supported-db-plugins`, `update-plugin-db`, `update-all-plugin-dbs`

### WooCommerce abilities (requires WooCommerce 7.0+)

#### Products (10)

`wc-list-products`, `wc-get-product`, `wc-create-product`, `wc-update-product`, `wc-delete-product`, `wc-duplicate-product`, `wc-update-stock`, `wc-list-product-categories`, `wc-list-variations`, `wc-bulk-products`

#### Orders — CRUD (6)

`wc-list-orders`, `wc-get-order`, `wc-create-order`, `wc-update-order`, `wc-delete-order`, `wc-update-order-status`

#### Orders — extras (5)

`wc-list-order-statuses`, `wc-create-refund`, `wc-list-order-notes`, `wc-add-order-note`, `wc-bulk-orders`

#### Order line items (3)

`wc-add-order-item`, `wc-update-order-item`, `wc-remove-order-item`

#### Order coupons & fees (4)

`wc-apply-order-coupon`, `wc-remove-order-coupon`, `wc-add-order-fee`, `wc-remove-order-fee`

#### Order shipping & recalc (3)

`wc-set-order-shipping`, `wc-remove-order-shipping`, `wc-recalculate-order`

#### Order payment workflow (3)

`wc-mark-order-paid` (with `silent` flag), `wc-send-order-email` (6-template whitelist), `wc-get-payment-url`

#### Reports (6)

`wc-sales-report`, `wc-top-sellers`, `wc-orders-totals`, `wc-revenue-stats`, `wc-low-stock-products`, `wc-products-totals`

#### Global attributes (5)

`wc-list-attributes`, `wc-get-attribute`, `wc-create-attribute`, `wc-update-attribute`, `wc-delete-attribute`

#### Attribute terms (4)

`wc-list-attribute-terms`, `wc-create-attribute-term`, `wc-update-attribute-term`, `wc-delete-attribute-term`

#### Product-attribute bindings (3)

`wc-set-product-attributes` (full replace), `wc-add-product-attribute`, `wc-remove-product-attribute`

> Supports both global (`pa_*`) and custom per-product attributes.

#### Variations (4)

`wc-generate-variations` (cartesian auto-fill of variation-flagged attributes), `wc-create-variation`, `wc-update-variation`, `wc-delete-variation`

#### WooCommerce meta (6)

| Entity | Get | Set | Delete |
|--------|-----|-----|--------|
| Products / variations | `wc-get-product-meta` | `wc-set-product-meta` | `wc-delete-product-meta` |
| Orders | `wc-get-order-meta` | `wc-set-order-meta` | `wc-delete-order-meta` |

> All HPOS-aware via `WC_Data` API. Inline `meta` is also accepted by `wc-create-product` / `wc-update-product`, `wc-create-order` / `wc-update-order`, `wc-create-variation` / `wc-update-variation`, `wc-add-order-item` / `wc-update-order-item`.

### Behavioral notes

- **Order locking** — every order modification ability rejects `cancelled` / `refunded` / `failed` orders with HTTP 409 (`order_locked`).
- **Deferred recalculation** — order item/coupon/fee/shipping mutations support `recalculate=false`; chain several mutations and finish with a single `wc-recalculate-order` call.
- **Email opt-out** — `wc-mark-order-paid` accepts `silent: true` to suppress the customer notification.

## Documentation

Full API documentation is available in the [docs/abilities](docs/abilities/) directory.

## Usage Examples

### REST API

```bash
# Check for updates
curl -X GET "https://yoursite.com/wp-json/wp-abilities/v1/abilities/site-manager/check-updates/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD"

# Create a post with custom taxonomy
curl -X POST "https://yoursite.com/wp-json/wp-abilities/v1/abilities/site-manager/create-post/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"input":{"title":"My Post","content":"Content here","status":"publish","taxonomies":{"my_custom_tax":[1,2,3]}}}'

# Set terms for a custom post type
curl -X POST "https://yoursite.com/wp-json/wp-abilities/v1/abilities/site-manager/set-post-terms/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":123,"taxonomy":"product_cat","terms":[5,10]}}'
```

### PHP

```php
// Check updates
$ability = wp_get_ability( 'site-manager/check-updates' );
$updates = $ability->execute( [ 'type' => 'all' ] );

// Create a post with custom taxonomies
$ability = wp_get_ability( 'site-manager/create-post' );
$result = $ability->execute([
    'title' => 'My New Post',
    'content' => 'Post content here',
    'status' => 'publish',
    'taxonomies' => [
        'category' => [1, 2],
        'post_tag' => [5, 6, 7],
        'my_custom_tax' => [10, 11],
    ],
]);
```

## Authentication

Use WordPress Application Passwords for REST API authentication:

1. Go to Users → Your Profile
2. Scroll to "Application Passwords"
3. Create new application password
4. Use Basic Auth: `Authorization: Basic base64(username:app_password)`

## AI Integration

This plugin is designed for AI agent integration via:

- **REST API** - Any AI can call abilities via HTTP
- **MCP Adapter** - Claude, GPT can use abilities as tools
- **Agentic Loops** - AI decides which abilities to call

Example AI workflow:
```
User: "Check my site and update everything safely"

AI Agent:
1. Calls site-manager/health-check
2. Calls site-manager/check-updates
3. Calls site-manager/create-backup
4. Calls site-manager/update-all with stop_on_error=true
5. If errors: reports issues, suggests rollback
6. If success: calls site-manager/health-check again
7. Returns summary to user
```

## Links

- [GitHub](https://github.com/lwplugins/lw-site-manager)
- [LW Plugins](https://lwplugins.com)
- [All LW Plugins](https://github.com/lwplugins)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

Most recent: **1.1.26** — split `WooCommerceAbilities.php` into focused classes and added 38 new abilities: order management (line items, coupons, fees, shipping, payment workflow), full attribute / variation CRUD, HPOS-aware meta on every entity, plus inline `meta` on every create/update.

## License

GPL-2.0-or-later


## Sponsor

<a href="https://sinann.io/">
  <img src="https://sinann.io/favicon.svg" alt="Sinann" width="40">
</a>

Supported by [Sinann](https://sinann.io/)
