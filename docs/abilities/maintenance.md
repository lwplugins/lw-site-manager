# Maintenance (Karbantartás)

Backup, health check, database és cache kezelésére szolgáló ability-k.

## Abilities

### Backup
| Ability | Leírás | Metódus |
|---------|--------|---------|
| create-backup | Backup indítása | POST |
| backup-status | Backup állapot lekérése | GET |
| cancel-backup | Backup megszakítása | POST |
| list-backups | Backupok listázása | GET |
| restore-backup | Backup visszaállítása | DELETE |
| delete-backup | Backup törlése | DELETE |

### Health & Diagnostics
| Ability | Leírás | Metódus |
|---------|--------|---------|
| health-check | Átfogó oldal állapotfelmérés | GET |
| error-log | PHP hibanapló lekérése | GET |

### Database
| Ability | Leírás | Metódus |
|---------|--------|---------|
| optimize-database | Adatbázis táblák optimalizálása | DELETE |
| cleanup-database | Revíziók, transientek, spam törlése | POST |

### Cache
| Ability | Leírás | Metódus |
|---------|--------|---------|
| flush-cache | Cache-ek ürítése | POST |

### Plugin Database Updates
| Ability | Leírás | Metódus |
|---------|--------|---------|
| check-plugin-db-updates | WooCommerce, Elementor stb. DB frissítések ellenőrzése | GET |
| update-plugin-db | Adott plugin DB frissítése | DELETE |
| update-all-plugin-dbs | Összes plugin DB frissítése | DELETE |
| get-supported-db-plugins | Támogatott pluginek listázása | GET |

---

## Backup Abilities

### create-backup

Backup job indítása. Azonnal visszatér a job ID-val, a backup a háttérben fut.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-backup/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| include_database | boolean | true | Adatbázis hozzáadása a backuphoz |
| include_files | boolean | true | WordPress fájlok hozzáadása |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Indítás sikeressége |
| message | string | Státusz üzenet |
| backup_id | string | Backup job azonosító |
| status | string | Kezdeti állapot ("pending") |
| total_files | integer | Összes fájlok száma |
| total_size | integer | Összes méret byte-ban |
| total_size_human | string | Olvasható méret |
| chunks_total | integer | Feldolgozási chunk-ok száma |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-backup/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"include_database":true,"include_files":true}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Backup job started",
  "backup_id": "2026-01-13_07-04-13_8fMZyHG8",
  "status": "pending",
  "total_files": 6270,
  "total_size": 123396018,
  "total_size_human": "118 MB",
  "chunks_total": 13
}
```

---

### backup-status

Backup job állapotának és haladásának lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/backup-status/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| backup_id | string | igen | Backup job azonosító |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| backup_id | string | Backup azonosító |
| status | string | Állapot (pending, processing, completed, failed, cancelled) |
| progress | number | Haladás százalékban (0-100) |
| total_files | integer | Összes fájlok száma |
| processed_files | integer | Feldolgozott fájlok száma |
| current_chunk | integer | Aktuális chunk |
| chunks_total | integer | Összes chunk |
| created_at | string | Létrehozás időpontja |
| started_at | string | Indítás időpontja |
| completed_at | string/null | Befejezés időpontja |
| file_path | string | Backup fájl elérési útja (ha kész) |
| file_size | integer | Backup méret byte-ban (ha kész) |
| file_size_human | string | Olvasható méret (ha kész) |
| errors | array | Hibák listája |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/backup-status/run?input%5Bbackup_id%5D=2026-01-13_07-04-13_8fMZyHG8" \
  -u "user:application_password"
```

**Response (folyamatban):**
```json
{
  "backup_id": "2026-01-13_07-04-13_8fMZyHG8",
  "status": "processing",
  "progress": 63.8,
  "total_files": 6270,
  "processed_files": 4000,
  "current_chunk": 8,
  "chunks_total": 13,
  "created_at": "2026-01-13 07:04:13",
  "started_at": "2026-01-13 07:04:14",
  "completed_at": null,
  "errors": []
}
```

