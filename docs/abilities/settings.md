# Settings

Abilities for managing WordPress settings.

## Abilities

### General Settings
| Ability | Description | Method |
|---------|-------------|--------|
| get-general-settings | Get general settings | GET |
| update-general-settings | Update general settings | POST |

### Reading Settings
| Ability | Description | Method |
|---------|-------------|--------|
| get-reading-settings | Get reading settings | GET |
| update-reading-settings | Update reading settings | POST |

### Discussion Settings
| Ability | Description | Method |
|---------|-------------|--------|
| get-discussion-settings | Get discussion settings | GET |
| update-discussion-settings | Update discussion settings | POST |

### Permalink Settings
| Ability | Description | Method |
|---------|-------------|--------|
| get-permalink-settings | Get permalink settings | GET |
| update-permalink-settings | Update permalink settings | POST |

---

## General Settings

### get-general-settings

Get general WordPress settings (site title, tagline, email, etc.).

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-general-settings/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| settings | object | Settings |
| settings.blogname | string | Site title |
| settings.blogdescription | string | Tagline |
| settings.siteurl | string | WordPress address (URL) |
| settings.home | string | Site address (URL) |
| settings.admin_email | string | Administrator email |
| settings.users_can_register | string | Registration enabled (0/1) |
| settings.default_role | string | Default role for new users |
| settings.timezone_string | string | Timezone (e.g., Europe/Budapest) |
| settings.date_format | string | Date format |
| settings.time_format | string | Time format |
| settings.start_of_week | string | Week starts on (0=Sunday, 1=Monday) |
| settings.WPLANG | string | Language code |
| settings.site_language | string | Current language |
| settings.available_roles | array | List of available roles |

#### Example

**Request:**
```bash
curl -X GET "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/get-general-settings/run" \
  -u "user:application_password"
```

**Response:**
```json
{
  "success": true,
  "settings": {
    "blogname": "Stanton Test Site",
    "blogdescription": "Test description",
    "siteurl": "https://example.com",
    "home": "https://example.com",
    "admin_email": "admin@example.com",
    "users_can_register": "0",
    "default_role": "subscriber",
    "timezone_string": "Europe/Budapest",
    "date_format": "F j, Y",
    "time_format": "g:i a",
    "start_of_week": "1",
    "WPLANG": "",
    "site_language": "en_US",
    "available_roles": [
      "administrator",
      "editor",
      "author",
      "contributor",
      "subscriber"
    ]
  }
}
```

---

### update-general-settings

Update general WordPress settings.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-general-settings/run`

#### Input Schema

| Field | Type | Description |
|-------|------|-------------|
| blogname | string | Site title |
| blogdescription | string | Tagline |
| admin_email | string | Administrator email |
| users_can_register | boolean | Enable registration |
| default_role | string | Default role for new users |
| timezone_string | string | Timezone (e.g., Europe/Budapest) |
| date_format | string | Date format (e.g., Y-m-d) |
| time_format | string | Time format (e.g., H:i) |
| start_of_week | integer | Week starts on (0-6) |
| WPLANG | string | Language code |

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| message | string | Status message |
| updated | array | List of updated settings |
| failed | array | Failed updates |

#### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-general-settings/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"blogname":"New site title","blogdescription":"New tagline","timezone_string":"Europe/Budapest"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 3 setting(s)",
  "updated": [
    "blogname",
    "blogdescription",
    "timezone_string"
  ],
  "failed": []
}
```

---

## Reading Settings

### get-reading-settings

Get reading settings.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-reading-settings/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| settings | object | Settings |
| settings.posts_per_page | string | Posts per page |
| settings.posts_per_rss | string | RSS items count |
| settings.rss_use_excerpt | string | Use RSS excerpt (0/1) |
| settings.show_on_front | string | Homepage display (posts/page) |
| settings.page_on_front | string | Homepage ID |
| settings.page_for_posts | string | Posts page ID |
| settings.blog_public | string | Search engine indexing (0/1) |
| settings.homepage_title | string | Homepage title (if set) |
| settings.posts_page_title | string | Posts page title (if set) |

#### Example

**Response:**
```json
{
  "success": true,
  "settings": {
    "posts_per_page": "10",
    "posts_per_rss": "10",
    "rss_use_excerpt": "0",
    "show_on_front": "posts",
    "page_on_front": "0",
    "page_for_posts": "0",
    "blog_public": "1"
  }
}
```

---

### update-reading-settings

