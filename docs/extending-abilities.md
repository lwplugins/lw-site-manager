# Extending LW Site Manager with Custom Abilities

## Overview

LW Site Manager provides two action hooks that allow any WordPress plugin to register its own abilities and categories. This means external plugins can add site management capabilities without modifying the site manager itself.

## Hooks

| Hook | When | Parameters |
|------|------|------------|
| `lw_site_manager_register_categories` | During category registration | None |
| `lw_site_manager_register_abilities` | After all core abilities are registered | `$permissions` (PermissionManager) |

## Quick Start

Register a custom ability in your plugin:

```php
// In your plugin's init or plugins_loaded hook:
add_action( 'lw_site_manager_register_categories', function (): void {
    wp_register_ability_category( 'my-plugin', [
        'label'       => __( 'My Plugin', 'my-plugin' ),
        'description' => __( 'My plugin management abilities', 'my-plugin' ),
    ]);
});

add_action( 'lw_site_manager_register_abilities', function ( $permissions ): void {
    wp_register_ability( 'my-plugin/get-status', [
        'label'               => __( 'Get Status', 'my-plugin' ),
        'description'         => __( 'Get current plugin status.', 'my-plugin' ),
        'category'            => 'my-plugin',
        'execute_callback'    => [ MyService::class, 'get_status' ],
        'permission_callback' => $permissions->callback( 'can_manage_options' ),
        'input_schema'        => [
            'type'    => 'object',
            'default' => [],
        ],
        'output_schema'       => [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'status'  => [ 'type' => 'string' ],
            ],
        ],
        'meta' => [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ],
    ]);
});
```

## Naming Convention

Use your plugin slug as prefix:

```
lw-seo/get-meta
lw-seo/set-meta
lw-cookie/get-consent
lw-firewall/get-blocked-ips
```

This prevents naming collisions between plugins.

## Permission Manager

The `$permissions` parameter provides access to the site manager's `PermissionManager`. Use `$permissions->callback( $method )` to get a permission callback:

| Method | Capability |
|--------|-----------|
| `can_manage_options` | `manage_options` |
| `can_edit_posts` | `edit_posts` |
| `can_publish_posts` | `publish_posts` |
| `can_delete_posts` | `delete_posts` |
| `can_manage_users` | `list_users` |
| `can_edit_users` | `edit_users` |
| `can_upload_files` | `upload_files` |
| `can_manage_categories` | `manage_categories` |

You can also use a custom callback:

```php
'permission_callback' => fn() => current_user_can( 'my_custom_capability' ),
```

## Ability Structure

### Input Schema

JSON Schema format. Defines what parameters the ability accepts:

```php
'input_schema' => [
    'type'       => 'object',
    'required'   => [ 'post_id' ],
    'properties' => [
        'post_id' => [
            'type'        => 'integer',
            'description' => __( 'The post ID.', 'my-plugin' ),
        ],
        'format' => [
            'type'        => 'string',
            'enum'        => [ 'full', 'summary' ],
            'default'     => 'full',
            'description' => __( 'Output format.', 'my-plugin' ),
        ],
    ],
],
```

### Output Schema

Defines the response structure:

```php
'output_schema' => [
    'type'       => 'object',
    'properties' => [
        'success' => [ 'type' => 'boolean' ],
        'data'    => [ 'type' => 'object' ],
        'message' => [ 'type' => 'string' ],
    ],
],
```

### Annotations

Tell AI agents and automation tools about the ability's behavior:

```php
'meta' => [
    'show_in_rest' => true,
    'annotations'  => [
        'readonly'    => true,   // Only reads data, no side effects
        'destructive' => false,  // Cannot cause data loss
        'idempotent'  => true,   // Safe to call multiple times
    ],
],
```

| Annotation | When `true` |
|------------|------------|
| `readonly` | GET request, no modifications. AI agents call freely. |
| `destructive` | May cause data loss. AI agents should ask confirmation. |
| `idempotent` | Multiple calls produce the same result. Safe to retry. |

