# Posts Abilities

## list-posts

Bejegyzések listázása szűrési és lapozási lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-posts/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `post_type` | string | nem | `post` | Post típus |
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `status` | string | nem | `any` | Állapot: `publish`, `draft`, `pending`, `trash`, `any` |
| `author` | integer | nem | - | Szerző ID |
| `category` | string | nem | - | Kategória slug |
| `tag` | string | nem | - | Tag slug |
| `search` | string | nem | - | Keresés címben és tartalomban |
| `date_after` | string | nem | - | Dátum után (Y-m-d) |
| `date_before` | string | nem | - | Dátum előtt (Y-m-d) |
| `orderby` | string | nem | `date` | Rendezés mező |
| `order` | string | nem | `DESC` | Irány: `ASC`, `DESC` |

### Output

```json
{
  "posts": [
    {
      "id": 32,
      "title": "A Git legfontosabb parancsai",
      "slug": "a-git-legfontosabb-parancsai",
      "status": "publish",
      "type": "post",
      "date": "2026-01-13 06:19:12",
      "modified": "2026-01-13 06:19:12",
      "author": 2
    }
  ],
  "total": 6,
  "total_pages": 2,
  "limit": 3,
  "offset": 0,
  "has_more": true
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-posts/run?input%5Blimit%5D=10&input%5Bstatus%5D=publish'
```

---

## get-post

Egyetlen bejegyzés részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-post/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | - | - | Post ID |
| `slug` | string | - | - | Post slug |
| `post_type` | string | nem | `post` | Post típus (slug használatakor kötelező) |

> **Megjegyzés:** `id` vagy `slug` megadása kötelező.

### Output

```json
{
  "success": true,
  "post": {
    "id": 32,
    "title": "A Git legfontosabb parancsai",
    "slug": "a-git-legfontosabb-parancsai",
    "status": "publish",
    "type": "post",
    "date": "2026-01-13 06:19:12",
    "modified": "2026-01-13 06:19:12",
    "author": 2,
    "content": "<p>Tartalom...</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "guid": "https://example.com/...",
    "permalink": "https://example.com/a-git-legfontosabb-parancsai/",
    "author_name": "admin",
    "featured_image": {
      "id": 29,
      "url": "https://example.com/wp-content/uploads/image.jpg"
    },
    "categories": [
      {"id": 1, "name": "Uncategorized", "slug": "uncategorized"}
    ],
    "tags": [
      {"id": 5, "name": "git", "slug": "git"}
    ],
    "comment_count": 10,
    "comment_status": "open"
  }
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post/run?input%5Bid%5D=32'
```

---

## create-post

Új bejegyzés létrehozása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-post/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `title` | string | **igen** | - | Cím |
| `content` | string | nem | - | Tartalom (HTML) |
| `excerpt` | string | nem | - | Kivonat |
| `status` | string | nem | `draft` | Állapot: `draft`, `publish`, `pending`, `private`, `future` |
| `post_type` | string | nem | `post` | Post típus |
| `slug` | string | nem | auto | URL slug |
| `author` | integer | nem | current | Szerző ID |
| `parent` | integer | nem | - | Szülő post ID |
| `menu_order` | integer | nem | - | Menü sorrend |
| `date` | string | nem | now | Dátum (Y-m-d H:i:s) |
| `categories` | array | nem | - | Kategória ID-k |
| `tags` | array | nem | - | Tag nevek vagy ID-k |
| `featured_image` | integer | nem | - | Kiemelt kép attachment ID |
| `meta` | object | nem | - | Egyedi meta mezők |

### Output

```json
{
  "success": true,
  "message": "Post created successfully",
  "post": {
    "id": 33,
    "title": "Teszt Post",
    "slug": "teszt-post",
    "status": "publish",
    "type": "post",
    "date": "2026-01-13 06:36:40",
    "modified": "2026-01-13 06:36:40",
    "author": 2,
    "content": "<p>Tartalom</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "permalink": "https://example.com/teszt-post/",
    "featured_image": null,
    "categories": [{"id": 1, "name": "Uncategorized", "slug": "uncategorized"}],
    "tags": []
  },
  "id": 33
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"title":"Új bejegyzés","content":"<p>Tartalom</p>","status":"publish","categories":[1,2]}}'
```