**Response (kész):**
```json
{
  "backup_id": "2026-01-13_07-04-13_8fMZyHG8",
  "status": "completed",
  "progress": 100,
  "total_files": 6270,
  "processed_files": 6270,
  "current_chunk": 13,
  "chunks_total": 13,
  "created_at": "2026-01-13 07:04:13",
  "started_at": "2026-01-13 07:04:14",
  "completed_at": "2026-01-13 07:07:26",
  "errors": [],
  "file_path": "/home/user/webapps/app/wp-content/uploads/wpsm-backups/2026-01-13_07-04-13_8fMZyHG8.zip",
  "file_size": 45246034,
  "file_size_human": "43 MB"
}
```

---

### list-backups

Elérhető backupok listázása.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-backups/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| limit | integer | 20 | Visszaadott elemek száma |
| offset | integer | 0 | Kihagyandó elemek száma |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| backups | array | Backupok listája |
| backups[].file_path | string | Backup fájl elérési útja |
| backups[].file_size | integer | Méret byte-ban |
| backups[].file_size_human | string | Olvasható méret |
| backups[].file_exists | boolean | Fájl létezik-e |
| backups[].manifest | object | Backup metaadatai |
| total | integer | Összes backup száma |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-backups/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "backups": [
    {
      "file_path": "/home/user/webapps/app/wp-content/uploads/wpsm-backups/2026-01-12_23-25-02_ltis2Uee.zip",
      "file_size": 44320767,
      "manifest": {
        "backup_id": "2026-01-12_23-25-02_ltis2Uee",
        "created_at": "2026-01-12 23:25:03",
        "wp_version": "6.9",
        "php_version": "8.1.31",
        "site_url": "https://example.com",
        "includes": {
          "database": true,
          "wordpress": true
        },
        "completed_at": "2026-01-12 23:25:51",
        "stats": {
          "files_count": 6241,
          "skipped_count": 0,
          "errors_count": 0
        }
      },
      "file_exists": true,
      "file_size_human": "42 MB"
    }
  ],
  "total": 1
}
```

---

### delete-backup

Backup fájl törlése.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-backup/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| backup_id | string | igen | Törlendő backup azonosító |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Törlés sikeressége |
| message | string | Státusz üzenet |
| deleted_id | string | Törölt backup azonosító |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-backup/run?input%5Bbackup_id%5D=2026-01-13_07-04-13_8fMZyHG8" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Backup deleted successfully",
  "deleted_id": "2026-01-13_07-04-13_8fMZyHG8"
}
```

---

## Health & Diagnostics

### health-check

Átfogó oldal állapotfelmérés futtatása.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/health-check/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| include_debug | boolean | false | Debug információk hozzáadása |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| status | string | Összesített állapot (good, recommended, should_be_improved, critical) |
| score | integer | Pontszám (0-100) |
| issues | array | Talált problémák listája |
| issues[].type | string | Probléma típusa (critical, warning, info) |
| issues[].message | string | Probléma leírása |
| issues[].category | string | Kategória (updates, security, performance, storage, etc.) |
| php_version | string | PHP verzió |
| wp_version | string | WordPress verzió |
| disk_usage | object | Lemezhasználat adatai |
| memory | object | Memória adatok |
| server | object | Szerver információk |
| paths | object | WordPress útvonalak |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/health-check/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "status": "recommended",
  "score": 87,
  "issues": [
    {
      "type": "warning",
      "message": "4 theme update(s) available",
      "category": "updates"
    },
    {
      "type": "info",
      "message": "WP_DEBUG is enabled (should be disabled in production)",
      "category": "security"
    }
  ],
  "php_version": "8.1.31",
  "wp_version": "6.9",
  "disk_usage": {
    "total": 39973924864,
    "total_human": "37 GB",
    "free": 25468809216,
    "free_human": "24 GB",
    "used": 14505115648,
    "used_human": "14 GB",
    "percent_used": 36.29,
    "wordpress": {
      "total": "187 MB",
      "uploads": "71 MB",
      "plugins": "18 MB",
      "themes": "39 MB"
    }
  },
  "memory": {
    "limit": "256 MB",
    "usage": "8 MB",
    "percent": 3.13
  },
  "server": {
    "software": "Apache/2.4.66 (Unix) OpenSSL/3.0.13",
    "hostname": "servername"
  },
  "paths": {
    "wordpress": "/home/user/webapps/app/",
    "wp_content": "/home/user/webapps/app/wp-content",
    "uploads": "/home/user/webapps/app/wp-content/uploads",
    "plugins": "/home/user/webapps/app/wp-content/plugins",
    "themes": "/home/user/webapps/app/wp-content/themes"
  }
}
```

---

### error-log

PHP hibanapló lekérése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/error-log/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| lines | integer | 100 | Lekérendő sorok száma (max 1000) |
| filter | string | - | Szűrés kulcsszóra |
| level | string | "all" | Szűrés szint alapján (all, error, warning, notice) |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| errors | array | Hiba sorok listája (stringek) |
| total | integer | Visszaadott hibák száma |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/error-log/run?input%5Blines%5D=5" \
  -u "user:application_password"
```

