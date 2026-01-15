# WordPress Abilities API

## Mi az az Abilities API?

A WordPress Abilities API egy WordPress 6.9-ben bevezetett új funkció, amely lehetővé teszi, hogy a WordPress oldalak strukturált, felfedezhetó és biztonságos műveleteket tegyenek elérhetővé külső rendszerek (AI asszisztensek, automatizációs eszközök, távoli adminisztrációs panelek) számára.

Az Abilities API lényegében egy **standardizált interfész**, amelyen keresztül:
- AI rendszerek (Claude, ChatGPT, Gemini) képesek megérteni és végrehajtani WordPress műveleteket
- Automatizációs eszközök (n8n, Make, Zapier) integrálódhatnak a WordPress-szel
- Központi kezelőfelületek több WordPress oldalt is irányíthatnak egységes módon

## Miért jobb mint a REST API?

| Tulajdonság | REST API | Abilities API |
|-------------|----------|---------------|
| **Felfedezhetőség** | Dokumentációt kell olvasni | Önleíró - JSON Schema-val |
| **Validáció** | Egyedi implementáció | Beépített input/output validáció |
| **Jogosultságok** | Capability-alapú | Ability-szintű finomhangolás |
| **AI-integráció** | Nincs natív támogatás | MCP-kompatibilis, AI-ready |
| **Annotációk** | Nincs | readonly, destructive, idempotent |

## Hogyan működik?

### 1. Ability regisztráció

Egy ability egy jól definiált művelet, amelynek van:
- **Neve** (pl. `site-manager/create-backup`)
- **Leírása** (mit csinál)
- **Input sémája** (milyen paramétereket vár - JSON Schema)
- **Output sémája** (mit ad vissza - JSON Schema)
- **Végrehajtó függvénye** (a tényleges logika)
- **Jogosultság-ellenőrzése** (ki futtathatja)
- **Metaadatai** (REST-ben elérhető-e, destruktív-e, stb.)

```php
wp_register_ability( 'site-manager/create-backup', [
    'label'       => 'Create Backup',
    'description' => 'Create a full site backup',
    'category'    => 'maintenance',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'include_database' => [ 'type' => 'boolean', 'default' => true ],
            'include_files'    => [ 'type' => 'boolean', 'default' => true ],
        ],
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'success'   => [ 'type' => 'boolean' ],
            'backup_id' => [ 'type' => 'string' ],
            'message'   => [ 'type' => 'string' ],
        ],
    ],
    'execute_callback'    => [ BackupManager::class, 'create_backup' ],
    'permission_callback' => fn() => current_user_can( 'manage_options' ),
    'meta' => [
        'show_in_rest' => true,
        'annotations'  => [
            'readonly'    => false,
            'destructive' => false,
            'idempotent'  => false,
        ],
    ],
]);
```

### 2. REST API végpontok

Ha `show_in_rest => true`, az ability automatikusan elérhető lesz:

| Művelet | Végpont |
|---------|---------|
| Összes ability listázása | `GET /wp-json/wp-abilities/v1/abilities` |
| Egy ability lekérése | `GET /wp-json/wp-abilities/v1/abilities/{name}` |
| Ability végrehajtása | `POST /wp-json/wp-abilities/v1/abilities/{name}/run` |

### 3. Végrehajtás

```bash
curl -X POST \
  -u "user:application-password" \
  -H "Content-Type: application/json" \
  -d '{"input":{"include_database":true,"include_files":true}}' \
  https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/create-backup/run
```

## Annotációk jelentése

| Annotáció | Jelentés |
|-----------|----------|
| `readonly: true` | Csak olvas, nem módosít semmit (GET kérés) |
| `destructive: true` | Adatvesztést vagy visszafordíthatatlan változást okozhat |
| `idempotent: true` | Többszöri futtatás ugyanazt az eredményt adja |

