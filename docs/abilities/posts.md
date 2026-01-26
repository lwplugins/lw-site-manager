# Posts Abilities

## list-posts

List posts with filtering and pagination options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-posts/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `post_type` | string | no | `post` | Post type |
| `limit` | integer | no | `20` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `status` | string | no | `any` | Status: `publish`, `draft`, `pending`, `trash`, `any` |
| `author` | integer | no | - | Author ID |
| `category` | string | no | - | Category slug |
| `tag` | string | no | - | Tag slug |
| `search` | string | no | - | Search in title and content |
| `date_after` | string | no | - | After date (Y-m-d) |
| `date_before` | string | no | - | Before date (Y-m-d) |
| `orderby` | string | no | `date` | Sort field |
| `order` | string | no | `DESC` | Direction: `ASC`, `DESC` |

### Output

```json
{
  "posts": [
    {
      "id": 32,
      "title": "Essential Git Commands",
      "slug": "essential-git-commands",
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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-posts/run?input%5Blimit%5D=10&input%5Bstatus%5D=publish'
```

---

## get-post

Get detailed information about a single post.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-post/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | - | - | Post ID |
| `slug` | string | - | - | Post slug |
| `post_type` | string | no | `post` | Post type (required when using slug) |

> **Note:** Either `id` or `slug` is required.

### Output

```json
{
  "success": true,
  "post": {
    "id": 32,
    "title": "Essential Git Commands",
    "slug": "essential-git-commands",
    "status": "publish",
    "type": "post",
    "date": "2026-01-13 06:19:12",
    "modified": "2026-01-13 06:19:12",
    "author": 2,
    "content": "<p>Content...</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "guid": "https://example.com/...",
    "permalink": "https://example.com/essential-git-commands/",
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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post/run?input%5Bid%5D=32'
```

---

## create-post

Create a new post.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-post/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `title` | string | **yes** | - | Title |
| `content` | string | no | - | Content (HTML) |
| `excerpt` | string | no | - | Excerpt |
| `status` | string | no | `draft` | Status: `draft`, `publish`, `pending`, `private`, `future` |
| `post_type` | string | no | `post` | Post type |
| `slug` | string | no | auto | URL slug |
| `author` | integer | no | current | Author ID |
| `parent` | integer | no | - | Parent post ID |
| `menu_order` | integer | no | - | Menu order |
| `date` | string | no | now | Date (Y-m-d H:i:s) |
| `categories` | array | no | - | Category IDs (for `post` type) |
| `tags` | array | no | - | Tag names, slugs or IDs (can be mixed) |
| `featured_image` | integer | no | - | Featured image attachment ID |
| `meta` | object | no | - | Custom meta fields |
| `taxonomies` | object | no | - | Custom taxonomies: `{"taxonomy_name": [term_ids]}` |

### Output

```json
{
  "success": true,
  "message": "Post created successfully",
  "post": {
    "id": 33,
    "title": "Test Post",
    "slug": "test-post",
    "status": "publish",
    "type": "post",
    "date": "2026-01-13 06:36:40",
    "modified": "2026-01-13 06:36:40",
    "author": 2,
    "content": "<p>Content</p>",
    "excerpt": "",
    "parent": 0,
    "menu_order": 0,
    "permalink": "https://example.com/test-post/",
    "featured_image": null,
    "categories": [{"id": 1, "name": "Uncategorized", "slug": "uncategorized"}],
    "tags": []
  },
  "id": 33
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"title":"New post","content":"<p>Content</p>","status":"publish","categories":[1,2]}}'
```

---

## update-post

Update an existing post.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-post/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Post ID |
| `title` | string | no | New title |
| `content` | string | no | New content |
| `excerpt` | string | no | New excerpt |
| `status` | string | no | New status |
| `slug` | string | no | New slug |
| `author` | integer | no | New author |
| `parent` | integer | no | Parent ID |
| `menu_order` | integer | no | Menu order |
| `date` | string | no | New date |
| `categories` | array | no | Category IDs (for `post` type) |
| `tags` | array | no | Tag names, slugs or IDs (can be mixed) |
| `featured_image` | integer | no | Featured image ID |
| `meta` | object | no | Meta fields |
| `taxonomies` | object | no | Custom taxonomies: `{"taxonomy_name": [term_ids]}` |

### Output

