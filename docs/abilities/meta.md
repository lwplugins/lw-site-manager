# Meta (Metadata)

Abilities for managing post, user and term metadata.

## Abilities

### Post Meta
| Ability | Description | Method |
|---------|-------------|--------|
| get-post-meta | Get post/page metadata | GET |
| set-post-meta | Set post/page metadata | POST |
| delete-post-meta | Delete post/page metadata | DELETE |

### User Meta
| Ability | Description | Method |
|---------|-------------|--------|
| get-user-meta | Get user metadata | GET |
| set-user-meta | Set user metadata | POST |
| delete-user-meta | Delete user metadata | DELETE |

### Term Meta
| Ability | Description | Method |
|---------|-------------|--------|
| get-term-meta | Get category/tag metadata | GET |
| set-term-meta | Set category/tag metadata | POST |
| delete-term-meta | Delete category/tag metadata | DELETE |

---

## Post Meta

### get-post-meta

Get metadata for a post or page.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-post-meta/run`

#### Input Schema

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| post_id | integer | yes | - | Post or page ID |
| key | string | no | - | Specific meta key (optional, if not provided returns all meta) |
| include_private | boolean | no | false | Include private meta keys (starting with _) |

#### Output Schema (all meta)

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| post_id | integer | Post ID |
| meta | object | Meta key-value pairs |

#### Output Schema (specific key)

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| post_id | integer | Post ID |
| key | string | Meta key |
| value | mixed | Meta value |

#### Example - All meta

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

#### Example - Specific key

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-post-meta/run?input%5Bpost_id%5D=1&input%5Bkey%5D=test_meta_key" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "post_id": 1,
  "key": "test_meta_key",
  "value": "test value"
}
```

---

### set-post-meta

Set metadata for a post or page.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-post-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| post_id | integer | yes | Post or page ID |
| key | string | yes | Meta key |
| value | mixed | yes | Meta value (string, number, boolean, array, object) |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| post_id | integer | Post ID |
| key | string | Meta key |
| value | mixed | Set value |

#### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-post-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"post_id":1,"key":"test_meta_key","value":"test value"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "post_id": 1,
  "key": "test_meta_key",
  "value": "test value"
}
```

---

### delete-post-meta

Delete metadata for a post or page.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-post-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| post_id | integer | yes | Post or page ID |
| key | string | yes | Meta key to delete |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| post_id | integer | Post ID |
| key | string | Deleted meta key |

#### Example

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-post-meta/run?input%5Bpost_id%5D=1&input%5Bkey%5D=test_meta_key" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "post_id": 1,
  "key": "test_meta_key"
}
```

---

## User Meta

### get-user-meta

Get user metadata.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-user-meta/run`

#### Input Schema

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| user_id | integer | yes | - | User ID |
| key | string | no | - | Specific meta key (optional) |
| include_private | boolean | no | false | Include private meta keys |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| user_id | integer | User ID |
| meta | object | Meta key-value pairs |

#### Example

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

Set user metadata.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-user-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| user_id | integer | yes | User ID |
| key | string | yes | Meta key |
| value | mixed | yes | Meta value |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| user_id | integer | User ID |
| key | string | Meta key |
| value | mixed | Set value |

#### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-user-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"user_id":1,"key":"test_user_meta","value":"user meta value"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "user_id": 1,
  "key": "test_user_meta",
  "value": "user meta value"
}
```

---

### delete-user-meta

Delete user metadata.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-user-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| user_id | integer | yes | User ID |
| key | string | yes | Meta key to delete |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| user_id | integer | User ID |
| key | string | Deleted meta key |

#### Example

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-user-meta/run?input%5Buser_id%5D=1&input%5Bkey%5D=test_user_meta" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "user_id": 1,
  "key": "test_user_meta"
}
```

---

## Term Meta

### get-term-meta

Get category or tag metadata.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-term-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| term_id | integer | yes | Term (category/tag) ID |
| key | string | no | Specific meta key (optional) |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| term_id | integer | Term ID |
| meta | object | Meta key-value pairs |

#### Example

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

Set category or tag metadata.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/set-term-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| term_id | integer | yes | Term ID |
| key | string | yes | Meta key |
| value | mixed | yes | Meta value |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| term_id | integer | Term ID |
| key | string | Meta key |
| value | mixed | Set value |

#### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/set-term-meta/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"term_id":1,"key":"test_term_meta","value":"term meta value"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Meta updated successfully",
  "term_id": 1,
  "key": "test_term_meta",
  "value": "term meta value"
}
```

---

### delete-term-meta

Delete category or tag metadata.

**Endpoint:** `DELETE /wp-json/wp-abilities/v1/abilities/site-manager/delete-term-meta/run`

#### Input Schema

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| term_id | integer | yes | Term ID |
| key | string | yes | Meta key to delete |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| term_id | integer | Term ID |
| key | string | Deleted meta key |

#### Example

**Request:**
```bash
curl -X DELETE "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-term-meta/run?input%5Bterm_id%5D=1&input%5Bkey%5D=test_term_meta" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "message": "Meta deleted successfully",
  "term_id": 1,
  "key": "test_term_meta"
}
```

---

## Notes

- Private meta keys (starting with `_`) are not included in queries by default
- Use `include_private: true` parameter to include private meta keys (for post and user meta)
- Meta values can be simple types (string, number, boolean) or complex types (array, object)
- WordPress automatically serializes/deserializes complex values