Ezek az annotációk segítenek az AI rendszereknek és automatizációs eszközöknek megérteni, hogy egy művelet mennyire "veszélyes", és szükséges-e megerősítést kérni a felhasználótól.

## AI és MCP integráció

Az Abilities API-t úgy tervezték, hogy natívan illeszkedjen az AI asszisztensek világába:

### Model Context Protocol (MCP)

Az MCP egy nyílt szabvány, amelyet az Anthropic fejlesztett ki az AI modellek és külső rendszerek közötti kommunikációra. Az Abilities API tökéletesen illeszkedik ehhez:

```
+-------------+     MCP      +-----------------+     REST     +-------------+
|   Claude    | <----------> |  MCP Adapter    | <----------> |  WordPress  |
|   (AI)      |              |                 |              |  Abilities  |
+-------------+              +-----------------+              +-------------+
```

### Hogyan használja egy AI?

1. **Felfedezés**: Az AI lekéri az elérhető ability-ket
2. **Megértés**: A JSON Schema-ból megérti, milyen paraméterek kellenek
3. **Validáció**: Az annotációkból tudja, hogy kell-e megerősítést kérni
4. **Végrehajtás**: Meghívja az ability-t a megfelelő paraméterekkel
5. **Feldolgozás**: A strukturált válaszból kinyeri az információt

## WP Site Manager Plugin

Ez a projekt egy WordPress plugin, amely az Abilities API-t használva komplett site management funkciókat biztosít:

### Kategóriák

| Kategória | Leírás | Példa ability-k |
|-----------|--------|-----------------|
| **maintenance** | Karbantartás | backup, cache flush, DB optimalizálás |
| **diagnostics** | Diagnosztika | health check, error log |
| **updates** | Frissítések | plugin/téma/core update |
| **plugins** | Bővítmények | list, activate, deactivate, install |
| **themes** | Témák | list, activate, install |
| **users** | Felhasználók | CRUD, role management |
| **content** | Tartalom | posts, pages, comments, media |
| **settings** | Beállítások | general, reading, discussion, permalinks |
| **wc-products** | WooCommerce termékek | CRUD, stock, variations |
| **wc-orders** | WooCommerce rendelések | list, status update, refunds |
| **wc-reports** | WooCommerce riportok | sales, top sellers, revenue |

### Telepítés

```bash
composer require wordpress/abilities-api
```

A plugin automatikusan regisztrálja az összes ability-t a `wp_abilities_api_init` hook-on.

### Hitelesítés

Az API Application Password-öt használ:

1. WordPress admin > Users > Profil
2. Application Passwords szekció
3. Új jelszó generálása
4. Használat: `curl -u "username:xxxx-xxxx-xxxx-xxxx"`

## Dokumentáció

Részletes ability dokumentáció:

- [Posts (Bejegyzések)](posts.md)
- [Pages (Oldalak)](pages.md)
- [Comments (Hozzászólások)](comments.md)
- [Media (Média)](media.md)
- [Users (Felhasználók)](user-management.md)
- [Settings (Beállítások)](settings.md)
- [Maintenance (Karbantartás)](maintenance.md)
- [Plugins (Bővítmények)](plugin-management.md)
- [Themes (Témák)](theme-management.md)
- [Categories (Kategóriák)](categories.md)
- [Tags (Címkék)](tags.md)
- [Meta](meta.md)
- [WooCommerce](woocommerce.md)

## Hivatalos források

- [Introducing the WordPress Abilities API](https://developer.wordpress.org/news/2025/11/introducing-the-wordpress-abilities-api/)
- [Abilities API - Common APIs Handbook](https://developer.wordpress.org/apis/abilities-api/)
- [Abilities API in WordPress 6.9](https://make.wordpress.org/core/2025/11/10/abilities-api-in-wordpress-6-9/)
- [@wordpress/abilities Package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-abilities/)
- [GitHub: WordPress/abilities-api](https://github.com/WordPress/abilities-api)
