# Pages Abilities

## list-pages

Oldalak listázása szűrési lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-pages/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `status` | string | nem | `any` | Állapot: `publish`, `draft`, `pending`, `trash`, `any` |
| `author` | integer | nem | - | Szerző ID |
| `search` | string | nem | - | Keresés címben és tartalomban |
| `parent` | integer | nem | - | Szülő oldal ID |
| `orderby` | string | nem | `menu_order` | Rendezés |
| `order` | string | nem | `ASC` | Irány: `ASC`, `DESC` |

### Output

```json
{
  "pages": [
    {
      "id": 2,
      "title": "Sample Page",
      "slug": "sample-page",
      "status": "publish",
      "type": "page",
      "date": "2026-01-12 20:27:32",
      "modified": "2026-01-12 20:27:32",
      "author": 1
    }
  ],
  "total": 2,
  "total_pages": 1,
  "limit": 20,
  "offset": 0,
  "has_more": false
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-pages/run?input%5Bstatus%5D=publish'
```

---

## get-page

Egyetlen oldal részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-page/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | - | Page ID |
| `slug` | string | - | Page slug |

> **Megjegyzés:** `id` vagy `slug` megadása kötelező.

### Output

```json
{
  "success": true,
  "page": {
    "id": 2,
    "title": "Sample Page",
    "slug": "sample-page",
    "status": "publish",
    "type": "page",
    "date": "2026-01-12 20:27:32",
    "modified": "2026-01-12 20:27:32",
    "author": 1,
    "content": "<p>Tartalom...</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "permalink": "https://example.com/sample-page/",
    "featured_image": null,
    "comment_status": "closed"
  }
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-page/run?input%5Bid%5D=2'
```

---

## create-page

Új oldal létrehozása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-page/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `title` | string | **igen** | - | Cím |
| `content` | string | nem | - | Tartalom (HTML) |
| `excerpt` | string | nem | - | Kivonat |
| `status` | string | nem | `draft` | Állapot: `draft`, `publish`, `pending`, `private` |
| `slug` | string | nem | auto | URL slug |
| `author` | integer | nem | current | Szerző ID |
| `parent` | integer | nem | - | Szülő oldal ID |
| `menu_order` | integer | nem | - | Menü sorrend |
| `template` | string | nem | - | Oldal sablon slug |
| `featured_image` | integer | nem | - | Kiemelt kép ID |
| `meta` | object | nem | - | Meta mezők |

### Output

```json
{
  "success": true,
  "message": "Post created successfully",
  "id": 38,
  "page": {
    "id": 38,
    "title": "Teszt Oldal",
    "slug": "teszt-oldal",
    "status": "publish",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"title":"Új oldal","content":"<p>Tartalom</p>","status":"publish"}}'
```

---

## update-page

Meglévő oldal módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-page/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Page ID |
| `title` | string | nem | Új cím |
| `content` | string | nem | Új tartalom |
| `excerpt` | string | nem | Új kivonat |
| `status` | string | nem | Új állapot |
| `slug` | string | nem | Új slug |
| `author` | integer | nem | Új szerző |
| `parent` | integer | nem | Szülő ID |
| `menu_order` | integer | nem | Menü sorrend |
| `template` | string | nem | Sablon |
| `featured_image` | integer | nem | Kiemelt kép ID |
| `meta` | object | nem | Meta mezők |

### Output

```json
{
  "success": true,
  "message": "Post updated successfully",
  "page": {
    "id": 38,
    "title": "Frissített oldal",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":38,"title":"Frissített cím"}}'
```

---

## delete-page

Oldal törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-page/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Page ID |
| `force` | boolean | nem | `false` | Végleges törlés |

### Output

```json
{
  "success": true,
  "message": "Post moved to trash",
  "deleted_id": 38,
  "force_delete": false
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-page/run?input%5Bid%5D=38'
```

---

## page-hierarchy

Hierarchikus oldalfa lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/page-hierarchy/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `status` | string | nem | `publish` | Oldalak állapota |

### Output

```json
{
  "hierarchy": [
    {
      "id": 2,
      "title": "Sample Page",
      "slug": "sample-page",
      "status": "publish",
      "menu_order": 0
    }
  ],
  "total": 2
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/page-hierarchy/run'
```

---

## page-templates

Elérhető oldalsablonok listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/page-templates/run`

### Output

```json
{
  "templates": [
    {
      "slug": "default",
      "name": "Default Template"
    },
    {
      "slug": "page-no-title",
      "name": "Page No Title"
    }
  ],
  "total": 2
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/page-templates/run'
```

---

## front-page-settings

Kezdőlap és blog oldal beállítások lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/front-page-settings/run`

### Output

```json
{
  "display_mode": "posts",
  "homepage": {},
  "posts_page": {}
}
```

Ha statikus oldal van beállítva:

```json
{
  "display_mode": "page",
  "homepage": {
    "id": 10,
    "title": "Kezdőlap",
    "slug": "home"
  },
  "posts_page": {
    "id": 15,
    "title": "Blog",
    "slug": "blog"
  }
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/front-page-settings/run'
```

---

## set-homepage

Oldal beállítása kezdőlapként.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-homepage/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Page ID |

### Output

```json
{
  "success": true,
  "message": "Homepage updated successfully",
  "page_id": 10,
  "previous_id": 0
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-homepage/run?input%5Bid%5D=10'
```

---

## set-posts-page

Oldal beállítása blog oldalként.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-posts-page/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Page ID |

### Output

```json
{
  "success": true,
  "message": "Posts page updated successfully",
  "page_id": 15,
  "previous_id": 0
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-posts-page/run?input%5Bid%5D=15'
```
