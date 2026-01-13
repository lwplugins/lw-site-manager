# Plugin Management Abilities

## list-plugins

Listázza az összes telepített plugint.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-plugins/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `status` | string | nem | `all` | Szűrés állapot szerint: `all`, `active`, `inactive` |

### Output

```json
{
  "plugins": [
    {
      "slug": "akismet/akismet.php",
      "name": "Akismet Anti-spam: Spam Protection",
      "version": "5.6",
      "author": "Automattic - Anti-spam Team",
      "description": "Plugin leírása...",
      "active": true
    }
  ],
  "total": 7
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-plugins/run?input%5Bstatus%5D=active'
```

---

## install-plugin

Plugin telepítése a WordPress.org repository-ból.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/install-plugin/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `slug` | string | **igen** | - | Plugin slug a WordPress.org-ról (pl. `hello-dolly`) |
| `activate` | boolean | nem | `false` | Aktiválja-e telepítés után |

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

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/install-plugin/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"slug":"hello-dolly","activate":true}}'
```

---

## activate-plugin

Plugin aktiválása.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/activate-plugin/run`

> **Megjegyzés:** DELETE method szükséges mert destructive művelet.

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `plugin` | string | **igen** | - | Plugin slug (pl. `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin activated successfully",
  "php_errors": []
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/activate-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## deactivate-plugin

Plugin deaktiválása.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/deactivate-plugin/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `plugin` | string | **igen** | - | Plugin slug (pl. `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin deactivated successfully"
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/deactivate-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## delete-plugin

Plugin törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-plugin/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `plugin` | string | **igen** | - | Plugin fájl útvonal (pl. `hello-dolly/hello.php`) |

### Output

```json
{
  "success": true,
  "message": "Plugin deleted successfully"
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-plugin/run?input%5Bplugin%5D=hello-dolly%2Fhello.php'
```

---

## update-plugin

Egyetlen plugin frissítése.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-plugin/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `plugin` | string | **igen** | - | Plugin slug (pl. `classic-editor/classic-editor.php`) |

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

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-plugin/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"plugin":"classic-editor/classic-editor.php"}}'
```

---

## check-updates

Elérhető frissítések ellenőrzése (core, plugins, themes).

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/check-updates/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `type` | string | nem | `all` | Típus: `all`, `core`, `plugins`, `themes` |
| `force_refresh` | boolean | nem | `false` | Frissítési cache törlése |

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

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/check-updates/run?input%5Bforce_refresh%5D=true'
```

---

## update-all

Összes plugin és téma frissítése egyszerre.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-all/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `include_core` | boolean | nem | `false` | WordPress core frissítése is |
| `include_plugins` | boolean | nem | `true` | Pluginok frissítése |
| `include_themes` | boolean | nem | `true` | Témák frissítése |
| `stop_on_error` | boolean | nem | `true` | PHP hiba esetén leálljon |

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

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-all/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"include_core":false,"include_plugins":true,"include_themes":true}}'
```
