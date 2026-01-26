# Maintenance

Abilities for backup, health check, database and cache management.

## Abilities

### Backup
| Ability | Description | Method |
|---------|-------------|--------|
| create-backup | Start backup | POST |
| backup-status | Get backup status | GET |
| cancel-backup | Cancel backup | POST |
| list-backups | List backups | GET |
| restore-backup | Restore backup | DELETE |
| delete-backup | Delete backup | DELETE |

### Health & Diagnostics
| Ability | Description | Method |
|---------|-------------|--------|
| health-check | Comprehensive site health check | GET |
| error-log | Get PHP error log | GET |

### Database
| Ability | Description | Method |
|---------|-------------|--------|
| optimize-database | Optimize database tables | DELETE |
| cleanup-database | Delete revisions, transients, spam | POST |

### Cache
| Ability | Description | Method |
|---------|-------------|--------|
| flush-cache | Flush caches | POST |

### Plugin Database Updates
| Ability | Description | Method |
|---------|-------------|--------|
| check-plugin-db-updates | Check WooCommerce, Elementor etc. DB updates | GET |
| update-plugin-db | Update specific plugin DB | DELETE |
| update-all-plugin-dbs | Update all plugin DBs | DELETE |
| get-supported-db-plugins | List supported plugins | GET |

---

## Backup Abilities

### create-backup

Start a backup job. Returns immediately with job ID, backup runs in background.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-backup/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| include_database | boolean | true | Include database in backup |
| include_files | boolean | true | Include WordPress files |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Whether job started successfully |
| message | string | Status message |
| backup_id | string | Backup job identifier |
| status | string | Initial status ("pending") |
| total_files | integer | Total number of files |
| total_size | integer | Total size in bytes |
| total_size_human | string | Human readable size |
| chunks_total | integer | Number of processing chunks |

#### Example

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

Get backup job status and progress.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/backup-status/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| backup_id | string | yes | Backup job identifier |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| backup_id | string | Backup identifier |
| status | string | Status (pending, processing, completed, failed, cancelled) |
| progress | number | Progress percentage (0-100) |
| total_files | integer | Total number of files |
| processed_files | integer | Processed files count |
| current_chunk | integer | Current chunk |
| chunks_total | integer | Total chunks |
| created_at | string | Creation time |
| started_at | string | Start time |
| completed_at | string/null | Completion time |
| file_path | string | Backup file path (when complete) |
| file_size | integer | Backup size in bytes (when complete) |
| file_size_human | string | Human readable size (when complete) |
| errors | array | List of errors |

#### Example

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/backup-status/run?input%5Bbackup_id%5D=2026-01-13_07-04-13_8fMZyHG8" \
  -u "user:application_password"
```

**Response (in progress):**
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

**Response (completed):**
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

List available backups.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-backups/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| limit | integer | 20 | Number of items to return |
| offset | integer | 0 | Number of items to skip |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| backups | array | List of backups |
| backups[].file_path | string | Backup file path |
| backups[].file_size | integer | Size in bytes |
| backups[].file_size_human | string | Human readable size |
| backups[].file_exists | boolean | Whether file exists |
| backups[].manifest | object | Backup metadata |
| total | integer | Total number of backups |

#### Example

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

Delete a backup file.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-backup/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| backup_id | string | yes | Backup identifier to delete |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Whether deletion succeeded |
| message | string | Status message |
| deleted_id | string | Deleted backup identifier |

#### Example

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

Run a comprehensive site health check.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/health-check/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| include_debug | boolean | false | Include debug information |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| status | string | Overall status (good, recommended, should_be_improved, critical) |
| score | integer | Score (0-100) |
| issues | array | List of issues found |
| issues[].type | string | Issue type (critical, warning, info) |
| issues[].message | string | Issue description |
| issues[].category | string | Category (updates, security, performance, storage, etc.) |
| php_version | string | PHP version |
| wp_version | string | WordPress version |
| disk_usage | object | Disk usage data |
| memory | object | Memory data |
| server | object | Server information |
| paths | object | WordPress paths |

#### Example

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

Get PHP error log.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/error-log/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| lines | integer | 100 | Number of lines to retrieve (max 1000) |
| filter | string | - | Filter by keyword |
| level | string | "all" | Filter by level (all, error, warning, notice) |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| errors | array | List of error lines (strings) |
| total | integer | Number of errors returned |

#### Example

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

Optimize database tables.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/optimize-database/run`

