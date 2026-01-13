# Settings (Beállítások)

WordPress beállítások kezelésére szolgáló ability-k.

## Abilities

### General Settings
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-general-settings | Általános beállítások lekérése | GET |
| update-general-settings | Általános beállítások frissítése | POST |

### Reading Settings
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-reading-settings | Olvasási beállítások lekérése | GET |
| update-reading-settings | Olvasási beállítások frissítése | POST |

### Discussion Settings
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-discussion-settings | Hozzászólás beállítások lekérése | GET |
| update-discussion-settings | Hozzászólás beállítások frissítése | POST |

### Permalink Settings
| Ability | Leírás | Metódus |
|---------|--------|---------|
| get-permalink-settings | Közvetlen hivatkozás beállítások lekérése | GET |
| update-permalink-settings | Közvetlen hivatkozás beállítások frissítése | POST |

---

## General Settings

### get-general-settings

Általános WordPress beállítások lekérése (oldal címe, szlogen, email, stb.).

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-general-settings/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| settings | object | Beállítások |
| settings.blogname | string | Oldal címe |
| settings.blogdescription | string | Szlogen |
| settings.siteurl | string | WordPress cím (URL) |
| settings.home | string | Oldal címe (URL) |
| settings.admin_email | string | Adminisztrátor email |
| settings.users_can_register | string | Regisztráció engedélyezése (0/1) |
| settings.default_role | string | Új felhasználók alapértelmezett szerepe |
| settings.timezone_string | string | Időzóna (pl. Europe/Budapest) |
| settings.date_format | string | Dátum formátum |
| settings.time_format | string | Idő formátum |
| settings.start_of_week | string | Hét kezdő napja (0=vasárnap, 1=hétfő) |
| settings.WPLANG | string | Nyelv kód |
| settings.site_language | string | Aktuális nyelv |
| settings.available_roles | array | Elérhető szerepek listája |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-general-settings/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "settings": {
    "blogname": "Stanton Test Site",
    "blogdescription": "Teszt leírás",
    "siteurl": "https://example.com",
    "home": "https://example.com",
    "admin_email": "admin@example.com",
    "users_can_register": "0",
    "default_role": "subscriber",
    "timezone_string": "Europe/Budapest",
    "date_format": "F j, Y",
    "time_format": "g:i a",
    "start_of_week": "1",
    "WPLANG": "",
    "site_language": "en_US",
    "available_roles": [
      "administrator",
      "editor",
      "author",
      "contributor",
      "subscriber"
    ]
  }
}
```

---

### update-general-settings

Általános WordPress beállítások frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-general-settings/run`

#### Input Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| blogname | string | Oldal címe |
| blogdescription | string | Szlogen |
| admin_email | string | Adminisztrátor email |
| users_can_register | boolean | Regisztráció engedélyezése |
| default_role | string | Új felhasználók alapértelmezett szerepe |
| timezone_string | string | Időzóna (pl. Europe/Budapest) |
| date_format | string | Dátum formátum (pl. Y-m-d) |
| time_format | string | Idő formátum (pl. H:i) |
| start_of_week | integer | Hét kezdő napja (0-6) |
| WPLANG | string | Nyelv kód |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| updated | array | Frissített beállítások listája |
| failed | array | Sikertelen frissítések |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-general-settings/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"blogname":"Új oldal cím","blogdescription":"Új szlogen","timezone_string":"Europe/Budapest"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 3 setting(s)",
  "updated": [
    "blogname",
    "blogdescription",
    "timezone_string"
  ],
  "failed": []
}
```

---

## Reading Settings

### get-reading-settings

Olvasási beállítások lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-reading-settings/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| settings | object | Beállítások |
| settings.posts_per_page | string | Bejegyzések száma oldalanként |
| settings.posts_per_rss | string | RSS elemek száma |
| settings.rss_use_excerpt | string | RSS kivonat használata (0/1) |
| settings.show_on_front | string | Kezdőlap megjelenítése (posts/page) |
| settings.page_on_front | string | Kezdőlap ID |
| settings.page_for_posts | string | Bejegyzések oldal ID |
| settings.blog_public | string | Keresőmotor indexelés (0/1) |
| settings.homepage_title | string | Kezdőlap címe (ha van) |
| settings.posts_page_title | string | Bejegyzések oldal címe (ha van) |

#### Példa

**Response:**
```json
{
  "success": true,
  "settings": {
    "posts_per_page": "10",
    "posts_per_rss": "10",
    "rss_use_excerpt": "0",
    "show_on_front": "posts",
    "page_on_front": "0",
    "page_for_posts": "0",
    "blog_public": "1"
  }
}
```

---

### update-reading-settings

Olvasási beállítások frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-reading-settings/run`

#### Input Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| posts_per_page | integer | Bejegyzések száma oldalanként (1-100) |
| posts_per_rss | integer | RSS elemek száma (1-100) |
| rss_use_excerpt | boolean | RSS kivonat használata |
| show_on_front | string | Kezdőlap megjelenítése ("posts" vagy "page") |
| page_on_front | integer | Kezdőlap ID |
| page_for_posts | integer | Bejegyzések oldal ID |
| blog_public | boolean | Keresőmotor indexelés tiltása |

---

## Discussion Settings

### get-discussion-settings

