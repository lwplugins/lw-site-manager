# WordPress Abilities API

## What is the Abilities API?

The WordPress Abilities API is a new feature introduced in WordPress 6.9 that enables WordPress sites to expose structured, discoverable, and secure operations to external systems (AI assistants, automation tools, remote administration panels).

The Abilities API is essentially a **standardized interface** through which:
- AI systems (Claude, ChatGPT, Gemini) can understand and execute WordPress operations
- Automation tools (n8n, Make, Zapier) can integrate with WordPress
- Central management interfaces can control multiple WordPress sites in a unified way

## Why is it Better Than REST API?

| Property | REST API | Abilities API |
|----------|----------|---------------|
| **Discoverability** | Requires reading documentation | Self-describing - with JSON Schema |
| **Validation** | Custom implementation | Built-in input/output validation |
| **Permissions** | Capability-based | Ability-level fine-tuning |
| **AI Integration** | No native support | MCP-compatible, AI-ready |
| **Annotations** | None | readonly, destructive, idempotent |

## How Does It Work?

### 1. Ability Registration

An ability is a well-defined operation that has:
- **Name** (e.g., `site-manager/create-backup`)
- **Description** (what it does)
- **Input Schema** (expected parameters - JSON Schema)
- **Output Schema** (what it returns - JSON Schema)
- **Execute Callback** (the actual logic)
- **Permission Check** (who can run it)
- **Metadata** (available in REST, destructive, etc.)

```php
wp_register_ability( 'site-manager/create-backup', [
    'label'       => 'Create Backup',
    'description' => 'Create a full site backup',
    'category'    => 'maintenance',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'include_database' => [ 'type' => 'boolean', 'default' => true ],
            'include_files'    => [ 'type' => 'boolean', 'default' => true ],
        ],
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'success'   => [ 'type' => 'boolean' ],
            'backup_id' => [ 'type' => 'string' ],
            'message'   => [ 'type' => 'string' ],
        ],
    ],
    'execute_callback'    => [ BackupManager::class, 'create_backup' ],
    'permission_callback' => fn() => current_user_can( 'manage_options' ),
    'meta' => [
        'show_in_rest' => true,
        'annotations'  => [
            'readonly'    => false,
            'destructive' => false,
            'idempotent'  => false,
        ],
    ],
]);
```

### 2. REST API Endpoints

If `show_in_rest => true`, the ability automatically becomes available:

| Operation | Endpoint |
|-----------|----------|
| List all abilities | `GET /wp-json/wp-abilities/v1/abilities` |
| Get single ability | `GET /wp-json/wp-abilities/v1/abilities/{name}` |
| Execute ability | `POST /wp-json/wp-abilities/v1/abilities/{name}/run` |

### 3. Execution

```bash
curl -X POST \
  -u "user:application-password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"include_database":true,"include_files":true}}' \
  https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-backup/run
```

## Annotation Meanings

| Annotation | Meaning |
|------------|---------|
| `readonly: true` | Only reads, does not modify anything (GET request) |
| `destructive: true` | May cause data loss or irreversible changes |
| `idempotent: true` | Multiple executions produce the same result |

These annotations help AI systems and automation tools understand how "dangerous" an operation is and whether user confirmation is needed.

## AI and MCP Integration

The Abilities API is designed to natively fit into the world of AI assistants:

### Model Context Protocol (MCP)

MCP is an open standard developed by Anthropic for communication between AI models and external systems. The Abilities API fits perfectly with this:

```
+-------------+     MCP      +-----------------+     REST     +-------------+
|   Claude    | <----------> |  MCP Adapter    | <----------> |  WordPress  |
|   (AI)      |              |                 |              |  Abilities  |
+-------------+              +-----------------+              +-------------+
```

### How Does an AI Use It?

1. **Discovery**: The AI retrieves available abilities
2. **Understanding**: From the JSON Schema, it understands what parameters are needed
3. **Validation**: From the annotations, it knows if confirmation is required
4. **Execution**: It calls the ability with the appropriate parameters
5. **Processing**: It extracts information from the structured response

## LW Site Manager Plugin

This project is a WordPress plugin that uses the Abilities API to provide complete site management functionality:

### Categories

| Category | Description | Example Abilities |
|----------|-------------|-------------------|
| **maintenance** | Maintenance | backup, cache flush, DB optimization |
| **diagnostics** | Diagnostics | health check, error log |
| **updates** | Updates | plugin/theme/core update |
| **plugins** | Plugins | list, activate, deactivate, install |
| **themes** | Themes | list, activate, install |
| **users** | Users | CRUD, role management |
| **content** | Content | posts, pages, comments, media |
| **settings** | Settings | general, reading, discussion, permalinks |
| **wc-products** | WooCommerce Products | CRUD, stock, variations |
| **wc-orders** | WooCommerce Orders | list, status update, refunds |
| **wc-reports** | WooCommerce Reports | sales, top sellers, revenue |

### Installation

```bash
composer require lwplugins/lw-site-manager
```

The plugin automatically registers all abilities on the `wp_abilities_api_init` hook.

### Authentication

The API uses Application Passwords:

1. WordPress admin > Users > Profile
2. Application Passwords section
3. Generate new password
4. Usage: `curl -u "username:xxxx-xxxx-xxxx-xxxx"`

## Documentation

Detailed ability documentation:

- [Posts](posts.md)
- [Pages](pages.md)
- [Comments](comments.md)
- [Media](media.md)
- [Users](user-management.md)
- [Settings](settings.md)
- [Maintenance](maintenance.md)
- [Plugins](plugin-management.md)
- [Themes](theme-management.md)
- [Categories](categories.md)
- [Tags](tags.md)
- [Meta](meta.md)
- [WooCommerce](woocommerce.md)

## Official Sources

- [Introducing the WordPress Abilities API](https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/)
- [Abilities API - Common APIs Handbook](https://developer.wordpress.org/apis/abilities-api/)
- [Abilities API in WordPress 6.9](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/)
- [@wordpress/abilities Package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)
- [GitHub: WordPress/abilities-api](https://github.com/WordPress/abilities-api)