#### Input Schema

| Field | Type | Description |
|-------|------|-------------|
| tables | array | Specific tables (if empty, all tables) |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| optimized | array | List of optimized tables |
| failed | array | List of failed tables |

#### Example

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

Delete revisions, transients, spam and other unnecessary data.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/cleanup-database/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| revisions | boolean | true | Delete post revisions |
| auto_drafts | boolean | true | Delete auto-draft posts |
| trash_posts | boolean | true | Delete trashed posts |
| spam_comments | boolean | true | Delete spam comments |
| trash_comments | boolean | true | Delete trashed comments |
| expired_transients | boolean | true | Delete expired transients |
| all_transients | boolean | false | Delete all transients |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| deleted | object | Deleted items by category |
| deleted.revisions | integer | Deleted revisions count |
| deleted.auto_drafts | integer | Deleted auto-drafts count |
| deleted.trash_posts | integer | Deleted trashed posts count |
| deleted.spam_comments | integer | Deleted spam comments count |
| deleted.trash_comments | integer | Deleted trashed comments count |
| deleted.expired_transients | integer | Deleted expired transients count |
| deleted.orphaned_postmeta | integer | Deleted orphaned postmeta entries |
| deleted.orphaned_commentmeta | integer | Deleted orphaned commentmeta entries |
| total | integer | Total deleted items |

#### Example

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

Flush caches (object cache, page cache, OPcache, plugin caches).

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/flush-cache/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| object_cache | boolean | true | Flush object cache (Redis, Memcached) |
| page_cache | boolean | true | Flush page cache |
| opcache | boolean | true | Flush PHP OPcache |
| plugin_caches | boolean | true | Flush plugin caches |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| flushed | array | List of flushed caches |

#### Example

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

## Notes

### Backup Operation
- The backup job runs asynchronously in the background
- Use the `backup-status` ability to track progress
- Backups are stored in `wp-content/uploads/wpsm-backups/`
- Large sites may take several minutes to backup

### HTTP Method Determination
The WordPress Abilities API determines HTTP method based on meta annotations:
- `readonly: true` → GET
- `destructive: true, idempotent: true` → DELETE
- otherwise → POST

---

## Plugin Database Updates

Certain plugins (WooCommerce, Elementor) occasionally require database updates. These abilities help manage DB updates.

### check-plugin-db-updates

Check for pending database updates.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/check-plugin-db-updates/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| updates | object | Pending updates by plugin slug |
| total_updates | integer | Total pending updates |
| supported | array | List of supported plugin slugs |

#### Example

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

Run database update for a specific plugin.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/update-plugin-db/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| plugin | string | yes | Plugin slug (woocommerce/woocommerce.php, elementor/elementor.php, elementor-pro/elementor-pro.php) |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Update success |
| message | string | Status message |
| plugin | string | Plugin slug |
| php_errors | array | List of PHP errors (if any) |

#### Example

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

Run all pending plugin database updates.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/update-all-plugin-dbs/run`

#### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| stop_on_error | boolean | true | Stop on PHP error |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Overall success |
| summary | string | Summary message |
| updated | array | Successfully updated plugins |
| failed | array | Failed updates |
| php_errors | array | List of PHP errors |
| stopped_early | boolean | Whether stopped due to error |

#### Example

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

List supported plugins for DB updates.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-supported-db-plugins/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| plugins | array | Detailed list of supported plugins |
| plugins[].slug | string | Plugin slug |
| plugins[].name | string | Plugin name |
| plugins[].installed | boolean | Whether installed |
| plugins[].active | boolean | Whether active |
| total | integer | Total supported plugins |
| installed_count | integer | Installed supported plugins count |

#### Example

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