Hozzászólás beállítások lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-discussion-settings/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| settings | object | Beállítások |
| settings.default_pingback_flag | string | Pingback engedélyezése |
| settings.default_ping_status | string | Ping állapot (open/closed) |
| settings.default_comment_status | string | Hozzászólás állapot (open/closed) |
| settings.require_name_email | string | Név és email kötelező |
| settings.comment_registration | string | Regisztráció szükséges hozzászóláshoz |
| settings.close_comments_for_old_posts | string | Régi bejegyzéseknél bezár |
| settings.close_comments_days_old | string | Napok száma bezárás előtt |
| settings.thread_comments | string | Szálazott hozzászólások |
| settings.thread_comments_depth | string | Szálazás mélysége |
| settings.page_comments | string | Oldalakra bontás |
| settings.comments_per_page | string | Hozzászólások oldalanként |
| settings.default_comments_page | string | Alapértelmezett oldal (newest/oldest) |
| settings.comment_order | string | Sorrend (asc/desc) |
| settings.comments_notify | string | Email értesítés |
| settings.moderation_notify | string | Moderálás értesítés |
| settings.comment_moderation | string | Kézi jóváhagyás |
| settings.comment_previously_approved | string | Korábban jóváhagyott szerző |
| settings.moderation_keys | string | Moderálási kulcsszavak |
| settings.disallowed_keys | string | Tiltott kulcsszavak |
| settings.show_avatars | string | Avatarok megjelenítése |
| settings.avatar_rating | string | Avatar besorolás (G/PG/R/X) |
| settings.avatar_default | string | Alapértelmezett avatar |

#### Példa

**Response:**
```json
{
  "success": true,
  "settings": {
    "default_pingback_flag": "1",
    "default_ping_status": "open",
    "default_comment_status": "open",
    "require_name_email": "1",
    "comment_registration": "0",
    "close_comments_for_old_posts": "0",
    "close_comments_days_old": "14",
    "thread_comments": "1",
    "thread_comments_depth": "5",
    "page_comments": "0",
    "comments_per_page": "50",
    "default_comments_page": "newest",
    "comment_order": "asc",
    "comments_notify": "1",
    "moderation_notify": "1",
    "comment_moderation": "0",
    "comment_previously_approved": "1",
    "moderation_keys": "",
    "disallowed_keys": "",
    "show_avatars": "1",
    "avatar_rating": "G",
    "avatar_default": "mystery"
  }
}
```

---

### update-discussion-settings

Hozzászólás beállítások frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-discussion-settings/run`

#### Input Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| default_pingback_flag | boolean | Pingback engedélyezése |
| default_ping_status | string | Ping állapot ("open" / "closed") |
| default_comment_status | string | Hozzászólás állapot ("open" / "closed") |
| require_name_email | boolean | Név és email kötelező |
| comment_registration | boolean | Regisztráció szükséges |
| close_comments_for_old_posts | boolean | Régi bejegyzéseknél bezár |
| close_comments_days_old | integer | Napok száma |
| thread_comments | boolean | Szálazott hozzászólások |
| thread_comments_depth | integer | Szálazás mélysége (1-10) |
| page_comments | boolean | Oldalakra bontás |
| comments_per_page | integer | Hozzászólások oldalanként |
| default_comments_page | string | Alapértelmezett oldal ("newest" / "oldest") |
| comment_order | string | Sorrend ("asc" / "desc") |
| comments_notify | boolean | Email értesítés |
| moderation_notify | boolean | Moderálás értesítés |
| comment_moderation | boolean | Kézi jóváhagyás |
| comment_previously_approved | boolean | Korábban jóváhagyott szerző |
| moderation_keys | string | Moderálási kulcsszavak (soronként egy) |
| disallowed_keys | string | Tiltott kulcsszavak (soronként egy) |
| show_avatars | boolean | Avatarok megjelenítése |
| avatar_rating | string | Avatar besorolás ("G" / "PG" / "R" / "X") |
| avatar_default | string | Alapértelmezett avatar |

---

## Permalink Settings

### get-permalink-settings

Közvetlen hivatkozás beállítások lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-permalink-settings/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| settings | object | Beállítások |
| settings.permalink_structure | string | Permalink struktúra |
| settings.category_base | string | Kategória alap |
| settings.tag_base | string | Címke alap |
| settings.common_structures | object | Gyakori struktúrák |

#### Példa

**Response:**
```json
{
  "success": true,
  "settings": {
    "permalink_structure": "/%postname%/",
    "category_base": "",
    "tag_base": "",
    "common_structures": {
      "plain": "",
      "day_name": "/%year%/%monthnum%/%day%/%postname%/",
      "month_name": "/%year%/%monthnum%/%postname%/",
      "numeric": "/archives/%post_id%",
      "post_name": "/%postname%/"
    }
  }
}
```

---

### update-permalink-settings

Közvetlen hivatkozás beállítások frissítése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-permalink-settings/run`

#### Input Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| permalink_structure | string | Permalink struktúra (pl. /%postname%/) |
| category_base | string | Kategória alap |
| tag_base | string | Címke alap |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-permalink-settings/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"permalink_structure":"/%postname%/"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 1 setting(s)",
  "updated": [
    "permalink_structure"
  ],
  "failed": []
}
```

---

## Megjegyzések

- A `siteurl` és `home` mezők biztonsági okokból nem módosíthatók az API-n keresztül
- A permalink struktúra módosítása automatikusan újragenerálja a rewrite szabályokat
- Az `admin_email` mező validálva van - csak érvényes email cím fogadható el
- A `default_role` csak létező szerepre állítható
