# Comments Abilities

## list-comments

List comments with filtering options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-comments/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `limit` | integer | no | `50` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `status` | string | no | `all` | Status: `all`, `approve`, `hold`, `spam`, `trash` |
| `post_id` | integer | no | - | Filter by post ID |
| `type` | string | no | - | Type: `comment`, `pingback`, `trackback` |
| `author_email` | string | no | - | Filter by author email |
| `search` | string | no | - | Search in content |
| `orderby` | string | no | `comment_date` | Sort field |
| `order` | string | no | `DESC` | Direction: `ASC`, `DESC` |

### Output

```json
{
  "comments": [
    {
      "id": 18,
      "post_id": 32,
      "author": "john_doe",
      "author_email": "john@example.com",
      "content": "Thank you everyone for the answers!",
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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-comments/run?input%5Bpost_id%5D=32&input%5Blimit%5D=10'
```

---

## get-comment

Get detailed information about a single comment.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-comment/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Comment ID |

### Output

```json
{
  "success": true,
  "comment": {
    "id": 19,
    "post_id": 32,
    "author": "Test User",
    "author_email": "test@test.com",
    "content": "This is a test comment",
    "date": "2026-01-13 06:43:40",
    "status": "approved",
    "parent": 0,
    "type": "comment",
    "author_url": "",
    "author_ip": "",
    "user_id": 0,
    "agent": "",
    "date_gmt": "2026-01-13 06:43:40",
    "post_title": "Essential Git Commands",
    "avatar": "https://gravatar.com/avatar/...",
    "replies_count": 0
  }
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-comment/run?input%5Bid%5D=19'
```

---

## create-comment

Create a new comment.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-comment/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `post_id` | integer | **yes** | - | Post ID |
| `content` | string | **yes** | - | Comment content |
| `author_name` | string | no | - | Author name |
| `author_email` | string | no | - | Author email |
| `author_url` | string | no | - | Author website |
| `parent` | integer | no | `0` | Parent comment ID (for replies) |
| `approved` | boolean | no | `true` | Whether the comment is approved |
| `user_id` | integer | no | - | WordPress user ID (overrides author fields) |

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
    "content": "This is a test comment",
    "date": "2026-01-13 06:43:40",
    "status": "approved",
    "parent": 0,
    "type": "comment"
  },
  "id": 19
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"post_id":32,"author_name":"Test User","author_email":"test@test.com","content":"Test comment"}}'
```

---

## update-comment

Update a comment.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-comment/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Comment ID |
| `content` | string | no | New content |
| `author_name` | string | no | New author name |
| `author_email` | string | no | New email |
| `author_url` | string | no | New URL |
| `status` | string | no | New status: `approve`, `hold`, `spam`, `trash` |

### Output

```json
{
  "success": true,
  "message": "Comment updated successfully",
  "comment": {
    "id": 19,
    "post_id": 32,
    "author": "Test User",
    "content": "This is an updated test comment",
    "status": "approved",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19,"content":"Updated content"}}'
```

---

## delete-comment

Delete a comment.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Comment ID |
| `force` | boolean | no | `false` | Permanent deletion (skip trash) |

### Output

```json
{
  "success": true,
  "message": "Comment permanently deleted",
  "deleted_id": 19,
  "force_delete": true
}
```

### Example

```bash
# Move to trash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run?input%5Bid%5D=19'

# Permanent deletion
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-comment/run?input%5Bid%5D=19&input%5Bforce%5D=true'
```

---

## approve-comment

Approve a comment.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/approve-comment/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Comment ID |

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

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/approve-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19}}'
```

---

## spam-comment

Mark a comment as spam.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/spam-comment/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Comment ID |

### Output

```json
{
  "success": true,
  "message": "Comment marked as spam",
  "id": 19
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/spam-comment/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":19}}'
```

---

## bulk-comments

Bulk operations on comments.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/bulk-comments/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ids` | array | **yes** | Array of comment IDs |
| `action` | string | **yes** | Action: `approve`, `unapprove`, `spam`, `trash`, `delete` |

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

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/bulk-comments/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"ids":[19,20,21],"action":"approve"}}'
```

---

## comment-counts

Get comment statistics.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `post_id` | integer | no | Filter by specific post |

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run'

# For a specific post
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/comment-counts/run?input%5Bpost_id%5D=32'
```
