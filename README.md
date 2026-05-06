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

### Updates

| Ability | Description |
|---------|-------------|
| `site-manager/check-updates` | Check for core, plugin, and theme updates |
| `site-manager/update-plugin` | Update a specific plugin |
| `site-manager/update-theme` | Update a specific theme |
| `site-manager/update-core` | Update WordPress core |
| `site-manager/update-all` | Update everything (with PHP error detection) |

### Plugin Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-plugins` | List all installed plugins |
| `site-manager/install-plugin` | Install a plugin from WordPress.org |
| `site-manager/activate-plugin` | Activate a plugin |
| `site-manager/deactivate-plugin` | Deactivate a plugin |
| `site-manager/delete-plugin` | Delete a plugin |

### Theme Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-themes` | List all installed themes |
| `site-manager/install-theme` | Install a theme from WordPress.org |
| `site-manager/activate-theme` | Switch to a different theme |
| `site-manager/delete-theme` | Delete a theme |

### Content Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-posts` | List posts with filtering |
| `site-manager/get-post` | Get a single post |
| `site-manager/create-post` | Create a new post |
| `site-manager/update-post` | Update an existing post |
| `site-manager/delete-post` | Delete a post |
| `site-manager/set-post-terms` | Set taxonomy terms for a post |
| `site-manager/get-post-terms` | Get taxonomy terms for a post |

### Page Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-pages` | List pages |
| `site-manager/get-page` | Get a single page |
| `site-manager/create-page` | Create a new page |
| `site-manager/update-page` | Update an existing page |
| `site-manager/delete-page` | Delete a page |

### Taxonomy Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-categories` | List categories |
| `site-manager/get-category` | Get a single category |
| `site-manager/create-category` | Create a category |
| `site-manager/update-category` | Update a category |
| `site-manager/delete-category` | Delete a category |
| `site-manager/list-tags` | List tags |
| `site-manager/get-tag` | Get a single tag |
| `site-manager/create-tag` | Create a tag |
| `site-manager/update-tag` | Update a tag |
| `site-manager/delete-tag` | Delete a tag |

### User Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-users` | List users |
| `site-manager/get-user` | Get user details |
| `site-manager/create-user` | Create a new user |
| `site-manager/update-user` | Update a user |
| `site-manager/delete-user` | Delete a user |

### Media Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-media` | List media files |
| `site-manager/upload-media` | Upload a media file |
| `site-manager/delete-media` | Delete a media file |

### Comments

| Ability | Description |
|---------|-------------|
| `site-manager/list-comments` | List comments |
| `site-manager/approve-comment` | Approve a comment |
| `site-manager/spam-comment` | Mark comment as spam |
| `site-manager/delete-comment` | Delete a comment |

### Backup

| Ability | Description |
|---------|-------------|
| `site-manager/create-backup` | Create a full or partial site backup |
| `site-manager/list-backups` | List all available backups |
| `site-manager/restore-backup` | Restore site from a backup |
| `site-manager/delete-backup` | Delete a backup |

### Health & Diagnostics

| Ability | Description |
|---------|-------------|
| `site-manager/health-check` | Run comprehensive site health check |
| `site-manager/error-log` | Retrieve recent PHP errors |

### Database

| Ability | Description |
|---------|-------------|
| `site-manager/optimize-database` | Optimize database tables |
| `site-manager/cleanup-database` | Remove revisions, transients, spam, etc. |
| `site-manager/repair-database` | Repair database tables |

### Cache

| Ability | Description |
|---------|-------------|
| `site-manager/flush-cache` | Clear all caches (object, page, opcache) |

### Settings

| Ability | Description |
|---------|-------------|
| `site-manager/get-option` | Get a WordPress option |
| `site-manager/update-option` | Update a WordPress option |
| `site-manager/list-options` | List options with filtering |

### WooCommerce (if active, requires WooCommerce 7.0+)

**Products** — `wc-list-products`, `wc-get-product`, `wc-create-product`, `wc-update-product`, `wc-delete-product`, `wc-duplicate-product`, `wc-update-stock`, `wc-list-product-categories`, `wc-list-variations`, `wc-bulk-products`

**Orders (CRUD)** — `wc-list-orders`, `wc-get-order`, `wc-create-order`, `wc-update-order`, `wc-delete-order`, `wc-update-order-status`

**Order extras** — `wc-list-order-statuses`, `wc-create-refund`, `wc-list-order-notes`, `wc-add-order-note`, `wc-bulk-orders`

**Order line items** — `wc-add-order-item`, `wc-update-order-item`, `wc-remove-order-item`

**Order coupons & fees** — `wc-apply-order-coupon`, `wc-remove-order-coupon`, `wc-add-order-fee`, `wc-remove-order-fee`

**Order shipping & recalc** — `wc-set-order-shipping`, `wc-remove-order-shipping`, `wc-recalculate-order`

**Order payment workflow** — `wc-mark-order-paid` (with `silent` flag), `wc-send-order-email` (6-template whitelist), `wc-get-payment-url`

**Reports** — `wc-sales-report`, `wc-top-sellers`, `wc-orders-totals`, `wc-revenue-stats`, `wc-low-stock-products`, `wc-products-totals`

All order modification abilities reject `cancelled` / `refunded` / `failed` orders with HTTP 409 (`order_locked`), and support a `recalculate=false` flag for chained operations followed by a single `wc-recalculate-order` call.

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

Most recent: **1.1.25** — split monolithic `WooCommerceAbilities.php` into focused classes and added 13 new order management abilities (line items, coupons, fees, shipping, payment workflow).

## License

GPL-2.0-or-later


## Sponsor

<a href="https://sinann.io/">
  <img src="https://sinann.io/favicon.svg" alt="Sinann" width="40">
</a>

Supported by [Sinann](https://sinann.io/)
