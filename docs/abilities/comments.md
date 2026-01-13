# Comments Abilities

## list-comments

Hozzászólások listázása szűrési lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-comments/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `status` | string | nem | `all` | Állapot: `all`, `approve`, `hold`, `spam`, `trash` |
| `post_id` | integer | nem | - | Szűrés post ID alapján |
| `author_email` | string | nem | - | Szűrés szerző email alapján |
| `search` | string | nem | - | Keresés tartalomban |
| `orderby` | string | nem | `date` | Rendezés |
| `order` | string | nem | `DESC` | Irány: `ASC`, `DESC` |

### Output

```json
{
  "comments": [
    {
      "id": 18,
      "post_id": 32,
      "author": "kata_junior",
      "author_email": "kata@example.com",
      "content": "Köszönöm mindenkinek a válaszokat!",
      "date": "2026-01-13 06:20:14",
      "status": "approved",
      "parent": 0,
      "type": "comment"
    }
  ],
  "total": 14,
  "total_pages": 5,
  "limit": 3,
  "offset": 0,
  "has_more": true
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-comments/run?input%5Bpost_id%5D=32&input%5Blimit%5D=10'
```

---

## get-comment

Egyetlen hozzászólás részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-comment/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Comment ID |

### Output

```json
{
  "success": true,
  "comment": {
    "id": 19,
    "post_id": 32,
    "author": "Test User",
    "author_email": "test@test.com",
    "content": "Ez egy teszt komment",
    "date": "2026-01-13 06:43:40",
    "status": "approved",
    "parent": 0,
    "type": "comment",
    "author_url": "",
    "author_ip": "",
    "user_id": 0,
    "agent": "",
    "date_gmt": "2026-01-13 06:43:40",
    "post_title": "A Git legfontosabb parancsai",
    "avatar": "https://gravatar.com/avatar/...",
    "replies_count": 0
  }
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-comment/run?input%5Bid%5D=19'
```

---

## create-comment

Új hozzászólás létrehozása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-comment/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `post_id` | integer | **igen** | - | Post ID |
| `content` | string | **igen** | - | Hozzászólás tartalma |
| `author_name` | string | nem | - | Szerző neve |
| `author_email` | string | nem | - | Szerző email |
| `author_url` | string | nem | - | Szerző weboldala |
| `parent` | integer | nem | `0` | Szülő comment ID (válaszhoz) |
| `status` | string | nem | `1` | Állapot: `1` (approved), `0` (pending), `spam` |
| `user_id` | integer | nem | - | WordPress user ID |

### Output

```json
{
  "success": true,
  "message": "Comment created successfully",
  "comment": {
    "id": 19,
    "post_id": 32,
    "author": "Test User",
    "author_email": "test@test.com",
    "content": "Ez egy teszt komment",
    "date": "2026-01-13 06:43:40",
    "status": "approved",
    "parent": 0,
    "type": "comment"
  },
  "id": 19
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"post_id":32,"author_name":"Test User","author_email":"test@test.com","content":"Teszt komment"}}'
```

---

## update-comment

Hozzászólás módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-comment/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Comment ID |
| `content` | string | nem | Új tartalom |
| `author_name` | string | nem | Új szerző név |
| `author_email` | string | nem | Új email |
| `author_url` | string | nem | Új URL |
| `status` | string | nem | Új állapot |

### Output

```json
{
  "success": true,
  "message": "Comment updated successfully",
  "comment": {
    "id": 19,
    "post_id": 32,
    "author": "Test User",
    "content": "Ez egy frissített teszt komment",
    "status": "approved",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19,"content":"Frissített tartalom"}}'
```

---

## delete-comment

Hozzászólás törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Comment ID |
| `force` | boolean | nem | `false` | Végleges törlés (kuka kihagyása) |

### Output

```json
{
  "success": true,
  "message": "Comment permanently deleted",
  "deleted_id": 19,
  "force_delete": true
}
```

### Példa

```bash
# Kukába helyezés
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run?input%5Bid%5D=19'

# Végleges törlés
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run?input%5Bid%5D=19&input%5Bforce%5D=true'
```

---

## approve-comment

Hozzászólás jóváhagyása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/approve-comment/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Comment ID |

### Output

```json
{
  "success": true,
  "message": "Comment approved",
  "comment": {
    "id": 19,
    "status": "approved",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/approve-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19}}'
```

---

## spam-comment

Hozzászólás spamnek jelölése.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/spam-comment/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Comment ID |

### Output

```json
{
  "success": true,
  "message": "Comment marked as spam",
  "id": 19
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/spam-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19}}'
```

---

## bulk-comments

Tömeges művelet hozzászólásokon.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/bulk-comments/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `ids` | array | **igen** | Comment ID-k tömbje |
| `action` | string | **igen** | Művelet: `approve`, `unapprove`, `spam`, `trash`, `delete` |

### Output

```json
{
  "success": true,
  "action": "trash",
  "processed": 3,
  "failed": 0,
  "total": 3,
  "success_ids": [19, 20, 21],
  "failed_ids": [],
  "message": "3 items processed successfully"
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/bulk-comments/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"ids":[19,20,21],"action":"approve"}}'
```

---

## comment-counts

Hozzászólás statisztikák lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `post_id` | integer | nem | Szűrés adott post-ra |

### Output

```json
{
  "total": 15,
  "approved": 13,
  "awaiting": 2,
  "spam": 0,
  "trash": 0,
  "post_trashed": 0
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run'

# Egy adott post-ra
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run?input%5Bpost_id%5D=32'
```
