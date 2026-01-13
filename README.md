# WP Site Manager

WordPress Site Manager using the Abilities API - A native, AI-ready alternative to MainWP.

**Author:** [trueqap](https://github.com/trueqap)

## Requirements

- PHP 8.0+
- WordPress 6.9+
- WordPress Abilities API plugin

## Installation

```bash
cd wp-content/plugins/wp-site-manager
composer install
```

Then activate the plugin in WordPress admin.

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
| `site-manager/activate-plugin` | Activate a plugin |
| `site-manager/deactivate-plugin` | Deactivate a plugin |

### Theme Management

| Ability | Description |
|---------|-------------|
| `site-manager/list-themes` | List all installed themes |
| `site-manager/activate-theme` | Switch to a different theme |

### Backup

| Ability | Description |
|---------|-------------|
| `site-manager/create-backup` | Create a full or partial site backup |
| `site-manager/list-backups` | List all available backups |
| `site-manager/restore-backup` | Restore site from a backup |

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

### Cache

| Ability | Description |
|---------|-------------|
| `site-manager/flush-cache` | Clear all caches (object, page, opcache) |

## Usage Examples

### REST API

```bash
# Check for updates
curl -X GET "https://yoursite.com/wp-json/wp-abilities/v1/site-manager/check-updates/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD"

# Update all plugins
curl -X POST "https://yoursite.com/wp-json/wp-abilities/v1/site-manager/update-all/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"include_plugins": true, "include_themes": true, "stop_on_error": true}'

# Create backup
curl -X POST "https://yoursite.com/wp-json/wp-abilities/v1/site-manager/create-backup/run" \
  -H "Authorization: Basic BASE64_ENCODED_APP_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"include_database": true, "include_uploads": true}'
```

### PHP

```php
// Check updates
$ability = wp_get_ability( 'site-manager/check-updates' );
$updates = $ability->execute( [ 'type' => 'all' ] );

// Update a plugin
$ability = wp_get_ability( 'site-manager/update-plugin' );
$result = $ability->execute( [ 'plugin' => 'woocommerce/woocommerce.php' ] );

if ( ! $result['success'] ) {
    // Check for PHP errors
    foreach ( $result['php_errors'] as $error ) {
        error_log( 'Update error: ' . $error );
    }
}
```

### JavaScript (Gutenberg)

```javascript
import { executeAbility } from '@wordpress/abilities';

// Check site health
executeAbility( 'site-manager/health-check', {} )
  .then( ( result ) => {
    console.log( 'Health score:', result.score );
    console.log( 'Issues:', result.issues );
  });
```

## PHP Error Detection

The plugin monitors for PHP errors during updates:

1. **Runtime errors** - Captured via custom error handler
2. **Fatal errors** - Captured via shutdown function
3. **Log monitoring** - Reads new entries from PHP error log
4. **Site health check** - Makes HTTP request to detect white screen

When `stop_on_error` is enabled (default), the update process stops if any PHP error is detected.

```php
// Update with error detection
$result = wp_get_ability( 'site-manager/update-all' )->execute([
    'include_plugins' => true,
    'include_themes' => true,
    'stop_on_error' => true,  // Stop if PHP error detected
]);

if ( ! empty( $result['php_errors'] ) ) {
    // Handle errors
    foreach ( $result['php_errors'] as $error ) {
        // Log, notify, rollback, etc.
    }
}

if ( $result['stopped_early'] ) {
    // Update was stopped due to PHP error
}
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

## License

GPL-2.0-or-later
