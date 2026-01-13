# Tags (Címkék)

A címkék kezelésére szolgáló ability-k.

## Abilities

| Ability | Leírás | Metódus |
|---------|--------|---------|
| list-tags | Címkék listázása | GET |
| get-tag | Címke részletei | GET |
| create-tag | Címke létrehozása | POST |
| update-tag | Címke frissítése | POST |
| delete-tag | Címke törlése | DELETE |

---

## list-tags

Címkék listázása szűrési és rendezési lehetőségekkel.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-tags/run`

### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| limit | integer | 20 | Visszaadott elemek száma |
| offset | integer | 0 | Kihagyandó elemek száma |
| hide_empty | boolean | false | Üres címkék elrejtése |
| search | string | - | Keresési kifejezés |
| orderby | string | "name" | Rendezési mező (name, slug, term_id, count) |
| order | string | "ASC" | Rendezési irány (ASC, DESC) |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| terms | array | Címkék listája |
| terms[].id | integer | Címke ID |
| terms[].name | string | Címke neve |
| terms[].slug | string | Címke slug |
| terms[].taxonomy | string | Taxonómia típus ("post_tag") |
| terms[].count | integer | Bejegyzések száma |
| total | integer | Összes címke száma |
| total_pages | integer | Összes oldalak száma |
| limit | integer | Visszaadott limit |
| offset | integer | Alkalmazott offset |

### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-tags/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "terms": [
    {
      "id": 9,
      "name": "git",
      "slug": "git",
      "taxonomy": "post_tag",
      "count": 0
    },
    {
      "id": 5,
      "name": "kávé",
      "slug": "kave",
      "taxonomy": "post_tag",
      "count": 1
    },
    {
      "id": 7,
      "name": "kultúra",
      "slug": "kultura",
      "taxonomy": "post_tag",
      "count": 1
    }
  ],
  "total": 7,
  "total_pages": 1,
  "limit": 20,
  "offset": 0
}
```

---

## get-tag

Egy címke részletes adatainak lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-tag/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Címke ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Címke adatai |
| term.id | integer | Címke ID |
| term.name | string | Címke neve |
| term.slug | string | Címke slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Címke leírása |
| term.parent | integer | Mindig 0 (címkék nem hierarchikusak) |
| term.link | string | Címke archív URL |

### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-tag/run?input%5Bid%5D=5" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 5,
    "name": "kávé",
    "slug": "kave",
    "taxonomy": "post_tag",
    "count": 1,
    "description": "",
    "parent": 0,
    "link": "https://example.com/tag/kave/"
  }
}
```

---

## create-tag

Új címke létrehozása.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-tag/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| name | string | igen | Címke neve |
| slug | string | nem | Címke slug (automatikusan generálódik ha nincs megadva) |
| description | string | nem | Címke leírása |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Létrehozott címke adatai |
| term.id | integer | Címke ID |
| term.name | string | Címke neve |
| term.slug | string | Címke slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Címke leírása |
| term.parent | integer | Mindig 0 |
| term.link | string | Címke archív URL |

### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-tag/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"name":"Teszt Címke","slug":"teszt-cimke","description":"Ez egy teszt címke"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 15,
    "name": "Teszt Címke",
    "slug": "teszt-cimke",
    "taxonomy": "post_tag",
    "count": 0,
    "description": "Ez egy teszt címke",
    "parent": 0,
    "link": "https://example.com/tag/teszt-cimke/"
  }
}
```

---

## update-tag

Létező címke frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-tag/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Címke ID |
| name | string | nem | Új név |
| slug | string | nem | Új slug |
| description | string | nem | Új leírás |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Frissített címke adatai |
| term.id | integer | Címke ID |
| term.name | string | Címke neve |
| term.slug | string | Címke slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Címke leírása |
| term.parent | integer | Mindig 0 |
| term.link | string | Címke archív URL |

### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-tag/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":15,"name":"Teszt Címke Frissítve","description":"Frissített leírás"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 15,
    "name": "Teszt Címke Frissítve",
    "slug": "teszt-cimke",
    "taxonomy": "post_tag",
    "count": 0,
    "description": "Frissített leírás",
    "parent": 0,
    "link": "https://example.com/tag/teszt-cimke/"
  }
}
```

---

## delete-tag

Címke törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-tag/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Törlendő címke ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| id | integer | Törölt címke ID |

### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-tag/run?input%5Bid%5D=15" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Term deleted successfully",
  "id": 15
}
```

### Megjegyzések

- A címkék nem hierarchikusak, ezért nincs parent mező az input-ban
- A törölt címke automatikusan eltávolításra kerül a hozzá kapcsolt bejegyzésekről
