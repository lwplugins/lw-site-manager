# User Management Abilities

## list-users

Felhasználók listázása szűrési és lapozási lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-users/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `limit` | integer | nem | `50` | Visszaadott elemek száma |
| `offset` | integer | nem | `0` | Kihagyott elemek száma |
| `orderby` | string | nem | `registered` | Rendezés: `registered`, `display_name`, `email`, `login` |
| `order` | string | nem | `DESC` | Irány: `ASC`, `DESC` |
| `role` | string | nem | - | Szűrés szerepkör szerint (pl. `administrator`) |
| `search` | string | nem | - | Keresés username, email, display_name mezőkben |

### Output

```json
{
  "users": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "display_name": "Admin User",
      "roles": ["administrator"],
      "registered": "2026-01-12 20:27:32"
    }
  ],
  "total": 7,
  "total_pages": 2,
  "limit": 5,
  "offset": 0,
  "has_more": true
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-users/run?input%5Blimit%5D=10&input%5Brole%5D=subscriber'
```

---

## get-user

Egyetlen felhasználó részletes adatainak lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-user/run`

### Input

Legalább egy azonosító megadása kötelező:

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | - | User ID |
| `email` | string | - | Email cím |
| `login` | string | - | Username |

### Output

```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@example.com",
    "display_name": "Admin User",
    "roles": ["administrator"],
    "registered": "2026-01-12 20:27:32",
    "first_name": "Admin",
    "last_name": "User",
    "website": "https://example.com",
    "bio": "",
    "avatar": "https://gravatar.com/...",
    "posts_count": "5",
    "last_login": null,
    "capabilities": ["switch_themes", "edit_themes", "..."]
  }
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-user/run?input%5Bid%5D=1'
```

---

## create-user

Új felhasználó létrehozása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-user/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `username` | string | **igen** | - | Felhasználónév |
| `email` | string | **igen** | - | Email cím |
| `password` | string | nem | auto-generated | Jelszó |
| `display_name` | string | nem | - | Megjelenítendő név |
| `first_name` | string | nem | - | Keresztnév |
| `last_name` | string | nem | - | Vezetéknév |
| `website` | string | nem | - | Weboldal URL |
| `role` | string | nem | `subscriber` | Szerepkör |
| `send_notification` | boolean | nem | `false` | Üdvözlő email küldése |

### Output

```json
{
  "success": true,
  "message": "User created successfully",
  "user": {
    "id": 18,
    "username": "testuser123",
    "email": "testuser123@example.com",
    "display_name": "Test User",
    "roles": ["editor"],
    "registered": "2026-01-13 06:34:27"
  },
  "id": 18,
  "password": "FIc{BY]$bPNqy%-g"
}
```

> **Megjegyzés:** A `password` mező csak akkor szerepel a válaszban, ha automatikusan lett generálva.

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-user/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"username":"newuser","email":"new@example.com","role":"editor"}}'
```

---

## update-user

Meglévő felhasználó módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-user/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | User ID |
| `email` | string | nem | Új email cím |
| `display_name` | string | nem | Megjelenítendő név |
| `first_name` | string | nem | Keresztnév |
| `last_name` | string | nem | Vezetéknév |
| `website` | string | nem | Weboldal URL |
| `password` | string | nem | Új jelszó |
| `role` | string | nem | Új szerepkör |

### Output

```json
{
  "success": true,
  "message": "User updated successfully",
  "user": {
    "id": 18,
    "username": "testuser123",
    "email": "testuser123@example.com",
    "display_name": "Test User",
    "roles": ["author"],
    "registered": "2026-01-13 06:34:27"
  }
}
```

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-user/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":18,"first_name":"Test","last_name":"User","role":"author"}}'
```

---

## delete-user

Felhasználó törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-user/run`

### Input

| Mező | Típus | Kötelező | Leírás |
|------|-------|----------|--------|
| `id` | integer | **igen** | Törlendő user ID |
| `reassign_to` | integer | nem | Tartalmak átruházása erre a user ID-ra |

### Output

```json
{
  "success": true,
  "message": "User deleted successfully",
  "deleted_id": 18,
  "reassigned": null
}
```

### Példa

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-user/run?input%5Bid%5D=18&input%5Breassign_to%5D=1'
```

---

## reset-password

Felhasználó jelszavának visszaállítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/reset-password/run`

### Input

Legalább egy azonosító megadása kötelező:

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | - | - | User ID |
| `email` | string | - | - | Email cím |
| `login` | string | - | - | Username |
| `new_password` | string | nem | auto-generated | Új jelszó |
| `send_notification` | boolean | nem | `true` | Email küldése az új jelszóval |

### Output

```json
{
  "success": true,
  "message": "Password reset successfully",
  "user_id": 18,
  "email": "testuser123@example.com",
  "notified": false,
  "password": "y+P3eZi;!y*mT.Um"
}
```

> **Megjegyzés:** A `password` mező csak akkor szerepel, ha automatikusan lett generálva.

### Példa

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/reset-password/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":18,"send_notification":false}}'
```

---

## get-roles

Elérhető szerepkörök listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-roles/run`

### Input

Nincs kötelező paraméter.

### Output

```json
{
  "roles": [
    {
      "slug": "administrator",
      "name": "Administrator",
      "capabilities": ["switch_themes", "edit_themes", "..."],
      "user_count": 2
    },
    {
      "slug": "editor",
      "name": "Editor",
      "capabilities": ["moderate_comments", "manage_categories", "..."],
      "user_count": 0
    },
    {
      "slug": "subscriber",
      "name": "Subscriber",
      "capabilities": ["read", "level_0"],
      "user_count": 5
    }
  ],
  "total": 7
}
```

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-roles/run'
```
