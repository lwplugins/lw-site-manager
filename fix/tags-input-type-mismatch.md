# Bug Report: Tags input típus eltérés a dokumentáció és implementáció között

## Érintett ability-k
- `site-manager/create-post`
- `site-manager/update-post`

## Probléma összefoglalása

A `tags` mező dokumentációja szerint integer ID-kat is elfogad, de a tényleges implementáció csak string típusú tag neveket/slugokat kezel helyesen.

## Dokumentáció

### create-post
```
| tags | array | nem | - | Tag nevek vagy ID-k |
```

### update-post
```
| tags | array | nem | Tag nevek |
```

## Várt működés (dokumentáció alapján)

```bash
# Integer ID-kkal
curl -X POST .../create-post/run \
  -d '{"input":{"title":"Test","tags":[28,29,30]}}'

# Eredmény: A 28, 29, 30 ID-jú címkék hozzárendelése a posthoz
```

## Tényleges működés

### 1. Integer ID-k küldése
```bash
curl -X POST .../create-post/run \
  -d '{"input":{"title":"Test","tags":[28,29,30]}}'
```

**Eredmény:** Hiba
```json
{
  "code": "ability_invalid_input",
  "message": "A \"site-manager/create-post\" képesség érvénytelen bemenettel rendelkezik. Ok: input[tags][0] nem string típus."
}
```

### 2. String-gé konvertált ID-k küldése
```bash
curl -X POST .../create-post/run \
  -d '{"input":{"title":"Test","tags":["28","29","30"]}}'
```

**Eredmény:** Új címkék jönnek létre "28", "29", "30" nevekkel, ahelyett hogy a meglévő 28, 29, 30 ID-jú címkéket rendelné hozzá.

```json
{
  "tags": [
    {"id": 32, "name": "28", "slug": "28"},
    {"id": 33, "name": "29", "slug": "29"},
    {"id": 34, "name": "30", "slug": "30"}
  ]
}
```

### 3. Tag nevek/slugok küldése (workaround)
```bash
curl -X POST .../create-post/run \
  -d '{"input":{"title":"Test","tags":["php","php-8-5","backend"]}}'
```

**Eredmény:** Működik, de ez nincs dokumentálva egyértelműen.

## Reprodukálás lépései

1. Hozz létre egy címkét manuálisan (pl. "php" - ID: 28)
2. Próbálj létrehozni egy postot az ID-val:
   ```json
   {"input":{"title":"Test","tags":[28]}}
   ```
3. Hibát kapsz: "nem string típus"
4. Próbáld string-ként:
   ```json
   {"input":{"title":"Test","tags":["28"]}}
   ```
5. Új "28" nevű címke jön létre

## Javasolt javítás

### 1. opció: Implementáció javítása (ajánlott)
A `tags` mező kezelje mindkét típust:
- Integer → Tag ID lookup
- String → Tag név/slug lookup (ha nem létezik, létrehozás)

```php
foreach ($tags as $tag) {
    if (is_int($tag)) {
        // ID alapján keresés
        $term = get_term($tag, 'post_tag');
    } else {
        // Név/slug alapján keresés vagy létrehozás
        $term = get_term_by('slug', $tag, 'post_tag')
             ?: get_term_by('name', $tag, 'post_tag');
        if (!$term) {
            // Új tag létrehozása
            $term = wp_insert_term($tag, 'post_tag');
        }
    }
}
```

### 2. opció: Dokumentáció javítása
Ha csak string támogatás marad, a dokumentációt frissíteni kell:

```
| tags | array[string] | nem | - | Tag nevek vagy slugok (stringként) |
```

## Érintett fájlok (valószínűleg)
- `includes/abilities/class-posts-abilities.php` - create_post(), update_post() metódusok
- `includes/schema/posts-schema.php` - input validációs séma

## Prioritás
Közepes - workaround elérhető (slug használata), de a dokumentáció félrevezető.

## Környezet
- WordPress: 6.9
- wp-site-manager: (verzió)
- PHP: 8.5
