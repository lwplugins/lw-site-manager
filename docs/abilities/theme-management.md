# Theme Management Abilities

## list-themes

List all installed themes.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-themes/run`

### Input

No required parameters.

### Output

```json
{
  "themes": [
    {
      "slug": "twentytwentyfive",
      "name": "Twenty Twenty-Five",
      "version": "1.4",
      "author": "the WordPress team",
      "description": "Twenty Twenty-Five emphasizes simplicity...",
      "active": true,
      "parent": null
    },
    {
      "slug": "astra",
      "name": "Astra",
      "version": "4.12.0",
      "author": "Brainstorm Force",
      "description": "The Astra WordPress theme...",
      "active": false,
      "parent": null
    }
  ],
  "total": 5
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-themes/run'
```

---

## install-theme

Install a theme from the WordPress.org repository.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/install-theme/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `slug` | string | **yes** | - | Theme slug from WordPress.org (e.g., `oceanwp`) |
| `activate` | boolean | no | `false` | Activate after installation |

### Output

```json
{
  "success": true,
  "message": "Theme \"OceanWP\" installed successfully (v4.1.4)",
  "theme": "oceanwp",
  "name": "OceanWP",
  "version": "4.1.4",
  "activated": false,
  "php_errors": []
}
```

### Error Codes

| Code | Description |
|------|-------------|
| `themes_api_failed` | Theme not found on WordPress.org |
| `theme_exists` | Theme is already installed |

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/install-theme/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"slug":"oceanwp","activate":false}}'
```

---

## activate-theme

Activate a theme (switch to another theme).

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/activate-theme/run`

> **Note:** DELETE method is required because this is a destructive operation.

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `theme` | string | **yes** | - | Theme slug (e.g., `oceanwp`) |

### Output

```json
{
  "success": true,
  "message": "Theme activated successfully",
  "php_errors": []
}
```

### Error Codes

| Code | Description |
|------|-------------|
| `theme_not_found` | Theme not found |

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/activate-theme/run?input%5Btheme%5D=oceanwp'
```

---

## delete-theme

Delete a theme.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-theme/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `theme` | string | **yes** | - | Theme slug (e.g., `oceanwp`) |

### Output

```json
{
  "success": true,
  "message": "Theme deleted successfully"
}
```

### Error Codes

| Code | Description |
|------|-------------|
| `theme_not_found` | Theme not found |
| `cannot_delete_active` | Cannot delete active theme |

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-theme/run?input%5Btheme%5D=oceanwp'
```

---

## update-theme

Update a single theme.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-theme/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `theme` | string | **yes** | - | Theme slug (e.g., `astra`) |

### Output

```json
{
  "success": true,
  "message": "Theme updated successfully: 4.11.9 → 4.12.0",
  "php_errors": [],
  "old_version": "4.11.9",
  "new_version": "4.12.0"
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-theme/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"theme":"astra"}}'
```
