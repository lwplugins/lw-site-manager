# Media Abilities

## list-media

Média elemek listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-media/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `mime_type` | string | nem | - | Szűrés típus szerint (pl. `image`, `image/jpeg`, `video`) |
| `search` | string | nem | - | Keresés címben |
| `orderby` | string | nem | `date` | Rendezés: `date`, `title`, `modified` |
| `order` | string | nem | `DESC` | Irány: `ASC`, `DESC` |

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

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-media/run?input%5Bmime_type%5D=image'
```

---

## get-media

Egyetlen média elem részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-media/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Media ID |

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

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-media/run?input%5Bid%5D=40'
```

---

## upload-media

Média feltöltése URL-ről.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/upload-media/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `url` | string | - | Forrás URL (használd VAGY `data`+`filename`) |
| `data` | string | - | Base64 kódolt fájl adat (`filename`-mel együtt) |
| `filename` | string | - | Fájlnév kiterjesztéssel (`data` használatakor kötelező) |
| `title` | string | nem | Média cím |
| `alt` | string | nem | Alt szöveg |
| `caption` | string | nem | Képaláírás |
| `description` | string | nem | Leírás |

> **Megjegyzés:** `url` VAGY `data`+`filename` megadása kötelező.

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

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/upload-media/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"url":"https://picsum.photos/800/600","title":"Random Image","alt":"Placeholder image"}}'
```

---

## update-media

Média metaadatok módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-media/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Media ID |
| `title` | string | nem | Új cím |
| `alt` | string | nem | Új alt szöveg |
| `caption` | string | nem | Új képaláírás |
| `description` | string | nem | Új leírás |

### Output

```json
{
  "success": true,
  "media": {
    "id": 40,
    "title": "Frissített cím",
    "alt": "Új alt szöveg",
    ...
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-media/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":40,"title":"Frissített cím","alt":"Új alt szöveg"}}'
```

---

## delete-media

Média törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-media/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Media ID |
| `force` | boolean | nem | `true` | Végleges törlés (kuka kihagyása) |

### Output

```json
{
  "success": true,
  "message": "Media deleted successfully",
  "id": 40
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-media/run?input%5Bid%5D=40'
```