Update reading settings.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-reading-settings/run`

#### Input Schema

| Field | Type | Description |
|-------|------|-------------|
| posts_per_page | integer | Posts per page (1-100) |
| posts_per_rss | integer | RSS items count (1-100) |
| rss_use_excerpt | boolean | Use RSS excerpt |
| show_on_front | string | Homepage display ("posts" or "page") |
| page_on_front | integer | Homepage ID |
| page_for_posts | integer | Posts page ID |
| blog_public | boolean | Discourage search engine indexing |

---

## Discussion Settings

### get-discussion-settings

Get discussion settings.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-discussion-settings/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| settings | object | Settings |
| settings.default_pingback_flag | string | Enable pingback |
| settings.default_ping_status | string | Ping status (open/closed) |
| settings.default_comment_status | string | Comment status (open/closed) |
| settings.require_name_email | string | Require name and email |
| settings.comment_registration | string | Registration required for comments |
| settings.close_comments_for_old_posts | string | Close comments on old posts |
| settings.close_comments_days_old | string | Days before closing comments |
| settings.thread_comments | string | Threaded comments |
| settings.thread_comments_depth | string | Threading depth |
| settings.page_comments | string | Paginate comments |
| settings.comments_per_page | string | Comments per page |
| settings.default_comments_page | string | Default page (newest/oldest) |
| settings.comment_order | string | Order (asc/desc) |
| settings.comments_notify | string | Email notification |
| settings.moderation_notify | string | Moderation notification |
| settings.comment_moderation | string | Manual approval |
| settings.comment_previously_approved | string | Previously approved author |
| settings.moderation_keys | string | Moderation keywords |
| settings.disallowed_keys | string | Disallowed keywords |
| settings.show_avatars | string | Show avatars |
| settings.avatar_rating | string | Avatar rating (G/PG/R/X) |
| settings.avatar_default | string | Default avatar |

#### Example

**Response:**
```json
{
  "success": true,
  "settings": {
    "default_pingback_flag": "1",
    "default_ping_status": "open",
    "default_comment_status": "open",
    "require_name_email": "1",
    "comment_registration": "0",
    "close_comments_for_old_posts": "0",
    "close_comments_days_old": "14",
    "thread_comments": "1",
    "thread_comments_depth": "5",
    "page_comments": "0",
    "comments_per_page": "50",
    "default_comments_page": "newest",
    "comment_order": "asc",
    "comments_notify": "1",
    "moderation_notify": "1",
    "comment_moderation": "0",
    "comment_previously_approved": "1",
    "moderation_keys": "",
    "disallowed_keys": "",
    "show_avatars": "1",
    "avatar_rating": "G",
    "avatar_default": "mystery"
  }
}
```

---

### update-discussion-settings

Update discussion settings.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-discussion-settings/run`

#### Input Schema

| Field | Type | Description |
|-------|------|-------------|
| default_pingback_flag | boolean | Enable pingback |
| default_ping_status | string | Ping status ("open" / "closed") |
| default_comment_status | string | Comment status ("open" / "closed") |
| require_name_email | boolean | Require name and email |
| comment_registration | boolean | Registration required |
| close_comments_for_old_posts | boolean | Close comments on old posts |
| close_comments_days_old | integer | Days count |
| thread_comments | boolean | Threaded comments |
| thread_comments_depth | integer | Threading depth (1-10) |
| page_comments | boolean | Paginate comments |
| comments_per_page | integer | Comments per page |
| default_comments_page | string | Default page ("newest" / "oldest") |
| comment_order | string | Order ("asc" / "desc") |
| comments_notify | boolean | Email notification |
| moderation_notify | boolean | Moderation notification |
| comment_moderation | boolean | Manual approval |
| comment_previously_approved | boolean | Previously approved author |
| moderation_keys | string | Moderation keywords (one per line) |
| disallowed_keys | string | Disallowed keywords (one per line) |
| show_avatars | boolean | Show avatars |
| avatar_rating | string | Avatar rating ("G" / "PG" / "R" / "X") |
| avatar_default | string | Default avatar |

---

## Permalink Settings

### get-permalink-settings

Get permalink settings.

**Endpoint:** `GET /wp-json/wp-abilities/v1/abilities/site-manager/get-permalink-settings/run`

#### Output Schema

| Field | Type | Description |
|-------|------|-------------|
| success | boolean | Operation success |
| settings | object | Settings |
| settings.permalink_structure | string | Permalink structure |
| settings.category_base | string | Category base |
| settings.tag_base | string | Tag base |
| settings.common_structures | object | Common structures |

#### Example

**Response:**
```json
{
  "success": true,
  "settings": {
    "permalink_structure": "/%postname%/",
    "category_base": "",
    "tag_base": "",
    "common_structures": {
      "plain": "",
      "day_name": "/%year%/%monthnum%/%day%/%postname%/",
      "month_name": "/%year%/%monthnum%/%postname%/",
      "numeric": "/archives/%post_id%",
      "post_name": "/%postname%/"
    }
  }
}
```

---

### update-permalink-settings

Update permalink settings.

**Endpoint:** `POST /wp-json/wp-abilities/v1/abilities/site-manager/update-permalink-settings/run`

#### Input Schema

| Field | Type | Description |
|-------|------|-------------|
| permalink_structure | string | Permalink structure (e.g., /%postname%/) |
| category_base | string | Category base |
| tag_base | string | Tag base |

#### Example

**Request:**
```bash
curl -X POST "https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/update-permalink-settings/run" \
  -u "user:application_password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"permalink_structure":"/%postname%/"}}'
```

**Response:**
```json
{
  "success": true,
  "message": "Updated 1 setting(s)",
  "updated": [
    "permalink_structure"
  ],
  "failed": []
}
```

---

## Notes

- The `siteurl` and `home` fields cannot be modified via API for security reasons
- Modifying permalink structure automatically regenerates rewrite rules
- The `admin_email` field is validated - only valid email addresses are accepted
- The `default_role` can only be set to an existing role
