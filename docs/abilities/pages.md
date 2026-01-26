# Pages Abilities

## list-pages

List pages with filtering options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-pages/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `limit` | integer | no | `20` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `status` | string | no | `any` | Status: `publish`, `draft`, `pending`, `trash`, `any` |
| `author` | integer | no | - | Author ID |
| `search` | string | no | - | Search in title and content |
| `parent` | integer | no | - | Parent page ID |
| `orderby` | string | no | `menu_order` | Sort field |
| `order` | string | no | `ASC` | Direction: `ASC`, `DESC` |

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-pages/run?input%5Bstatus%5D=publish'
```

---

## get-page

Get detailed information about a single page.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-page/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | - | Page ID |
| `slug` | string | - | Page slug |

> **Note:** Either `id` or `slug` is required.

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
    "content": "<p>Content...</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "permalink": "https://example.com/sample-page/",
    "featured_image": null,
    "comment_status": "closed"
  }
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-page/run?input%5Bid%5D=2'
```

---

## create-page

Create a new page.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-page/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `title` | string | **yes** | - | Title |
| `content` | string | no | - | Content (HTML) |
| `excerpt` | string | no | - | Excerpt |
| `status` | string | no | `draft` | Status: `draft`, `publish`, `pending`, `private` |
| `slug` | string | no | auto | URL slug |
| `author` | integer | no | current | Author ID |
| `parent` | integer | no | - | Parent page ID |
| `menu_order` | integer | no | - | Menu order |
| `template` | string | no | - | Page template slug |
| `featured_image` | integer | no | - | Featured image ID |
| `meta` | object | no | - | Meta fields |

### Output

```json
{
  "success": true,
  "message": "Post created successfully",
  "id": 38,
  "page": {
    "id": 38,
    "title": "Test Page",
    "slug": "test-page",
    "status": "publish",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"title":"New page","content":"<p>Content</p>","status":"publish"}}'
```

---

## update-page

Update an existing page.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-page/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Page ID |
| `title` | string | no | New title |
| `content` | string | no | New content |
| `excerpt` | string | no | New excerpt |
| `status` | string | no | New status |
| `slug` | string | no | New slug |
| `author` | integer | no | New author |
| `parent` | integer | no | Parent ID |
| `menu_order` | integer | no | Menu order |
| `template` | string | no | Template |
| `featured_image` | integer | no | Featured image ID |
| `meta` | object | no | Meta fields |

### Output

```json
{
  "success": true,
  "message": "Post updated successfully",
  "page": {
    "id": 38,
    "title": "Updated page",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":38,"title":"Updated title"}}'
```

---

## delete-page

Delete a page.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-page/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Page ID |
| `force` | boolean | no | `false` | Permanent deletion |

### Output

```json
{
  "success": true,
  "message": "Post moved to trash",
  "deleted_id": 38,
  "force_delete": false
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-page/run?input%5Bid%5D=38'
```

---

## page-hierarchy

Get hierarchical page tree.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/page-hierarchy/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `status` | string | no | `publish` | Page status |

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/page-hierarchy/run'
```

---

## page-templates

List available page templates.

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/page-templates/run'
```

---

## front-page-settings

Get homepage and blog page settings.

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

When static page is set:

```json
{
  "display_mode": "page",
  "homepage": {
    "id": 10,
    "title": "Home",
    "slug": "home"
  },
  "posts_page": {
    "id": 15,
    "title": "Blog",
    "slug": "blog"
  }
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/front-page-settings/run'
```

---

## set-homepage

Set a page as homepage.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-homepage/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Page ID |

### Output

```json
{
  "success": true,
  "message": "Homepage updated successfully",
  "page_id": 10,
  "previous_id": 0
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-homepage/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":10}}'
```

---

## set-posts-page

Set a page as blog page.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-posts-page/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Page ID |

### Output

```json
{
  "success": true,
  "message": "Posts page updated successfully",
  "page_id": 15,
  "previous_id": 0
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-posts-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":15}}'
```

---

## restore-page

Restore a page from trash.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/restore-page/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Page ID |

### Output

```json
{
  "success": true,
  "message": "Page restored successfully",
  "page": {
    "id": 38,
    "title": "Restored page",
    "slug": "restored-page",
    "status": "draft",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/restore-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":38}}'
```

---

## duplicate-page

Duplicate a page.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-page/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Page ID to duplicate |
| `new_title` | string | no | - | New title for the copy |
| `status` | string | no | `draft` | Copy status |
| `copy_meta` | boolean | no | `true` | Copy meta fields |

### Output

```json
{
  "success": true,
  "message": "Page duplicated successfully",
  "id": 45,
  "page": {
    "id": 45,
    "title": "Page (copy)",
    "slug": "page-copy",
    "status": "draft",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-page/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":38,"new_title":"Page copy","status":"draft"}}'
```

---

## reorder-pages

Reorder pages.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/reorder-pages/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `order` | array | **yes** | Array of page IDs in desired order |

### Output

```json
{
  "success": true,
  "message": "Pages reordered successfully",
  "updated": 5
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/reorder-pages/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"order":[10,5,15,8,12]}}'
```

---

## set-page-template

Set a page template.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-page-template/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Page ID |
| `template` | string | no | Template slug (or "default") |

### Output

```json
{
  "success": true,
  "message": "Page template updated successfully",
  "page_id": 38,
  "template": "page-no-title",
  "previous_template": "default"
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-page-template/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":38,"template":"page-no-title"}}'
```
