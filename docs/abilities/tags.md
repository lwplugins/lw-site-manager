# Tags

Abilities for managing tags.

## Abilities

| Ability | Description | Method |
|---------|-------------|--------|
| list-tags | List tags | GET |
| get-tag | Get tag details | GET |
| create-tag | Create tag | POST |
| update-tag | Update tag | POST |
| delete-tag | Delete tag | DELETE |

---

## list-tags

List tags with filtering and sorting options.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-tags/run`

### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| limit | integer | 20 | Number of items to return |
| offset | integer | 0 | Number of items to skip |
| hide_empty | boolean | false | Hide empty tags |
| search | string | - | Search term |
| orderby | string | "name" | Sort field (name, slug, term_id, count) |
| order | string | "ASC" | Sort direction (ASC, DESC) |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| terms | array | List of tags |
| terms[].id | integer | Tag ID |
| terms[].name | string | Tag name |
| terms[].slug | string | Tag slug |
| terms[].taxonomy | string | Taxonomy type ("post_tag") |
| terms[].count | integer | Post count |
| total | integer | Total tag count |
| total_pages | integer | Total pages |
| limit | integer | Applied limit |
| offset | integer | Applied offset |

### Example

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
      "name": "coffee",
      "slug": "coffee",
      "taxonomy": "post_tag",
      "count": 1
    },
    {
      "id": 7,
      "name": "culture",
      "slug": "culture",
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

Get detailed information about a tag.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-tag/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Tag ID |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Tag data |
| term.id | integer | Tag ID |
| term.name | string | Tag name |
| term.slug | string | Tag slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Post count |
| term.description | string | Tag description |
| term.parent | integer | Always 0 (tags are not hierarchical) |
| term.link | string | Tag archive URL |

### Example

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
    "name": "coffee",
    "slug": "coffee",
    "taxonomy": "post_tag",
    "count": 1,
    "description": "",
    "parent": 0,
    "link": "https://example.com/tag/coffee/"
  }
}
```

---

## create-tag

Create a new tag.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-tag/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | yes | Tag name |
| slug | string | no | Tag slug (auto-generated if not provided) |
| description | string | no | Tag description |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Created tag data |
| term.id | integer | Tag ID |
| term.name | string | Tag name |
| term.slug | string | Tag slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Post count |
| term.description | string | Tag description |
| term.parent | integer | Always 0 |
| term.link | string | Tag archive URL |

### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-tag/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"name":"Test Tag","slug":"test-tag","description":"This is a test tag"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 15,
    "name": "Test Tag",
    "slug": "test-tag",
    "taxonomy": "post_tag",
    "count": 0,
    "description": "This is a test tag",
    "parent": 0,
    "link": "https://example.com/tag/test-tag/"
  }
}
```

---

## update-tag

Update an existing tag.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-tag/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Tag ID |
| name | string | no | New name |
| slug | string | no | New slug |
| description | string | no | New description |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Updated tag data |
| term.id | integer | Tag ID |
| term.name | string | Tag name |
| term.slug | string | Tag slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Post count |
| term.description | string | Tag description |
| term.parent | integer | Always 0 |
| term.link | string | Tag archive URL |

### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-tag/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":15,"name":"Test Tag Updated","description":"Updated description"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 15,
    "name": "Test Tag Updated",
    "slug": "test-tag",
    "taxonomy": "post_tag",
    "count": 0,
    "description": "Updated description",
    "parent": 0,
    "link": "https://example.com/tag/test-tag/"
  }
}
```

---

## delete-tag

Delete a tag.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-tag/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Tag ID to delete |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| id | integer | Deleted tag ID |

### Example

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

### Notes

- Tags are not hierarchical, so there is no parent field in the input
- Deleted tags are automatically removed from associated posts