### Execute Callback

A static method that receives `array $input` and returns `array` (success) or `\WP_Error` (failure):

```php
class MyService {
    public static function get_status( array $input ): array|\WP_Error {
        // Validate input.
        if ( empty( $input['post_id'] ) ) {
            return new \WP_Error(
                'missing_id',
                __( 'Post ID is required.', 'my-plugin' ),
                [ 'status' => 400 ]
            );
        }

        // Do work.
        $post = get_post( (int) $input['post_id'] );
        if ( ! $post ) {
            return new \WP_Error(
                'not_found',
                __( 'Post not found.', 'my-plugin' ),
                [ 'status' => 404 ]
            );
        }

        // Return success.
        return [
            'success' => true,
            'status'  => $post->post_status,
        ];
    }
}
```

## REST API Usage

Once registered with `show_in_rest => true`, abilities are accessible via REST:

```bash
# List your abilities
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities?category=my-plugin"

# Run a readonly ability (GET)
curl -u "user:app-password" \
  "https://example.com/wp-json/wp-abilities/v1/abilities/my-plugin/get-status/run?input[post_id]=123"

# Run a write ability (POST)
curl -u "user:app-password" \
  -X POST -H "Content-Type: application/json" \
  -d '{"input":{"post_id":123,"title":"New Title"}}' \
  "https://example.com/wp-json/wp-abilities/v1/abilities/my-plugin/set-data/run"
```

**Read-only abilities** use `GET` with `input[]` query parameters.
**Write abilities** use `POST` with `{"input":{...}}` JSON body.

## No Hard Dependency

Your plugin should work even if LW Site Manager is not installed. The hooks simply never fire:

```php
// Safe - these hooks are no-ops if site manager is not active.
add_action( 'lw_site_manager_register_categories', [ MyIntegration::class, 'register_category' ] );
add_action( 'lw_site_manager_register_abilities', [ MyIntegration::class, 'register_abilities' ] );
```

No `class_exists()` check needed. No autoload dependency. Just WordPress action hooks.

## Real-World Example: LW SEO

The `lw-seo` plugin registers 5 SEO abilities:

```
includes/SiteManager/
├── Integration.php    # Hook registration
├── SeoAbilities.php   # Ability definitions
└── SeoService.php     # Execute callbacks
```

**Integration.php** - hooks into the site manager:
```php
final class Integration {
    public static function init(): void {
        add_action( 'lw_site_manager_register_categories', [ self::class, 'register_category' ] );
        add_action( 'lw_site_manager_register_abilities', [ self::class, 'register_abilities' ] );
    }

    public static function register_category(): void {
        wp_register_ability_category( 'seo', [
            'label'       => __( 'SEO', 'lw-seo' ),
            'description' => __( 'Search engine optimization abilities', 'lw-seo' ),
        ]);
    }

    public static function register_abilities( object $permissions ): void {
        SeoAbilities::register( $permissions );
    }
}
```

**Registered abilities:**

| Ability | Type | Description |
|---------|------|-------------|
| `lw-seo/get-meta` | readonly | Get SEO meta for a post or term |
| `lw-seo/set-meta` | write | Set SEO meta (title, description, social, signals) |
| `lw-seo/get-content-signals` | readonly | Get resolved AI content signals |
| `lw-seo/get-markdown` | readonly | Get markdown representation of content |
| `lw-seo/get-options` | readonly | Get global SEO settings |

**Plugin.php** - safe init:
```php
// No-op if site manager is not active.
SiteManager\Integration::init();
```

## Recommended File Structure

```
your-plugin/
└── includes/
    └── SiteManager/
        ├── Integration.php    # Hook registration + category
        ├── Abilities.php      # wp_register_ability() calls
        └── Service.php        # Execute callbacks
```
