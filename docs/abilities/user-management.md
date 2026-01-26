# User Management Abilities

## list-users

List users with filtering and pagination options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/list-users/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `limit` | integer | no | `50` | Number of items to return |
| `offset` | integer | no | `0` | Number of items to skip |
| `orderby` | string | no | `registered` | Sort by: `registered`, `display_name`, `email`, `login` |
| `order` | string | no | `DESC` | Direction: `ASC`, `DESC` |
| `role` | string | no | - | Filter by role (e.g., `administrator`) |
| `search` | string | no | - | Search in username, email, display_name fields |

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/list-users/run?input%5Blimit%5D=10&input%5Brole%5D=subscriber'
```

---

## get-user

Get detailed information about a single user.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-user/run`

### Input

At least one identifier is required:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | - | User ID |
| `email` | string | - | Email address |
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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-user/run?input%5Bid%5D=1'
```

---

## create-user

Create a new user.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/create-user/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `username` | string | **yes** | - | Username |
| `email` | string | **yes** | - | Email address |
| `password` | string | no | auto-generated | Password |
| `display_name` | string | no | - | Display name |
| `first_name` | string | no | - | First name |
| `last_name` | string | no | - | Last name |
| `website` | string | no | - | Website URL |
| `role` | string | no | `subscriber` | Role |
| `send_notification` | boolean | no | `false` | Send welcome email |

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

> **Note:** The `password` field is only included in the response if it was auto-generated.

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-user/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"username":"newuser","email":"new@example.com","role":"editor"}}'
```

---

## update-user

Update an existing user.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/update-user/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | User ID |
| `email` | string | no | New email address |
| `display_name` | string | no | Display name |
| `first_name` | string | no | First name |
| `last_name` | string | no | Last name |
| `website` | string | no | Website URL |
| `password` | string | no | New password |
| `role` | string | no | New role |

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

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-user/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":18,"first_name":"Test","last_name":"User","role":"author"}}'
```

---

## delete-user

Delete a user.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/delete-user/run`

### Input

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | **yes** | User ID to delete |
| `reassign_to` | integer | no | Reassign content to this user ID |

### Output

```json
{
  "success": true,
  "message": "User deleted successfully",
  "deleted_id": 18,
  "reassigned": null
}
```

### Example

```bash
curl -s -u "user:pass" -X DELETE \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/delete-user/run?input%5Bid%5D=18&input%5Breassign_to%5D=1'
```

---

## reset-password

Reset a user's password.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/reset-password/run`

### Input

At least one identifier is required:

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | - | - | User ID |
| `email` | string | - | - | Email address |
| `login` | string | - | - | Username |
| `new_password` | string | no | auto-generated | New password |
| `send_notification` | boolean | no | `true` | Send email with new password |

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

> **Note:** The `password` field is only included if it was auto-generated.

### Example

```bash
curl -s -u "user:pass" -X POST \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/reset-password/run' \
  -H "Content-Type: application/json" \
  -d '{"input":{"id":18,"send_notification":false}}'
```

---

## get-roles

List available roles.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/get-roles/run`

### Input

No required parameters.

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

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-roles/run'
```
