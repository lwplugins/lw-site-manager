# Meta (Metaadatok)

Post, user és term metaadatok kezelésére szolgáló ability-k.

## Abilities

### Post Meta
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-post-meta | Post/oldal metaadatok lekérése | GET |
| set-post-meta | Post/oldal metaadat beállítása | POST |
| delete-post-meta | Post/oldal metaadat törlése | DELETE |

### User Meta
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-user-meta | Felhasználó metaadatok lekérése | GET |
| set-user-meta | Felhasználó metaadat beállítása | POST |
| delete-user-meta | Felhasználó metaadat törlése | DELETE |

### Term Meta
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-term-meta | Kategória/címke metaadatok lekérése | GET |
| set-term-meta | Kategória/címke metaadat beállítása | POST |
| delete-term-meta | Kategória/címke metaadat törlése | DELETE |

---

## Post Meta

### get-post-meta

Post vagy oldal metaadatainak lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-post-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| post_id | integer | igen | - | Post vagy oldal ID |
| key | string | nem | - | Specifikus meta kulcs (opcionális, ha nincs megadva, összes meta) |
| include_private | boolean | nem | false | Privát meta kulcsok (_-al kezdődők) belefoglalása |

#### Output Schema (összes meta)

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| post_id | integer | Post ID |
| meta | object | Meta kulcs-érték párok |

#### Output Schema (specifikus kulcs)

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| post_id | integer | Post ID |
| key | string | Meta kulcs |
| value | mixed | Meta érték |

#### Példa - Összes meta

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-meta/run?input%5Bpost_id%5D=1" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "post_id": 1,
  "meta": []
}
```

#### Példa - Specifikus kulcs

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-meta/run?input%5Bpost_id%5D=1&input%5Bkey%5D=teszt_meta_kulcs" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "post_id": 1,
  "key": "teszt_meta_kulcs",
  "value": "teszt érték"
}
```

---

### set-post-meta

Post vagy oldal metaadatának beállítása.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-post-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| post_id | integer | igen | Post vagy oldal ID |
| key | string | igen | Meta kulcs |
| value | mixed | igen | Meta érték (string, number, boolean, array, object) |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| post_id | integer | Post ID |
| key | string | Meta kulcs |
| value | mixed | Beállított érték |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-post-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"post_id":1,"key":"teszt_meta_kulcs","value":"teszt érték"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "post_id": 1,
  "key": "teszt_meta_kulcs",
  "value": "teszt érték"
}
```

---

### delete-post-meta

Post vagy oldal metaadatának törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-post-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| post_id | integer | igen | Post vagy oldal ID |
| key | string | igen | Törlendő meta kulcs |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| post_id | integer | Post ID |
| key | string | Törölt meta kulcs |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post-meta/run?input%5Bpost_id%5D=1&input%5Bkey%5D=teszt_meta_kulcs" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "post_id": 1,
  "key": "teszt_meta_kulcs"
}
```

---

## User Meta

### get-user-meta

Felhasználó metaadatainak lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-user-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| user_id | integer | igen | - | Felhasználó ID |
| key | string | nem | - | Specifikus meta kulcs (opcionális) |
| include_private | boolean | nem | false | Privát meta kulcsok belefoglalása |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| user_id | integer | Felhasználó ID |
| meta | object | Meta kulcs-érték párok |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-user-meta/run?input%5Buser_id%5D=1" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "user_id": 1,
  "meta": {
    "nickname": "admin",
    "first_name": "",
    "last_name": "",
    "description": "",
    "rich_editing": "true",
    "admin_color": "fresh",
    "wp_capabilities": "a:1:{s:13:\"administrator\";b:1;}",
    "wp_user_level": "10"
  }
}
```

---

### set-user-meta

Felhasználó metaadatának beállítása.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-user-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| user_id | integer | igen | Felhasználó ID |
| key | string | igen | Meta kulcs |
| value | mixed | igen | Meta érték |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| user_id | integer | Felhasználó ID |
| key | string | Meta kulcs |
| value | mixed | Beállított érték |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-user-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"user_id":1,"key":"teszt_user_meta","value":"user meta érték"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "user_id": 1,
  "key": "teszt_user_meta",
  "value": "user meta érték"
}
```

---

### delete-user-meta

Felhasználó metaadatának törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-user-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| user_id | integer | igen | Felhasználó ID |
| key | string | igen | Törlendő meta kulcs |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| user_id | integer | Felhasználó ID |
| key | string | Törölt meta kulcs |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-user-meta/run?input%5Buser_id%5D=1&input%5Bkey%5D=teszt_user_meta" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "user_id": 1,
  "key": "teszt_user_meta"
}
```

---

## Term Meta

### get-term-meta

Kategória vagy címke metaadatainak lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-term-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| term_id | integer | igen | Term (kategória/címke) ID |
| key | string | nem | Specifikus meta kulcs (opcionális) |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| term_id | integer | Term ID |
| meta | object | Meta kulcs-érték párok |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-term-meta/run?input%5Bterm_id%5D=1" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "term_id": 1,
  "meta": []
}
```

---

### set-term-meta

Kategória vagy címke metaadatának beállítása.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-term-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| term_id | integer | igen | Term ID |
| key | string | igen | Meta kulcs |
| value | mixed | igen | Meta érték |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| term_id | integer | Term ID |
| key | string | Meta kulcs |
| value | mixed | Beállított érték |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-term-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"term_id":1,"key":"teszt_term_meta","value":"term meta érték"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "term_id": 1,
  "key": "teszt_term_meta",
  "value": "term meta érték"
}
```

---

### delete-term-meta

Kategória vagy címke metaadatának törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-term-meta/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| term_id | integer | igen | Term ID |
| key | string | igen | Törlendő meta kulcs |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| term_id | integer | Term ID |
| key | string | Törölt meta kulcs |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-term-meta/run?input%5Bterm_id%5D=1&input%5Bkey%5D=teszt_term_meta" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "term_id": 1,
  "key": "teszt_term_meta"
}
```

---

## Megjegyzések

- A privát meta kulcsok (amelyek `_` karakterrel kezdődnek) alapértelmezetten nem jelennek meg a lekérdezésekben
- A `include_private: true` paraméterrel a privát meta kulcsok is lekérhetők (post és user meta esetén)
- A meta értékek lehetnek egyszerű típusok (string, number, boolean) vagy összetett típusok (array, object)
- A WordPress automatikusan szerializálja/deszerializálja az összetett értékeket