**Response:**
```json
{
  "errors": [
    "[13-Jan-2026 07:02:19 UTC] PHP Fatal error: ...",
    "Stack trace:",
    "#0 /path/to/file.php(197): ...",
    "#1 /path/to/file.php(511): ...",
    "#2 {main}"
  ],
  "total": 5
}
```

---

## Database

### optimize-database

Adatbázis táblák optimalizálása.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/optimize-database/run`

#### Input Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| tables | array | Specifikus táblák (ha üres, az összes) |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| optimized | array | Optimalizált táblák listája |
| failed | array | Sikertelen táblák listája |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/optimize-database/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Optimized 17 tables, 0 failed",
  "optimized": [
    "wp_commentmeta",
    "wp_comments",
    "wp_links",
    "wp_options",
    "wp_postmeta",
    "wp_posts",
    "wp_term_relationships",
    "wp_term_taxonomy",
    "wp_termmeta",
    "wp_terms",
    "wp_usermeta",
    "wp_users"
  ],
  "failed": []
}
```

---

### cleanup-database

Revíziók, transientek, spam és egyéb felesleges adatok törlése.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/cleanup-database/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| revisions | boolean | true | Post revíziók törlése |
| auto_drafts | boolean | true | Auto-draft bejegyzések törlése |
| trash_posts | boolean | true | Kukában lévő bejegyzések törlése |
| spam_comments | boolean | true | Spam kommentek törlése |
| trash_comments | boolean | true | Kukában lévő kommentek törlése |
| expired_transients | boolean | true | Lejárt transientek törlése |
| all_transients | boolean | false | Összes transient törlése |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| deleted | object | Törölt elemek kategóriánként |
| deleted.revisions | integer | Törölt revíziók száma |
| deleted.auto_drafts | integer | Törölt auto-draft-ok száma |
| deleted.trash_posts | integer | Törölt kukás bejegyzések száma |
| deleted.spam_comments | integer | Törölt spam kommentek száma |
| deleted.trash_comments | integer | Törölt kukás kommentek száma |
| deleted.expired_transients | integer | Törölt lejárt transientek száma |
| deleted.orphaned_postmeta | integer | Törölt árva postmeta bejegyzések |
| deleted.orphaned_commentmeta | integer | Törölt árva commentmeta bejegyzések |
| total | integer | Összes törölt elem |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/cleanup-database/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Cleaned up 7 items from database",
  "deleted": {
    "revisions": 1,
    "auto_drafts": 1,
    "trash_posts": 1,
    "spam_comments": 0,
    "trash_comments": 0,
    "expired_transients": 1,
    "all_transients": 0,
    "orphaned_postmeta": 3,
    "orphaned_commentmeta": 0
  },
  "total": 7
}
```

---

## Cache

### flush-cache

Cache-ek ürítése (object cache, page cache, OPcache, plugin cache-ek).

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/flush-cache/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| object_cache | boolean | true | Object cache ürítése (Redis, Memcached) |
| page_cache | boolean | true | Page cache ürítése |
| opcache | boolean | true | PHP OPcache ürítése |
| plugin_caches | boolean | true | Plugin cache-ek ürítése |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Művelet sikeressége |
| message | string | Státusz üzenet |
| flushed | array | Ürített cache-ek listája |

#### Példa

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/flush-cache/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"object_cache":true,"page_cache":true,"opcache":true,"plugin_caches":true}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Flushed 3 cache(s): object_cache, opcache, rewrite_rules",
  "flushed": [
    "object_cache",
    "opcache",
    "rewrite_rules"
  ]
}
```

