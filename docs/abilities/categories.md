# Categories (Kategóriák)

A kategóriák kezelésére szolgáló ability-k.

## Abilities

| Ability | Leírás | Metódus |
|---------|--------|---------|
| list-categories | Kategóriák listázása | GET |
| get-category | Kategória részletei | GET |
| create-category | Kategória létrehozása | POST |
| update-category | Kategória frissítése | POST |
| delete-category | Kategória törlése | DELETE |

---

## list-categories

Kategóriák listázása szűrési és rendezési lehetőségekkel.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-categories/run`

### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| limit | integer | 20 | Visszaadott elemek száma |
| offset | integer | 0 | Kihagyandó elemek száma |
| hide_empty | boolean | false | Üres kategóriák elrejtése |
| search | string | - | Keresési kifejezés |
| parent | integer | - | Szűrés szülő kategória ID alapján |
| orderby | string | "name" | Rendezési mező (name, slug, term_id, count) |
| order | string | "ASC" | Rendezési irány (ASC, DESC) |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| terms | array | Kategóriák listája |
| terms[].id | integer | Kategória ID |
| terms[].name | string | Kategória neve |
| terms[].slug | string | Kategória slug |
| terms[].taxonomy | string | Taxonómia típus ("category") |
| terms[].count | integer | Bejegyzések száma |
| total | integer | Összes kategória száma |
| total_pages | integer | Összes oldalak száma |
| limit | integer | Visszaadott limit |
| offset | integer | Alkalmazott offset |

### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-categories/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "terms": [
    {
      "id": 8,
      "name": "Fejlesztés",
      "slug": "fejlesztes",
      "taxonomy": "category",
      "count": 0
    },
    {
      "id": 4,
      "name": "Gasztronómia",
      "slug": "gasztronomia",
      "taxonomy": "category",
      "count": 1
    },
    {
      "id": 1,
      "name": "Uncategorized",
      "slug": "uncategorized",
      "taxonomy": "category",
      "count": 5
    }
  ],
  "total": 3,
  "total_pages": 1,
  "limit": 20,
  "offset": 0
}
```

---

## get-category

Egy kategória részletes adatainak lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-category/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Kategória ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Kategória adatai |
| term.id | integer | Kategória ID |
| term.name | string | Kategória neve |
| term.slug | string | Kategória slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Kategória leírása |
| term.parent | integer | Szülő kategória ID (0 ha nincs) |
| term.link | string | Kategória archív URL |

### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-category/run?input%5Bid%5D=4" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 4,
    "name": "Gasztronómia",
    "slug": "gasztronomia",
    "taxonomy": "category",
    "count": 1,
    "description": "Ételek, italok, receptek",
    "parent": 0,
    "link": "https://example.com/category/gasztronomia/"
  }
}
```

---

## create-category

Új kategória létrehozása.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-category/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| name | string | igen | Kategória neve |
| slug | string | nem | Kategória slug (automatikusan generálódik ha nincs megadva) |
| description | string | nem | Kategória leírása |
| parent | integer | nem | Szülő kategória ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Létrehozott kategória adatai |
| term.id | integer | Kategória ID |
| term.name | string | Kategória neve |
| term.slug | string | Kategória slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Kategória leírása |
| term.parent | integer | Szülő kategória ID |
| term.link | string | Kategória archív URL |

### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-category/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"name":"Teszt Kategória","slug":"teszt-kategoria","description":"Ez egy teszt kategória","parent":0}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 14,
    "name": "Teszt Kategória",
    "slug": "teszt-kategoria",
    "taxonomy": "category",
    "count": 0,
    "description": "Ez egy teszt kategória",
    "parent": 0,
    "link": "https://example.com/category/teszt-kategoria/"
  }
}
```

---

## update-category

Létező kategória frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-category/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Kategória ID |
| name | string | nem | Új név |
| slug | string | nem | Új slug |
| description | string | nem | Új leírás |
| parent | integer | nem | Új szülő kategória ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term | object | Frissített kategória adatai |
| term.id | integer | Kategória ID |
| term.name | string | Kategória neve |
| term.slug | string | Kategória slug |
| term.taxonomy | string | Taxonómia típus |
| term.count | integer | Bejegyzések száma |
| term.description | string | Kategória leírása |
| term.parent | integer | Szülő kategória ID |
| term.link | string | Kategória archív URL |

### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-category/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":14,"name":"Teszt Kategória Frissítve","description":"Frissített leírás"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 14,
    "name": "Teszt Kategória Frissítve",
    "slug": "teszt-kategoria",
    "taxonomy": "category",
    "count": 0,
    "description": "Frissített leírás",
    "parent": 0,
    "link": "https://example.com/category/teszt-kategoria/"
  }
}
```

---

## delete-category

Kategória törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-category/run`

### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| id | integer | igen | Törlendő kategória ID |

### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| id | integer | Törölt kategória ID |

### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-category/run?input%5Bid%5D=14" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Term deleted successfully",
  "id": 14
}
```

### Megjegyzések

- Az alapértelmezett kategória (Uncategorized) nem törölhető
- A törölt kategóriához tartozó bejegyzések az alapértelmezett kategóriába kerülnek
