# Media Abilities

## list-media

List media items.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-media/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `limit` | integer | no | `20` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `mime_type` | string | no | - | Filter by type (e.g., `image`, `image/jpeg`, `video`) |
| `search` | string | no | - | Search in title |
| `orderby` | string | no | `date` | Sort by: `date`, `title`, `modified` |
| `order` | string | no | `DESC` | Direction: `ASC`, `DESC` |

### Output

```json
{
  "media": [
    {
      "id": 31,
      "title": "Code Screen",
      "url": "https://example.com/wp-content/uploads/2026/01/image.jpg",
      "mime_type": "image/jpeg",
      "date": "2026-01-13 06:18:27"
    }
  ],
  "total": 15,
  "total_pages": 5,
  "limit": 3,
  "offset": 0
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-media/run?input%5Bmime_type%5D=image'
```

---

## get-media

Get detailed information about a single media item.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-media/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Media ID |

### Output

```json
{
  "success": true,
  "media": {
    "id": 40,
    "title": "Test Image",
    "url": "https://example.com/wp-content/uploads/2026/01/image.jpg",
    "mime_type": "image/jpeg",
    "date": "2026-01-13 06:48:57",
    "alt": "",
    "caption": "",
    "description": "",
    "filename": "image.jpg",
    "width": 800,
    "height": 600,
    "filesize": 33012,
    "sizes": {
      "thumbnail": {
        "url": "https://example.com/wp-content/uploads/2026/01/image-150x150.jpg",
        "width": 150,
        "height": 150
      },
      "medium": {
        "url": "https://example.com/wp-content/uploads/2026/01/image-300x225.jpg",
        "width": 300,
        "height": 225
      },
      "large": {
        "url": "https://example.com/wp-content/uploads/2026/01/image.jpg",
        "width": 800,
        "height": 600
      }
    }
  }
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-media/run?input%5Bid%5D=40'
```

---

## upload-media

Upload media from URL.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/upload-media/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `url` | string | - | Source URL (use EITHER `url` OR `data`+`filename`) |
| `data` | string | - | Base64 encoded file data (together with `filename`) |
| `filename` | string | - | Filename with extension (required when using `data`) |
| `title` | string | no | Media title |
| `alt` | string | no | Alt text |
| `caption` | string | no | Caption |
| `description` | string | no | Description |

> **Note:** Either `url` OR `data`+`filename` is required.

### Output

```json
{
  "success": true,
  "media": {
    "id": 40,
    "title": "Test Image",
    "url": "https://example.com/wp-content/uploads/2026/01/test-image.jpg",
    "mime_type": "image/jpeg",
    "date": "2026-01-13 06:48:57",
    "alt": "",
    "caption": "",
    "description": "",
    "filename": "test-image.jpg",
    "width": 800,
    "height": 600,
    "filesize": 33012,
    "sizes": {
      "thumbnail": {...},
      "medium": {...},
      "large": {...}
    }
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/upload-media/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"url":"https://picsum.photos/800/600","title":"Random Image","alt":"Placeholder image"}}'
```

---

## update-media

Update media metadata.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-media/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | Media ID |
| `title` | string | no | New title |
| `alt` | string | no | New alt text |
| `caption` | string | no | New caption |
| `description` | string | no | New description |

### Output

```json
{
  "success": true,
  "media": {
    "id": 40,
    "title": "Updated title",
    "alt": "New alt text",
    ...
  }
}
```

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-media/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":40,"title":"Updated title","alt":"New alt text"}}'
```

---

## delete-media

Delete media.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-media/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Media ID |
| `force` | boolean | no | `true` | Permanent deletion (skip trash) |

### Output

```json
{
  "success": true,
  "message": "Media deleted successfully",
  "id": 40
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-media/run?input%5Bid%5D=40'
```
