# Plugin Management Abilities

## list-plugins

List all installed plugins.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-plugins/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `status` | string | no | `all` | Filter by status: `all`, `active`, `inactive` |

### Output

```json
{
  "plugins": [
    {
      "slug": "akismet/akismet.php",
      "name": "Akismet Anti-spam: Spam Protection",
      "version": "5.6",
      "author": "Automattic - Anti-spam Team",
      "description": "Plugin description...",
      "active": true
    }
  ],
  "total": 7
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-plugins/run?input%5Bstatus%5D=active'
```

---

## install-plugin

Install a plugin from the WordPress.org repository.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/install-plugin/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `slug` | string | **yes** | - | Plugin slug from WordPress.org (e.g., `hello-dolly`) |
| `activate` | boolean | no | `false` | Activate after installation |

### Output

```json
{
  "success": true,
  "message": "Plugin \"Hello Dolly\" installed successfully (v1.7.2)",
  "plugin": "hello-dolly/hello.php",
  "name": "Hello Dolly",
  "version": "1.7.2",
  "activated": false,
  "php_errors": []
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/install-plugin/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"slug":"hello-dolly","activate":true}}'
```

---

## activate-plugin

Activate a plugin.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/activate-plugin/run`

> **Note:** DELETE method is required because this is a destructive operation.

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `plugin` | string | **yes** | - | Plugin slug (e.g., `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin activated successfully",
  "php_errors": []
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/activate-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## deactivate-plugin

Deactivate a plugin.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/deactivate-plugin/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `plugin` | string | **yes** | - | Plugin slug (e.g., `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin deactivated successfully"
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/deactivate-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## delete-plugin

Delete a plugin.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-plugin/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `plugin` | string | **yes** | - | Plugin file path (e.g., `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin deleted successfully"
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## update-plugin

Update a single plugin.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-plugin/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `plugin` | string | **yes** | - | Plugin slug (e.g., `classic-editor/classic-editor.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin updated successfully: 1.6.6 → 1.6.7",
  "php_errors": [],
  "old_version": "1.6.6",
  "new_version": "1.6.7"
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-plugin/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"plugin":"classic-editor/classic-editor.php"}}'
```

---

## check-updates

Check for available updates (core, plugins, themes).

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/check-updates/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `type` | string | no | `all` | Type: `all`, `core`, `plugins`, `themes` |
| `force_refresh` | boolean | no | `false` | Clear update cache |

### Output

```json
{
  "core": {
    "current": "6.9",
    "available": "6.9",
    "has_update": false
  },
  "plugins": [
    {
      "slug": "akismet/akismet.php",
      "name": "Akismet Anti-spam: Spam Protection",
      "current": "5.5.9",
      "available": "5.6"
    }
  ],
  "themes": [
    {
      "slug": "astra",
      "name": "Astra",
      "current": "4.11.9",
      "available": "4.12.0"
    }
  ],
  "total_updates": 11
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/check-updates/run?input%5Bforce_refresh%5D=true'
```

---

## update-all

Update all plugins and themes at once.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-all/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `include_core` | boolean | no | `false` | Include WordPress core update |
| `include_plugins` | boolean | no | `true` | Update plugins |
| `include_themes` | boolean | no | `true` | Update themes |
| `stop_on_error` | boolean | no | `true` | Stop on PHP error |

### Output

```json
{
  "success": true,
  "summary": "Updated: 10, Failed: 0, PHP Errors: 0",
  "updated": {
    "core": false,
    "plugins": [
      {
        "slug": "akismet/akismet.php",
        "name": "Akismet Anti-spam: Spam Protection",
        "old_version": "5.5.9",
        "new_version": "5.6"
      }
    ],
    "themes": [
      {
        "slug": "astra",
        "name": "Astra",
        "old_version": "4.11.9",
        "new_version": "4.12.0"
      }
    ]
  },
  "failed": [],
  "php_errors": [],
  "stopped_early": false
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-all/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"include_core":false,"include_plugins":true,"include_themes":true}}'
```