```json
{
  "success": true,
  "message": "Post updated successfully",
  "post": {
    "id": 33,
    "title": "Updated title",
    "slug": "test-post",
    "status": "publish",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33,"title":"Updated title","excerpt":"New excerpt"}}'
```

---

## delete-post

Delete a post (to trash or permanently).

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Post ID |
| `force` | boolean | no | `false` | Permanent deletion (skip trash) |

### Output

```json
{
  "success": true,
  "message": "Post moved to trash",
  "deleted_id": 33,
  "force_delete": false
}
```

### Example

```bash
# Move to trash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run?input%5Bid%5D=33'

# Permanent deletion
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post/run?input%5Bid%5D=33&input%5Bforce%5D=true'
```

---

## restore-post

Restore a post from trash.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/restore-post/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Post ID |

### Output

```json
{
  "success": true,
  "message": "Post restored from trash",
  "post": {
    "id": 33,
    "title": "Restored post",
    "slug": "test-post",
    "status": "draft",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/restore-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33}}'
```

---

## duplicate-post

Duplicate a post.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-post/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Post ID to duplicate |
| `new_title` | string | no | `"Original (Copy)"` | New title |
| `status` | string | no | `draft` | Copy status |
| `copy_meta` | boolean | no | `true` | Copy meta fields |

### Output

```json
{
  "success": true,
  "message": "Post duplicated successfully",
  "original_id": 33,
  "post": {
    "id": 35,
    "title": "Post copy",
    "slug": "",
    "status": "draft",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/duplicate-post/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":33,"new_title":"Copy"}}'
```

---

## bulk-posts

Bulk operations on multiple posts.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/bulk-posts/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ids` | array | **yes** | Array of post IDs |
| `action` | string | **yes** | Action: `publish`, `draft`, `trash`, `delete`, `restore` |

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

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/bulk-posts/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"ids":[33,35],"action":"trash"}}'
```

---

## get-post-types

List available post types.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-post-types/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `public` | boolean | no | Only public types |

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-types/run?input%5Bpublic%5D=true'
```

---

## set-post-terms

Set taxonomy terms for a post. Supports custom post types and custom taxonomies.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/set-post-terms/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Post ID |
| `taxonomy` | string | **yes** | - | Taxonomy name (e.g., `category`, `post_tag`, or custom) |
| `terms` | array | no | `[]` | Array of term IDs |
| `append` | boolean | no | `false` | Append terms instead of replacing |

### Output

```json
{
  "success": true,
  "message": "Terms updated successfully",
  "post_id": 14,
  "taxonomy": "bovitmeny_category",
  "terms": [
    {"id": 3, "name": "E-commerce", "slug": "e-commerce"},
    {"id": 4, "name": "WooCommerce", "slug": "woocommerce"}
  ]
}
```

### Example

```bash
# Set categories for a custom post type
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-post-terms/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":14,"taxonomy":"bovitmeny_category","terms":[3,4]}}'

# Append terms instead of replacing
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-post-terms/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":14,"taxonomy":"bovitmeny_category","terms":[5],"append":true}}'
```

---

## get-post-terms

Get taxonomy terms for a post. Returns either terms for a specific taxonomy or all taxonomies.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-post-terms/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Post ID |
| `taxonomy` | string | no | Taxonomy name (returns all if not specified) |

### Output (specific taxonomy)

```json
{
  "success": true,
  "post_id": 14,
  "taxonomy": "bovitmeny_category",
  "terms": [
    {"id": 3, "name": "E-commerce", "slug": "e-commerce", "taxonomy": "bovitmeny_category"},
    {"id": 4, "name": "WooCommerce", "slug": "woocommerce", "taxonomy": "bovitmeny_category"}
  ]
}
```

### Output (all taxonomies)

```json
{
  "success": true,
  "post_id": 14,
  "taxonomies": {
    "bovitmeny_category": [
      {"id": 3, "name": "E-commerce", "slug": "e-commerce"},
      {"id": 4, "name": "WooCommerce", "slug": "woocommerce"}
    ],
    "bovitmeny_tag": [
      {"id": 10, "name": "subscription", "slug": "subscription"}
    ]
  }
}
```

### Example

```bash
# Get terms for a specific taxonomy
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-terms/run?input%5Bid%5D=14&input%5Btaxonomy%5D=bovitmeny_category'

# Get all taxonomy terms
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-terms/run?input%5Bid%5D=14'
```
