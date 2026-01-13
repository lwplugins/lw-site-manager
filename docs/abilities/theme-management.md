# Theme Management Abilities

## list-themes

Listázza az összes telepített témát.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-themes/run`

### Input

Nincs kötelező paraméter.

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

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-themes/run'
```

---

## install-theme

Téma telepítése a WordPress.org repository-ból.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/install-theme/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `slug` | string | **igen** | - | Téma slug a WordPress.org-ról (pl. `oceanwp`) |
| `activate` | boolean | nem | `false` | Aktiválja-e telepítés után |

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

### Hibakódok

| Kód | Leírás |
|-----|--------|
| `themes_api_failed` | A téma nem található a WordPress.org-on |
| `theme_exists` | A téma már telepítve van |

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/install-theme/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"slug":"oceanwp","activate":false}}'
```

---

## activate-theme

Téma aktiválása (váltás másik témára).

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/activate-theme/run`

> **Megjegyzés:** DELETE method szükséges mert destructive művelet.

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `theme` | string | **igen** | - | Téma slug (pl. `oceanwp`) |

### Output

```json
{
  "success": true,
  "message": "Theme activated successfully",
  "php_errors": []
}
```

### Hibakódok

| Kód | Leírás |
|-----|--------|
| `theme_not_found` | A téma nem található |

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/activate-theme/run?input%5Btheme%5D=oceanwp'
```

---

## delete-theme

Téma törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-theme/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `theme` | string | **igen** | - | Téma slug (pl. `oceanwp`) |

### Output

```json
{
  "success": true,
  "message": "Theme deleted successfully"
}
```

### Hibakódok

| Kód | Leírás |
|-----|--------|
| `theme_not_found` | A téma nem található |
| `cannot_delete_active` | Aktív témát nem lehet törölni |

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-theme/run?input%5Btheme%5D=oceanwp'
```

---

## update-theme

Egyetlen téma frissítése.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-theme/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `theme` | string | **igen** | - | Téma slug (pl. `astra`) |

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

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-theme/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"theme":"astra"}}'
```
