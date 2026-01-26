# Categories

Abilities for managing categories.

## Abilities

| Ability | Description | Method |
|---------|-------------|--------|
| list-categories | List categories | GET |
| get-category | Get category details | GET |
| create-category | Create category | POST |
| update-category | Update category | POST |
| delete-category | Delete category | DELETE |

---

## list-categories

List categories with filtering and sorting options.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/list-categories/run`

### Input Schema

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| limit | integer | 20 | Number of items to return |
| offset | integer | 0 | Number of items to skip |
| hide_empty | boolean | false | Hide empty categories |
| search | string | - | Search term |
| parent | integer | - | Filter by parent category ID |
| orderby | string | "name" | Sort field (name, slug, term_id, count) |
| order | string | "ASC" | Sort direction (ASC, DESC) |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| terms | array | List of categories |
| terms[].id | integer | Category ID |
| terms[].name | string | Category name |
| terms[].slug | string | Category slug |
| terms[].taxonomy | string | Taxonomy type ("category") |
| terms[].count | integer | Number of posts |
| total | integer | Total number of categories |
| total_pages | integer | Total number of pages |
| limit | integer | Applied limit |
| offset | integer | Applied offset |

### Example

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
      "name": "Development",
      "slug": "development",
      "taxonomy": "category",
      "count": 0
    },
    {
      "id": 4,
      "name": "Gastronomy",
      "slug": "gastronomy",
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

Get detailed information about a category.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-category/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Category ID |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Category data |
| term.id | integer | Category ID |
| term.name | string | Category name |
| term.slug | string | Category slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Number of posts |
| term.description | string | Category description |
| term.parent | integer | Parent category ID (0 if none) |
| term.link | string | Category archive URL |

### Example

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
    "name": "Gastronomy",
    "slug": "gastronomy",
    "taxonomy": "category",
    "count": 1,
    "description": "Food, drinks, recipes",
    "parent": 0,
    "link": "https://example.com/category/gastronomy/"
  }
}
```

---

## create-category

Create a new category.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/create-category/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | yes | Category name |
| slug | string | no | Category slug (auto-generated if not provided) |
| description | string | no | Category description |
| parent | integer | no | Parent category ID |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Created category data |
| term.id | integer | Category ID |
| term.name | string | Category name |
| term.slug | string | Category slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Number of posts |
| term.description | string | Category description |
| term.parent | integer | Parent category ID |
| term.link | string | Category archive URL |

### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-category/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"name":"Test Category","slug":"test-category","description":"This is a test category","parent":0}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 14,
    "name": "Test Category",
    "slug": "test-category",
    "taxonomy": "category",
    "count": 0,
    "description": "This is a test category",
    "parent": 0,
    "link": "https://example.com/category/test-category/"
  }
}
```

---

## update-category

Update an existing category.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-category/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Category ID |
| name | string | no | New name |
| slug | string | no | New slug |
| description | string | no | New description |
| parent | integer | no | New parent category ID |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term | object | Updated category data |
| term.id | integer | Category ID |
| term.name | string | Category name |
| term.slug | string | Category slug |
| term.taxonomy | string | Taxonomy type |
| term.count | integer | Number of posts |
| term.description | string | Category description |
| term.parent | integer | Parent category ID |
| term.link | string | Category archive URL |

### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-category/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":14,"name":"Test Category Updated","description":"Updated description"}}'
```

**Response:**
```json
{
  "success": true,
  "term": {
    "id": 14,
    "name": "Test Category Updated",
    "slug": "test-category",
    "taxonomy": "category",
    "count": 0,
    "description": "Updated description",
    "parent": 0,
    "link": "https://example.com/category/test-category/"
  }
}
```

---

## delete-category

Delete a category.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-category/run`

### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | integer | yes | Category ID to delete |

### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| id | integer | Deleted category ID |

### Example

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

### Notes

- The default category (Uncategorized) cannot be deleted
- Posts belonging to deleted categories will be moved to the default category