---

## Megjegyzések

### Backup működés
- A backup job aszinkron módon fut a háttérben
- Használd a `backup-status` ability-t a haladás követéséhez
- A backupok a `wp-content/uploads/wpsm-backups/` mappába kerülnek
- Nagy oldalak esetén a backup több percig is tarthat

### HTTP metódusok meghatározása
A WordPress Abilities API a meta annotations alapján határozza meg a HTTP metódust:
- `readonly: true` → GET
- `destructive: true, idempotent: true` → DELETE
- egyébként → POST

---

## Plugin Database Updates

Bizonyos pluginek (WooCommerce, Elementor) időnként adatbázis-frissítést igényelnek. Ezek az ability-k segítenek a DB frissítések kezelésében.

### check-plugin-db-updates

Függőben lévő adatbázis frissítések ellenőrzése.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/check-plugin-db-updates/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| updates | object | Függőben lévő frissítések plugin slug szerint |
| total_updates | integer | Összes függőben lévő frissítés |
| supported | array | Támogatott plugin slugok listája |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/check-plugin-db-updates/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "updates": {
    "woocommerce/woocommerce.php": {
      "current_version": "9.5.1",
      "db_version": "9.4.0",
      "needs_update": true
    }
  },
  "total_updates": 1,
  "supported": ["woocommerce/woocommerce.php", "elementor/elementor.php", "elementor-pro/elementor-pro.php"]
}
```

---

### update-plugin-db

Adott plugin adatbázis-frissítésének futtatása.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/update-plugin-db/run`

#### Input Schema

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| plugin | string | igen | Plugin slug (woocommerce/woocommerce.php, elementor/elementor.php, elementor-pro/elementor-pro.php) |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Frissítés sikeressége |
| message | string | Státusz üzenet |
| plugin | string | Plugin slug |
| php_errors | array | PHP hibák listája (ha voltak) |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-plugin-db/run?input%5Bplugin%5D=woocommerce/woocommerce.php" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Database update completed for woocommerce/woocommerce.php",
  "plugin": "woocommerce/woocommerce.php",
  "php_errors": []
}
```

---

### update-all-plugin-dbs

Összes függőben lévő plugin adatbázis-frissítés futtatása.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/update-all-plugin-dbs/run`

#### Input Schema

| Mező | Típus | Alapértelmezett | Leírás |
|------|-------|-----------------|--------|
| stop_on_error | boolean | true | Megállítás PHP hiba esetén |

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| success | boolean | Összes frissítés sikeressége |
| summary | string | Összefoglaló üzenet |
| updated | array | Sikeresen frissített pluginek |
| failed | array | Sikertelen frissítések |
| php_errors | array | PHP hibák listája |
| stopped_early | boolean | Korán leállt-e hiba miatt |

#### Példa

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-all-plugin-dbs/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "summary": "Updated 2 plugin databases",
  "updated": ["woocommerce/woocommerce.php", "elementor/elementor.php"],
  "failed": [],
  "php_errors": [],
  "stopped_early": false
}
```

---

### get-supported-db-plugins

Támogatott DB-frissítéses pluginek listázása.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-supported-db-plugins/run`

#### Output Schema

| Mező | Típus | Leírás |
|------|-------|--------|
| plugins | array | Támogatott pluginek részletes listája |
| plugins[].slug | string | Plugin slug |
| plugins[].name | string | Plugin neve |
| plugins[].installed | boolean | Telepítve van-e |
| plugins[].active | boolean | Aktív-e |
| total | integer | Összes támogatott plugin |
| installed_count | integer | Telepített támogatott pluginek száma |

#### Példa

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-supported-db-plugins/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "plugins": [
    {
      "slug": "woocommerce/woocommerce.php",
      "name": "WooCommerce",
      "installed": true,
      "active": true
    },
    {
      "slug": "elementor/elementor.php",
      "name": "Elementor",
      "installed": true,
      "active": true
    },
    {
      "slug": "elementor-pro/elementor-pro.php",
      "name": "Elementor Pro",
      "installed": false,
      "active": false
    }
  ],
  "total": 3,
  "installed_count": 2
}
```