---

## update-post

Meglévő bejegyzés módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-post/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Post ID |
| `title` | string | nem | Új cím |
| `content` | string | nem | Új tartalom |
| `excerpt` | string | nem | Új kivonat |
| `status` | string | nem | Új állapot |
| `slug` | string | nem | Új slug |
| `author` | integer | nem | Új szerző |
| `parent` | integer | nem | Szülő ID |
| `menu_order` | integer | nem | Menü sorrend |
| `date` | string | nem | Új dátum |
| `categories` | array | nem | Kategória ID-k |
| `tags` | array | nem | Tag nevek |
| `featured_image` | integer | nem | Kiemelt kép ID |
| `meta` | object | nem | Meta mezők |

### Output

```json
{
  "success": true,
  "message": "Post updated successfully",
  "post": {
    "id": 33,
    "title": "Frissített cím",
    "slug": "teszt-post",
    "status": "publish",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33,"title":"Frissített cím","excerpt":"Új kivonat"}}'
```

---

## delete-post

Bejegyzés törlése (kukába vagy véglegesen).

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Post ID |
| `force` | boolean | nem | `false` | Végleges törlés (kuka kihagyása) |

### Output

```json
{
  "success": true,
  "message": "Post moved to trash",
  "deleted_id": 33,
  "force_delete": false
}
```

### Példa

```bash
# Kukába helyezés
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run?input%5Bid%5D=33'

# Végleges törlés
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run?input%5Bid%5D=33&input%5Bforce%5D=true'
```

---

## restore-post

Bejegyzés visszaállítása kukából.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/restore-post/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Post ID |

### Output

```json
{
  "success": true,
  "message": "Post restored from trash",
  "post": {
    "id": 33,
    "title": "Visszaállított post",
    "slug": "teszt-post",
    "status": "draft",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/restore-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33}}'
```

---

## duplicate-post

Bejegyzés másolása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-post/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Másolandó post ID |
| `new_title` | string | nem | `"Eredeti (Copy)"` | Új cím |
| `status` | string | nem | `draft` | Másolat állapota |
| `copy_meta` | boolean | nem | `true` | Meta mezők másolása |

### Output

```json
{
  "success": true,
  "message": "Post duplicated successfully",
  "original_id": 33,
  "post": {
    "id": 35,
    "title": "Post másolat",
    "slug": "",
    "status": "draft",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33,"new_title":"Másolat"}}'
```

---

## bulk-posts

Tömeges művelet több bejegyzésen.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/bulk-posts/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `ids` | array | **igen** | Post ID-k tömbje |
| `action` | string | **igen** | Művelet: `publish`, `draft`, `trash`, `delete`, `restore` |

### Output

```json
{
  "success": true,
  "action": "delete",
  "processed": 2,
  "failed": 0,
  "total": 2,
  "success_ids": [33, 35],
  "failed_ids": [],
  "message": "2 items processed successfully"
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/bulk-posts/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"ids":[33,35],"action":"trash"}}'
```

---

## get-post-types

Elérhető post típusok listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-post-types/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `public` | boolean | nem | Csak publikus típusok |

### Output

```json
{
  "post_types": [
    {
      "name": "post",
      "label": "Posts",
      "singular": "Post",
      "public": true,
      "hierarchical": false,
      "has_archive": false,
      "supports": {
        "title": true,
        "editor": true,
        "author": true,
        "thumbnail": true,
        "excerpt": true
      }
    },
    {
      "name": "page",
      "label": "Pages",
      "singular": "Page",
      "public": true,
      "hierarchical": true
    }
  ],
  "total": 17
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-types/run?input%5Bpublic%5D=true'
```
