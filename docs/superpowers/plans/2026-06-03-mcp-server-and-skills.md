# Built-in MCP Server + Bundled Skills — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a built-in MCP server (`/wp-json/mcp/lw-site-manager`) and a bundled, static Skills library to LW Site Manager, fully backward-compatible, modelled on Novamira's architecture.

**Architecture:** Two additive subsystems under `src/Mcp/` and `src/Skills/`, bootstrapped from the main plugin class and guarded so a missing adapter never fatals. The MCP server is the official `wordpress/mcp-adapter` default server, re-branded; abilities are exposed via `meta.mcp.public`; a two-layer auth gate (transport `manage_options` + existing per-ability caps) protects it. Skills are `skills/<slug>/SKILL.md` directories surfaced through an overridden `discover-abilities` tool + a `site-manager/skill-get` ability. The work also relocates the dead `includes/Admin/` classes into `src/Admin/` and wires the admin menu.

**Tech Stack:** PHP 8.2, WordPress Abilities API, `wordpress/mcp-adapter ^0.5` (GPL-2.0-or-later), PHPUnit 9.6, WPCS.

---

## CRITICAL LICENSING CONSTRAINT

Novamira's source (`/Users/trueqap/Downloads/novamira/`) is **AGPL-3.0-or-later**. This plugin is **GPL-2.0-or-later**. You MUST NOT copy Novamira source code. Implement original code that follows the documented *architecture* (architecture/ideas are not copyrightable; literal code is). The `wordpress/mcp-adapter` library is GPL-2.0-or-later and safe to depend on.

## Verified adapter API facts (wordpress/mcp-adapter v0.5.0)

- `WP\MCP\Core\McpAdapter::instance()` boots the adapter and fires `mcp_adapter_init`. It auto-registers the default server (unless `mcp_adapter_create_default_server` filter returns false) and the three meta-tool abilities (`mcp-adapter/discover-abilities`, `mcp-adapter/get-ability-info`, `mcp-adapter/execute-ability`) on `wp_abilities_api_init`.
- The default server is configured via the `mcp_adapter_default_server_config` filter (`server_id`, `server_route`, `server_name`, `server_description`, `server_version`, `tools`, `resources`, `prompts`). Endpoint = `/wp-json/{server_route_namespace}/{server_route}`; namespace defaults to `mcp`.
- Abilities are discovered for MCP by `meta.mcp.public === true`; `meta.mcp.type` ∈ {`tool`,`resource`,`prompt`} (default `tool`). Tool-type abilities are invoked through `execute-ability`; resource/prompt-type are auto-registered natively.
- Transport auth for the default server: `HttpTransport::check_permission` calls `current_user_can( apply_filters('mcp_adapter_default_transport_permission_user_capability', 'read', $context) )`. Fail-closed.
- Tool-result post-processing: `mcp_adapter_tool_call_result` filter, 3 args `($result, array $args, string $tool_name)`; the execute tool's sanitized name is `mcp-adapter-execute-ability`.
- `wp_get_abilities()`, `wp_get_ability($name)`, `wp_register_ability()`, `wp_unregister_ability()` exist (Abilities API, WP ≥ 6.9).

## File structure

```
composer.json                                  modify (add dependency)
lw-site-manager.php                            modify (bootstrap Mcp + Skills; admin)
src/Mcp/Toggle.php                             create
src/Mcp/TransportGuard.php                     create
src/Mcp/ResultUnwrapper.php                    create
src/Mcp/DiscoverAbility.php                    create
src/Mcp/Server.php                             create
src/Mcp/Bootstrap.php                          create
src/Skills/Parser.php                          create
src/Skills/Sources.php                         create
src/Skills/BuiltInSource.php                   create
src/Skills/Catalog.php                         create
src/Skills/SkillGetAbility.php                 create
src/Skills/PromptAbilities.php                 create
src/Skills/Bootstrap.php                       create
src/Abilities/Registrars/AbstractAbilitiesRegistrar.php   modify (buildMeta → mcp.public)
src/Admin/ParentPage.php                       create (relocate from includes/Admin/)
src/Admin/NoticeManager.php                    create (relocate from includes/Admin/)
src/Admin/McpSettingsPage.php                  create
includes/Admin/ParentPage.php                  delete (after relocation)
includes/Admin/NoticeManager.php               delete (after relocation)
skills/site-health-triage/SKILL.md             create
skills/woocommerce-order-ops/SKILL.md          create
skills/safe-bulk-content/SKILL.md              create
tests/Unit/Skills/ParserTest.php               create
tests/Unit/Skills/SourcesTest.php              create
tests/Unit/Skills/BuiltInSourceTest.php        create
tests/Unit/Mcp/ToggleTest.php                  create
tests/Unit/Mcp/ResultUnwrapperTest.php         create
readme.txt / CHANGELOG.md                      modify (version bump)
```

**Conventions for every new file:** `declare(strict_types=1);`, namespace `LightweightPlugins\SiteManager\…`, ≤200 lines/class, ≤30 lines/method, type declarations on params+returns, `if ( ! defined( 'ABSPATH' ) ) { exit; }` guard, run `composer phpcbf` then `composer phpcs` (tabs, not spaces) before each commit. **Never push without explicit consent.**

---

## Phase 0 — Dependency

### Task 1: Add the MCP adapter dependency

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Require the library (pinned, pre-1.0)**

Run:
```bash
cd lw-site-manager
composer require wordpress/mcp-adapter:^0.5
```
Expected: adds `wordpress/mcp-adapter` and transitive `wordpress/php-mcp-schema` under `require`, updates `composer.lock`, installs into `vendor/`.

- [ ] **Step 2: Verify the adapter class loads**

Run:
```bash
php -r "require 'vendor/autoload.php'; var_dump(class_exists('WP\\\\MCP\\\\Core\\\\McpAdapter'));"
```
Expected: `bool(true)`

- [ ] **Step 3: Confirm `.distignore` still ships vendor/**

Run: `grep -c '^vendor' .distignore`
Expected: `0` (vendor is NOT ignored → ships in ZIP). If non-zero, stop and fix.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: add wordpress/mcp-adapter dependency"
```

---

## Phase 1 — Skills subsystem (self-contained; testable without MCP)

### Task 2: SKILL.md frontmatter Parser

**Files:**
- Create: `src/Skills/Parser.php`
- Test: `tests/Unit/Skills/ParserTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {

	public function test_parses_frontmatter_fields(): void {
		$raw = "---\nname: my-skill\ndescription: Do a thing\nenable_prompt: true\n---\n\nBody here.";
		$p = Parser::parse( $raw );
		$this->assertSame( 'my-skill', $p['name'] );
		$this->assertSame( 'Do a thing', $p['description'] );
		$this->assertTrue( $p['enable_prompt'] );
		$this->assertTrue( $p['enable_agentic'] ); // default true
		$this->assertSame( 'Body here.', $p['body'] );
		$this->assertNull( $p['parse_error'] );
	}

	public function test_no_frontmatter_keeps_whole_body(): void {
		$p = Parser::parse( "Just a body." );
		$this->assertSame( '', $p['name'] );
		$this->assertSame( 'Just a body.', $p['body'] );
	}

	public function test_unterminated_frontmatter_reports_error(): void {
		$p = Parser::parse( "---\nname: x\nno closing" );
		$this->assertNotNull( $p['parse_error'] );
	}

	public function test_normalize_slug(): void {
		$this->assertSame( 'my-skill', Parser::normalize_slug( 'My Skill!' ) );
		$this->assertSame( '', Parser::normalize_slug( '   ' ) );
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:unit -- --filter ParserTest`
Expected: FAIL (`Parser` not found).

- [ ] **Step 3: Implement Parser**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lenient SKILL.md frontmatter parser. Original implementation.
 */
final class Parser {

	public const MAX_BODY_BYTES = 1048576; // 1 MB sanity cap.

	/**
	 * @return array{name:string,description:string,enable_prompt:bool,enable_agentic:bool,body:string,parse_error:?string}
	 */
	public static function parse( string $raw ): array {
		$result = [
			'name'           => '',
			'description'    => '',
			'enable_prompt'  => false,
			'enable_agentic' => true,
			'body'           => $raw,
			'parse_error'    => null,
		];

		$normalized = (string) preg_replace( '/\r\n?/', "\n", $raw );
		if ( ! str_starts_with( $normalized, "---\n" ) ) {
			return $result;
		}

		$closing = strpos( $normalized, "\n---\n", 4 );
		if ( false === $closing && str_ends_with( $normalized, "\n---" ) ) {
			$closing = strlen( $normalized ) - 4;
		}
		if ( false === $closing ) {
			$result['parse_error'] = 'Frontmatter opened with --- but never closed.';
			return $result;
		}

		$front          = substr( $normalized, 4, $closing - 4 );
		$result['body'] = ltrim( substr( $normalized, $closing + 5 ), "\n" );
		self::apply_frontmatter( $front, $result );
		return $result;
	}

	/**
	 * @param array{name:string,description:string,enable_prompt:bool,enable_agentic:bool,body:string,parse_error:?string} $result
	 */
	private static function apply_frontmatter( string $front, array &$result ): void {
		foreach ( explode( "\n", $front ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			$colon = strpos( $line, ':' );
			if ( false === $colon ) {
				continue;
			}
			$key   = strtolower( trim( substr( $line, 0, $colon ) ) );
			$value = trim( substr( $line, $colon + 1 ), " \t\"'" );
			match ( $key ) {
				'name'           => $result['name'] = $value,
				'description'    => $result['description'] = $value,
				'enable_prompt'  => $result['enable_prompt'] = self::to_bool( $value, false ),
				'enable_agentic' => $result['enable_agentic'] = self::to_bool( $value, true ),
				default          => null,
			};
		}
	}

	private static function to_bool( string $value, bool $fallback ): bool {
		$parsed = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return null === $parsed ? $fallback : $parsed;
	}

	public static function normalize_slug( string $raw ): string {
		$slug = sanitize_title( $raw );
		if ( strlen( $slug ) > 60 ) {
			$slug = rtrim( substr( $slug, 0, 60 ), '-' );
		}
		return $slug;
	}

	/**
	 * @param array{slug?:string,description?:string,enable_prompt?:bool,enable_agentic?:bool,content?:string} $skill
	 */
	public static function render_skill_md( array $skill ): string {
		return sprintf(
			"---\nname: %s\ndescription: %s\nenable_prompt: %s\nenable_agentic: %s\n---\n\n%s",
			$skill['slug'] ?? '',
			str_replace( "\n", ' ', $skill['description'] ?? '' ),
			( $skill['enable_prompt'] ?? false ) ? 'true' : 'false',
			( $skill['enable_agentic'] ?? true ) ? 'true' : 'false',
			$skill['content'] ?? ''
		);
	}
}
```

> Note: `sanitize_title` is a WP function — the unit test environment must load WP test stubs (the existing `tests/` bootstrap already does, since the project ships `phpunit.xml.dist`). If `ParserTest` cannot resolve `sanitize_title`, move the slug test to `tests/Integration/`.

- [ ] **Step 4: Run, verify pass**

Run: `composer test:unit -- --filter ParserTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Skills/Parser.php tests/Unit/Skills/ParserTest.php
git commit -m "feat(skills): add SKILL.md frontmatter parser"
```

---

### Task 3: BuiltInSource + Sources registry + bundled dir

**Files:**
- Create: `src/Skills/BuiltInSource.php`, `src/Skills/Sources.php`
- Create: `skills/.gitkeep` (placeholder so the dir exists; real skills added in Task 7)
- Test: `tests/Unit/Skills/SourcesTest.php`, `tests/Unit/Skills/BuiltInSourceTest.php`

- [ ] **Step 1: Write failing tests**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Sources;
use PHPUnit\Framework\TestCase;

final class SourcesTest extends TestCase {

	public function test_find_returns_null_when_no_source_has_slug(): void {
		add_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
		$this->assertNull( Sources::find( 'nope' ) );
		remove_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
	}

	public function test_sources_are_priority_sorted_and_annotated(): void {
		$loader = static fn(): array => [ [
			'slug' => 'demo', 'name' => 'Demo', 'description' => 'd',
			'content' => 'body', 'enable_prompt' => false, 'enable_agentic' => true,
		] ];
		$cb = static function ( array $s ) use ( $loader ): array {
			$s['t'] = [ 'id' => 't', 'priority' => 5, 'label' => 'Test', 'loader' => $loader ];
			return $s;
		};
		add_filter( 'lw_site_manager_skill_sources', $cb );
		$found = Sources::find( 'demo' );
		$this->assertSame( 't', $found['source'] );
		$this->assertSame( 'Test', $found['source_label'] );
		$this->assertContains( 'demo', array_column( Sources::discoverable( 'agentic' ), 'slug' ) );
		remove_filter( 'lw_site_manager_skill_sources', $cb );
	}
}
```

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\BuiltInSource;
use PHPUnit\Framework\TestCase;

final class BuiltInSourceTest extends TestCase {

	public function test_loads_bundled_skill_directories(): void {
		$skills = BuiltInSource::load();
		$slugs  = array_column( $skills, 'slug' );
		// At least the three starter skills (added in Task 7) are present once that task lands.
		$this->assertIsArray( $skills );
		foreach ( $skills as $s ) {
			$this->assertArrayHasKey( 'slug', $s );
			$this->assertArrayHasKey( 'content', $s );
			$this->assertNotSame( '', $s['content'] );
		}
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:unit -- --filter "SourcesTest|BuiltInSourceTest"`
Expected: FAIL (classes not found).

- [ ] **Step 3: Implement BuiltInSource**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the statically bundled skill library: skills/<slug>/SKILL.md.
 */
final class BuiltInSource {

	public const SOURCE_ID    = 'built-in';
	public const SOURCE_LABEL = 'Built-in';
	public const PRIORITY     = 10;

	/**
	 * Register with the source registry. Hooked on `lw_site_manager_skill_sources`.
	 *
	 * @param array<string,array<string,mixed>> $sources
	 * @return array<string,array<string,mixed>>
	 */
	public static function register( array $sources ): array {
		$sources[ self::SOURCE_ID ] = [
			'id'       => self::SOURCE_ID,
			'priority' => self::PRIORITY,
			'label'    => self::SOURCE_LABEL,
			'loader'   => [ self::class, 'load' ],
		];
		return $sources;
	}

	/**
	 * @return list<array{slug:string,name:string,description:string,content:string,enable_prompt:bool,enable_agentic:bool}>
	 */
	public static function load(): array {
		static $cache = null;
		if ( is_array( $cache ) ) {
			return $cache;
		}

		$dir   = self::dir();
		$files = is_dir( $dir ) ? glob( $dir . '/*/SKILL.md' ) : [];
		$out   = [];

		foreach ( (array) $files as $path ) {
			$slug = Parser::normalize_slug( basename( dirname( $path ) ) );
			if ( '' === $slug ) {
				continue;
			}
			$raw = file_get_contents( $path );
			if ( false === $raw ) {
				continue;
			}
			$parsed = Parser::parse( $raw );
			if ( null !== $parsed['parse_error'] || '' === trim( $parsed['body'] ) ) {
				continue;
			}
			$out[] = [
				'slug'           => $slug,
				'name'           => '' !== $parsed['name'] ? $parsed['name'] : $slug,
				'description'    => $parsed['description'],
				'content'        => $parsed['body'],
				'enable_prompt'  => $parsed['enable_prompt'],
				'enable_agentic' => $parsed['enable_agentic'],
			];
		}

		$cache = $out;
		return $out;
	}

	private static function dir(): string {
		return rtrim( LW_SITE_MANAGER_DIR, '/' ) . '/skills';
	}
}
```

- [ ] **Step 4: Implement Sources**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter-based skill source registry. Other plugins may add sources via
 * the `lw_site_manager_skill_sources` filter.
 */
final class Sources {

	/**
	 * @return list<array{id:string,priority:int,label:string,loader:callable}>
	 */
	public static function registry(): array {
		/** @var array<string,array{id:string,priority:int,label:string,loader:callable}> $sources */
		$sources = apply_filters( 'lw_site_manager_skill_sources', [] );
		$list    = array_values( $sources );
		usort( $list, static fn( array $a, array $b ): int => $a['priority'] <=> $b['priority'] );
		return $list;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public static function find( string $slug ): ?array {
		foreach ( self::registry() as $entry ) {
			foreach ( ( $entry['loader'] )() as $skill ) {
				if ( ( $skill['slug'] ?? '' ) === $slug ) {
					$skill['source']       = $entry['id'];
					$skill['source_label'] = $entry['label'];
					return $skill;
				}
			}
		}
		return null;
	}

	/**
	 * @return list<array<string,mixed>>
	 */
	public static function all(): array {
		$out = [];
		foreach ( self::registry() as $entry ) {
			foreach ( ( $entry['loader'] )() as $skill ) {
				$skill['source']       = $entry['id'];
				$skill['source_label'] = $entry['label'];
				$out[]                 = $skill;
			}
		}
		return $out;
	}

	/**
	 * @param 'agentic'|'prompt' $mode
	 * @return list<array<string,mixed>>
	 */
	public static function discoverable( string $mode ): array {
		$key     = 'agentic' === $mode ? 'enable_agentic' : 'enable_prompt';
		$default = 'agentic' === $mode;
		$out     = [];
		foreach ( self::all() as $skill ) {
			if ( '' === trim( (string) ( $skill['description'] ?? '' ) ) ) {
				continue;
			}
			if ( '' === trim( (string) ( $skill['content'] ?? '' ) ) ) {
				continue;
			}
			if ( ! ( $skill[ $key ] ?? $default ) ) {
				continue;
			}
			$out[] = $skill;
		}
		return $out;
	}
}
```

- [ ] **Step 5: Create the bundled dir placeholder**

```bash
mkdir -p skills && touch skills/.gitkeep
```

- [ ] **Step 6: Run tests**

Run: `composer test:unit -- --filter "SourcesTest|BuiltInSourceTest"`
Expected: PASS (BuiltInSourceTest passes trivially with an empty `skills/` dir; real skills land in Task 7).

- [ ] **Step 7: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Skills/BuiltInSource.php src/Skills/Sources.php skills/.gitkeep tests/Unit/Skills/SourcesTest.php tests/Unit/Skills/BuiltInSourceTest.php
git commit -m "feat(skills): bundled-source loader and filter-based registry"
```

---

### Task 4: `site-manager/skill-get` ability

**Files:**
- Create: `src/Skills/SkillGetAbility.php`
- Test: `tests/Integration/Skills/SkillGetAbilityTest.php` (integration — needs the Abilities API)

- [ ] **Step 1: Write failing integration test**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Integration\Skills;

use LightweightPlugins\SiteManager\Skills\BuiltInSource;
use LightweightPlugins\SiteManager\Skills\SkillGetAbility;
use WP_UnitTestCase;

final class SkillGetAbilityTest extends WP_UnitTestCase {

	public function test_unknown_slug_returns_not_found(): void {
		add_filter( 'lw_site_manager_skill_sources', [ BuiltInSource::class, 'register' ] );
		$result = SkillGetAbility::execute( [ 'slug' => 'does-not-exist' ] );
		$this->assertFalse( $result['found'] );
	}

	public function test_known_slug_returns_rendered_skill(): void {
		$loader = static fn(): array => [ [
			'slug' => 'demo', 'name' => 'Demo', 'description' => 'd',
			'content' => '# Body', 'enable_prompt' => false, 'enable_agentic' => true,
		] ];
		add_filter( 'lw_site_manager_skill_sources', static function ( array $s ) use ( $loader ): array {
			$s['t'] = [ 'id' => 't', 'priority' => 5, 'label' => 'Test', 'loader' => $loader ];
			return $s;
		} );
		$result = SkillGetAbility::execute( [ 'slug' => 'demo' ] );
		$this->assertTrue( $result['found'] );
		$this->assertStringContainsString( '# Body', $result['content'] );
		$this->assertSame( 't', $result['source'] );
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:integration -- --filter SkillGetAbilityTest`
Expected: FAIL (`SkillGetAbility` not found). (If the integration harness is not installed, run `composer install-wp-tests` first.)

- [ ] **Step 3: Implement SkillGetAbility**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `site-manager/skill-get` ability: load a bundled skill by slug.
 */
final class SkillGetAbility {

	public const NAME = 'site-manager/skill-get';

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		wp_register_ability(
			self::NAME,
			[
				'label'               => __( 'Get Skill', 'lw-site-manager' ),
				'description'         => __( 'Load a Site Manager skill by slug. Returns the full SKILL.md content plus metadata.', 'lw-site-manager' ),
				'category'            => 'skill',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'slug' => [
							'type'        => 'string',
							'description' => 'The slug of the skill to load.',
						],
					],
					'required'   => [ 'slug' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'found'       => [ 'type' => 'boolean' ],
						'slug'        => [ 'type' => 'string' ],
						'name'        => [ 'type' => 'string' ],
						'description' => [ 'type' => 'string' ],
						'content'     => [ 'type' => 'string' ],
						'source'      => [ 'type' => 'string' ],
					],
					'required'   => [ 'found' ],
				],
				'execute_callback'    => [ self::class, 'execute' ],
				'permission_callback' => [ self::class, 'can_run' ],
				'meta'                => [
					'annotations' => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'mcp'         => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}

	public static function can_run(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public static function execute( array $input ): array {
		$slug = self::normalize_slug( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return [ 'found' => false ];
		}
		$skill = Sources::find( $slug );
		if ( null === $skill ) {
			return [ 'found' => false ];
		}
		return [
			'found'       => true,
			'slug'        => (string) $skill['slug'],
			'name'        => (string) ( $skill['name'] ?? $skill['slug'] ),
			'description' => (string) ( $skill['description'] ?? '' ),
			'content'     => Parser::render_skill_md( $skill ),
			'source'      => (string) ( $skill['source'] ?? 'built-in' ),
		];
	}

	private static function normalize_slug( string $slug ): string {
		$slug = trim( $slug );
		if ( str_starts_with( $slug, 'site-manager/' ) ) {
			$slug = substr( $slug, strlen( 'site-manager/' ) );
		}
		return $slug;
	}
}
```

- [ ] **Step 4: Run, verify pass**

Run: `composer test:integration -- --filter SkillGetAbilityTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Skills/SkillGetAbility.php tests/Integration/Skills/SkillGetAbilityTest.php
git commit -m "feat(skills): site-manager/skill-get ability"
```

---

### Task 5: Catalog (skill list injected into discover instructions)

**Files:**
- Create: `src/Skills/Catalog.php`
- Test: `tests/Unit/Skills/CatalogTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Catalog;
use PHPUnit\Framework\TestCase;

final class CatalogTest extends TestCase {

	public function test_render_lists_slug_badge_and_description(): void {
		$md = Catalog::render( [
			[ 'slug' => 'demo', 'description' => 'Do a thing', 'source_label' => 'Built-in' ],
		] );
		$this->assertStringContainsString( '## Available Skills', $md );
		$this->assertStringContainsString( '`demo`', $md );
		$this->assertStringContainsString( '(Built-in)', $md );
		$this->assertStringContainsString( 'Do a thing', $md );
		$this->assertStringContainsString( 'site-manager/skill-get', $md );
	}

	public function test_inject_prepends_only_when_skills_exist(): void {
		$this->assertSame( 'orig', Catalog::inject( 'orig', [] ) );
		$out = Catalog::inject( 'orig', [
			[ 'slug' => 'demo', 'description' => 'd', 'source_label' => 'Built-in' ],
		] );
		$this->assertStringEndsWith( "orig", $out );
		$this->assertStringContainsString( '## Available Skills', $out );
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:unit -- --filter CatalogTest`
Expected: FAIL.

- [ ] **Step 3: Implement Catalog**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders + injects the "## Available Skills" catalog into the MCP
 * discover-abilities instructions string.
 */
final class Catalog {

	/**
	 * Filter callback for `lw_site_manager_discover_instructions`.
	 */
	public static function inject_filter( mixed $instructions ): mixed {
		if ( ! is_string( $instructions ) ) {
			return $instructions;
		}
		return self::inject( $instructions, Sources::discoverable( 'agentic' ) );
	}

	/**
	 * @param list<array<string,mixed>> $skills
	 */
	public static function inject( string $instructions, array $skills ): string {
		if ( [] === $skills ) {
			return $instructions;
		}
		return self::render( $skills ) . "\n" . $instructions;
	}

	/**
	 * @param list<array<string,mixed>> $skills
	 */
	public static function render( array $skills ): string {
		$lines = [
			'',
			'## Available Skills',
			'',
			'When a skill description matches the user request, call `site-manager/skill-get` with the slug to load its full instructions before starting work.',
			'',
		];
		foreach ( $skills as $skill ) {
			$lines[] = sprintf(
				'- **`%s`** *(%s)* — %s',
				(string) ( $skill['slug'] ?? '' ),
				(string) ( $skill['source_label'] ?? '' ),
				trim( (string) ( $skill['description'] ?? '' ) )
			);
		}
		$lines[] = '';
		return implode( "\n", $lines );
	}
}
```

- [ ] **Step 4: Run, verify pass**

Run: `composer test:unit -- --filter CatalogTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Skills/Catalog.php tests/Unit/Skills/CatalogTest.php
git commit -m "feat(skills): catalog renderer + discover-instructions injector"
```

---

### Task 6: PromptAbilities (native MCP prompts for enable_prompt skills)

**Files:**
- Create: `src/Skills/PromptAbilities.php`

- [ ] **Step 1: Implement (no unit test — thin registration glue, covered by manual smoke in Task 18)**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers one `site-manager/skill-prompt-{slug}` ability per skill that
 * opted in via `enable_prompt: true`, exposed natively as an MCP prompt.
 */
final class PromptAbilities {

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( Sources::discoverable( 'prompt' ) as $skill ) {
			self::register_one( $skill );
		}
	}

	/**
	 * @param array<string,mixed> $skill
	 */
	private static function register_one( array $skill ): void {
		$slug = (string) ( $skill['slug'] ?? '' );
		if ( '' === $slug ) {
			return;
		}
		$rendered = Parser::render_skill_md( $skill );
		wp_register_ability(
			'site-manager/skill-prompt-' . $slug,
			[
				'label'               => sprintf( /* translators: %s: skill name */ __( 'Skill: %s', 'lw-site-manager' ), (string) ( $skill['name'] ?? $slug ) ),
				'description'         => (string) ( $skill['description'] ?? '' ),
				'category'            => 'skill',
				'input_schema'        => [ 'type' => 'object', 'properties' => [] ],
				'execute_callback'    => static fn(): array => [
					'messages' => [
						[
							'role'    => 'user',
							'content' => [ 'type' => 'text', 'text' => $rendered ],
						],
					],
				],
				'permission_callback' => [ SkillGetAbility::class, 'can_run' ],
				'meta'                => [
					'annotations' => [ 'readonly' => true, 'destructive' => false, 'idempotent' => true ],
					'mcp'         => [ 'public' => true, 'type' => 'prompt' ],
				],
			]
		);
	}
}
```

- [ ] **Step 2: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Skills/PromptAbilities.php
git commit -m "feat(skills): optional native MCP prompt mode"
```

---

### Task 7: Bundled starter skills + Skills Bootstrap

**Files:**
- Create: `skills/site-health-triage/SKILL.md`, `skills/woocommerce-order-ops/SKILL.md`, `skills/safe-bulk-content/SKILL.md`
- Create: `src/Skills/Bootstrap.php`

- [ ] **Step 1: Write `skills/site-health-triage/SKILL.md`**

```markdown
---
name: site-health-triage
description: Diagnose a WordPress site's health using LW Site Manager abilities. Use when the user asks why the site is slow, broken, erroring, or "what's wrong with my site".
enable_prompt: false
enable_agentic: true
---

# Site health triage

You are diagnosing a WordPress site through LW Site Manager abilities. Work read-first; never run a destructive ability during triage.

## Steps

1. Call `site-manager/health-check` for the overall report (PHP/WP versions, disk, cron, https).
2. Call `site-manager/error-log` to read recent PHP errors. Quote the most recent fatal/parse errors verbatim.
3. Call `site-manager/check-updates` to list pending core/plugin/theme updates — outdated components are a common cause.
4. If updates were recently applied, call `site-manager/list-plugins` and look for unexpectedly deactivated plugins.

## Rules

- Report findings with the exact ability output; do not speculate beyond it.
- Recommend fixes, but do NOT call `site-manager/update-*`, `site-manager/cleanup-database`, or any backup/restore ability unless the user explicitly approves.
```

- [ ] **Step 2: Write `skills/woocommerce-order-ops/SKILL.md`**

```markdown
---
name: woocommerce-order-ops
description: Safely inspect and modify WooCommerce orders via LW Site Manager. Use when the user asks to look up an order, refund, change an order status, or report on sales.
enable_prompt: false
enable_agentic: true
---

# WooCommerce order operations

Operate on WooCommerce orders through LW Site Manager abilities. Treat customer data as sensitive.

## Reading

- `site-manager/wc-list-orders` to find orders (filter by status/customer/date). Do not page through the entire customer base to fish for data.
- `site-manager/wc-get-order` for one order's detail.

## Writing (confirm with the user first)

- `site-manager/wc-update-order-status` to move an order between statuses.
- `site-manager/wc-create-refund` to refund. Always confirm the amount and that it does not exceed the order total before calling.
- `site-manager/wc-mark-order-paid` only when the user confirms payment was received out-of-band.

## Rules

- Echo back the order id + amount and get explicit confirmation before any refund or paid-marking.
- Never expose more customer PII than needed to answer the question.
```

- [ ] **Step 3: Write `skills/safe-bulk-content/SKILL.md`**

```markdown
---
name: safe-bulk-content
description: Perform bulk post/page content operations safely via LW Site Manager. Use when the user asks to bulk edit, trash, publish, or reorganize many posts or pages.
enable_prompt: false
enable_agentic: true
---

# Safe bulk content operations

Bulk content changes are easy to get wrong. Always preview before mutating.

## Steps

1. `site-manager/list-posts` (or `site-manager/list-pages`) with the user's filter to get the exact set of ids.
2. Show the user the list (titles + ids) and get explicit confirmation of the target set.
3. Only then call `site-manager/bulk-posts` with the confirmed ids and action.
4. For deletes, prefer trash over permanent delete; mention `site-manager/restore-post` exists.

## Rules

- Never run `site-manager/bulk-posts` with a `publish` or `delete` action without showing the affected ids first.
- If the set is larger than ~50 items, confirm again — bulk mistakes scale.
```

- [ ] **Step 4: Implement Skills Bootstrap**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the Skills subsystem hooks. Safe to call unconditionally.
 */
final class Bootstrap {

	public static function init(): void {
		add_filter( 'lw_site_manager_skill_sources', [ BuiltInSource::class, 'register' ] );
		add_filter( 'lw_site_manager_discover_instructions', [ Catalog::class, 'inject_filter' ], 10 );

		add_action( 'wp_abilities_api_categories_init', [ self::class, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ SkillGetAbility::class, 'register' ], 999 );
		add_action( 'wp_abilities_api_init', [ PromptAbilities::class, 'register' ], 500 );
	}

	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			'skill',
			[
				'label'       => __( 'Skills', 'lw-site-manager' ),
				'description' => __( 'Load Site Manager skill playbooks.', 'lw-site-manager' ),
			]
		);
	}
}
```

- [ ] **Step 5: Verify BuiltInSourceTest now sees the three skills**

Run: `composer test:unit -- --filter BuiltInSourceTest`
Expected: PASS, and (optionally add an assertion) `assertContains('site-health-triage', $slugs)`.

- [ ] **Step 6: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add skills/ src/Skills/Bootstrap.php tests/Unit/Skills/BuiltInSourceTest.php
git commit -m "feat(skills): bundled starter skills + subsystem bootstrap"
```

---

## Phase 2 — MCP server subsystem

### Task 8: Toggle (enable option + domain-lock)

**Files:**
- Create: `src/Mcp/Toggle.php`
- Test: `tests/Unit/Mcp/ToggleTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\Toggle;
use WP_UnitTestCase;

final class ToggleTest extends WP_UnitTestCase {

	public function test_disabled_by_default(): void {
		$this->assertFalse( Toggle::is_enabled() );
	}

	public function test_enable_then_enabled_on_same_domain(): void {
		Toggle::enable();
		$this->assertTrue( Toggle::is_enabled() );
	}

	public function test_auto_disables_on_domain_change(): void {
		Toggle::enable();
		update_option( Toggle::OPTION_DOMAIN, 'https://old-domain.example' );
		$this->assertFalse( Toggle::is_enabled() ); // domain mismatch → off
		$this->assertFalse( (bool) get_option( Toggle::OPTION_ENABLED ) );
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:integration -- --filter ToggleTest`
Expected: FAIL (`Toggle` not found). (Toggle uses options → run under the integration harness.)

- [ ] **Step 3: Implement Toggle**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable/disable state for the MCP server, with a domain-lock that
 * auto-disables when the site URL changes (e.g. a staging clone).
 */
final class Toggle {

	public const OPTION_ENABLED = 'lw_site_manager_mcp_enabled';
	public const OPTION_DOMAIN  = 'lw_site_manager_mcp_domain';

	public static function is_enabled(): bool {
		if ( ! get_option( self::OPTION_ENABLED, false ) ) {
			return false;
		}
		$locked  = (string) get_option( self::OPTION_DOMAIN, '' );
		$current = self::current_domain();
		if ( '' !== $locked && $locked !== $current ) {
			self::disable(); // domain changed → fail safe.
			return false;
		}
		return true;
	}

	public static function enable(): void {
		update_option( self::OPTION_ENABLED, true );
		update_option( self::OPTION_DOMAIN, self::current_domain() );
	}

	public static function disable(): void {
		update_option( self::OPTION_ENABLED, false );
	}

	public static function locked_domain(): string {
		return (string) get_option( self::OPTION_DOMAIN, '' );
	}

	private static function current_domain(): string {
		return (string) wp_parse_url( get_site_url(), PHP_URL_HOST );
	}
}
```

- [ ] **Step 4: Run, verify pass**

Run: `composer test:integration -- --filter ToggleTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Mcp/Toggle.php tests/Unit/Mcp/ToggleTest.php
git commit -m "feat(mcp): enable toggle with domain-lock"
```

---

### Task 9: ResultUnwrapper

**Files:**
- Create: `src/Mcp/ResultUnwrapper.php`
- Test: `tests/Unit/Mcp/ResultUnwrapperTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\ResultUnwrapper;
use PHPUnit\Framework\TestCase;

final class ResultUnwrapperTest extends TestCase {

	public function test_passes_through_non_execute_tool(): void {
		$r = [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'x' ] ];
		$this->assertSame( $r, ResultUnwrapper::filter( $r, [], 'mcp-adapter-discover-abilities' ) );
	}

	public function test_unwraps_inner_error(): void {
		$r   = [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'boom', 'code' => 'E1' ] ];
		$out = ResultUnwrapper::filter( $r, [], 'mcp-adapter-execute-ability' );
		$this->assertFalse( $out['success'] );
		$this->assertStringContainsString( 'boom', $out['error'] );
		$this->assertStringContainsString( 'E1', $out['error'] ); // structured detail appended
	}

	public function test_passes_through_inner_success(): void {
		$r = [ 'success' => true, 'data' => [ 'success' => true, 'foo' => 1 ] ];
		$this->assertSame( $r, ResultUnwrapper::filter( $r, [], 'mcp-adapter-execute-ability' ) );
	}
}
```

- [ ] **Step 2: Run, verify failure**

Run: `composer test:unit -- --filter ResultUnwrapperTest`
Expected: FAIL.

- [ ] **Step 3: Implement ResultUnwrapper**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unwraps `{ success:true, data:{ success:false, error } }` from the adapter's
 * execute-ability dispatcher so logical failures surface as real MCP errors.
 * Hooked on `mcp_adapter_tool_call_result`.
 */
final class ResultUnwrapper {

	public static function filter( mixed $result, array $args, string $tool_name ): mixed {
		if ( 'mcp-adapter-execute-ability' !== $tool_name ) {
			return $result;
		}
		if ( ! is_array( $result ) || true !== ( $result['success'] ?? null ) ) {
			return $result;
		}
		$data = $result['data'] ?? null;
		if ( ! is_array( $data ) || false !== ( $data['success'] ?? null ) ) {
			return $result;
		}
		$error = $data['error'] ?? null;
		if ( ! is_string( $error ) || '' === trim( $error ) ) {
			return $result;
		}

		$detail = $data;
		unset( $detail['success'], $detail['error'] );
		if ( [] !== $detail ) {
			$encoded = wp_json_encode( $detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( is_string( $encoded ) ) {
				$data['error'] = $error . "\n\nStructured detail (JSON):\n" . $encoded;
			}
		}
		return $data;
	}
}
```

- [ ] **Step 4: Run, verify pass**

Run: `composer test:unit -- --filter ResultUnwrapperTest`
Expected: PASS.

- [ ] **Step 5: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Mcp/ResultUnwrapper.php tests/Unit/Mcp/ResultUnwrapperTest.php
git commit -m "feat(mcp): unwrap inner error results for honest MCP errors"
```

---

### Task 10: TransportGuard + DiscoverAbility override

**Files:**
- Create: `src/Mcp/TransportGuard.php`, `src/Mcp/DiscoverAbility.php`

- [ ] **Step 1: Implement TransportGuard**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transport-level capability gate for the MCP endpoint. Hooked on
 * `mcp_adapter_default_transport_permission_user_capability`.
 */
final class TransportGuard {

	public static function capability( mixed $capability, mixed $context = null ): string {
		/**
		 * Filter the minimum capability required to reach the MCP transport.
		 *
		 * @param string $cap Default 'manage_options'.
		 */
		$cap = (string) apply_filters( 'lw_site_manager_mcp_capability', 'manage_options', $context );
		return '' !== $cap ? $cap : 'manage_options';
	}
}
```

- [ ] **Step 2: Implement DiscoverAbility (override of the adapter's discover tool)**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces the adapter's `mcp-adapter/discover-abilities` tool so its result
 * carries an `instructions` field (run through a filter the Skills Catalog
 * hooks). Registered at wp_abilities_api_init priority 999 — after the
 * adapter registered its default abilities.
 */
final class DiscoverAbility {

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		if ( null !== wp_get_ability( 'mcp-adapter/discover-abilities' ) ) {
			wp_unregister_ability( 'mcp-adapter/discover-abilities' );
		}
		wp_register_ability(
			'mcp-adapter/discover-abilities',
			[
				'label'               => __( 'Discover Abilities', 'lw-site-manager' ),
				'description'         => __( 'List public WordPress abilities plus Site Manager usage instructions and skill catalog.', 'lw-site-manager' ),
				'category'            => 'mcp-adapter',
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'instructions' => [ 'type' => 'string' ],
						'abilities'    => [ 'type' => 'array' ],
					],
					'required'   => [ 'instructions', 'abilities' ],
				],
				'execute_callback'    => [ self::class, 'execute' ],
				'permission_callback' => [ TransportGuard::class, 'allow_read' ],
				'meta'                => [
					'annotations' => [ 'readonly' => true, 'destructive' => false, 'idempotent' => true ],
					'mcp'         => [ 'public' => true, 'type' => 'tool' ],
				],
			]
		);
	}

	/**
	 * @return array{instructions:string,abilities:list<array<string,string>>}
	 */
	public static function execute(): array {
		$list = [];
		foreach ( wp_get_abilities() as $ability ) {
			$meta = $ability->get_meta();
			if ( ! ( $meta['mcp']['public'] ?? false ) ) {
				continue;
			}
			if ( 'tool' !== ( $meta['mcp']['type'] ?? 'tool' ) ) {
				continue;
			}
			$list[] = [
				'name'        => $ability->get_name(),
				'label'       => $ability->get_label(),
				'description' => $ability->get_description(),
			];
		}

		$instructions = '';
		if ( current_user_can( 'manage_options' ) ) {
			/** @var string $instructions */
			$instructions = (string) apply_filters( 'lw_site_manager_discover_instructions', self::base_instructions() );
		}

		return [
			'instructions' => $instructions,
			'abilities'    => $list,
		];
	}

	private static function base_instructions(): string {
		return __( 'You are connected to a WordPress site via LW Site Manager. Use the listed abilities to manage it. Prefer read abilities first; confirm destructive actions with the user.', 'lw-site-manager' );
	}
}
```

- [ ] **Step 3: Add `TransportGuard::allow_read()` helper**

Add to `src/Mcp/TransportGuard.php`:

```php
	public static function allow_read(): bool {
		return current_user_can( 'manage_options' );
	}
```

- [ ] **Step 4: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Mcp/TransportGuard.php src/Mcp/DiscoverAbility.php
git commit -m "feat(mcp): transport capability gate + discover-abilities override"
```

---

### Task 11: Expose own abilities via mcp.public meta (gated by Toggle)

**Files:**
- Modify: `src/Abilities/Registrars/AbstractAbilitiesRegistrar.php:38-47`

- [ ] **Step 1: Update `buildMeta()` to add mcp.public when MCP is enabled**

Replace the `buildMeta` method body:

```php
	protected function buildMeta( bool $readonly = false, bool $destructive = false, bool $idempotent = false ): array {
		$meta = [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => $readonly,
				'destructive' => $destructive,
				'idempotent'  => $idempotent,
			],
		];

		// Expose to the built-in MCP server only when it is enabled. The plain
		// Abilities REST surface ignores this key, so it is harmless either way.
		if ( \LightweightPlugins\SiteManager\Mcp\Toggle::is_enabled() ) {
			$meta['mcp'] = [
				'public' => true,
				'type'   => 'tool',
			];
		}

		return $meta;
	}
```

- [ ] **Step 2: Run the full suite to confirm no regression**

Run: `composer test`
Expected: PASS (no test asserts absence of the `mcp` key).

- [ ] **Step 3: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Abilities/Registrars/AbstractAbilitiesRegistrar.php
git commit -m "feat(mcp): flag own abilities mcp.public when MCP is enabled"
```

---

### Task 12: Server + MCP Bootstrap

**Files:**
- Create: `src/Mcp/Server.php`, `src/Mcp/Bootstrap.php`

- [ ] **Step 1: Implement Server (branding + result filter + discover override registration)**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configures the adapter's default server as the LW Site Manager MCP server.
 */
final class Server {

	public const SERVER_ID    = 'lw-site-manager';
	public const ROUTE_NS     = 'mcp';
	public const SERVER_ROUTE = 'lw-site-manager';

	public static function brand( mixed $config ): mixed {
		if ( ! is_array( $config ) ) {
			return $config;
		}
		$config['server_id']          = self::SERVER_ID;
		$config['server_route']       = self::SERVER_ROUTE;
		$config['server_name']        = 'LW Site Manager';
		$config['server_description'] = 'Manage this WordPress site via LW Site Manager abilities and skills.';
		return $config;
	}

	public static function endpoint(): string {
		return rest_url( self::ROUTE_NS . '/' . self::SERVER_ROUTE );
	}
}
```

- [ ] **Step 2: Implement Bootstrap (the no-op-if-missing guard lives here)**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires and boots the MCP subsystem. Hard no-op when the MCP server is
 * disabled or the adapter library is absent.
 */
final class Bootstrap {

	public static function init(): void {
		if ( ! Toggle::is_enabled() ) {
			return;
		}
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			add_action( 'admin_notices', [ self::class, 'render_missing_notice' ] );
			return;
		}

		add_filter( 'mcp_adapter_default_server_config', [ Server::class, 'brand' ] );
		add_filter( 'mcp_adapter_default_transport_permission_user_capability', [ TransportGuard::class, 'capability' ], 10, 2 );
		add_filter( 'mcp_adapter_tool_call_result', [ ResultUnwrapper::class, 'filter' ], 10, 3 );
		add_action( 'wp_abilities_api_init', [ DiscoverAbility::class, 'register' ], 999 );

		// Boot the adapter; it registers the (branded) default server on mcp_adapter_init.
		\WP\MCP\Core\McpAdapter::instance();
	}

	public static function render_missing_notice(): void {
		echo '<div class="notice notice-warning"><p><strong>LW Site Manager:</strong> ';
		echo esc_html__( 'The MCP server is enabled but the MCP Adapter library is missing. Run "composer install" or re-install from a release ZIP.', 'lw-site-manager' );
		echo '</p></div>';
	}
}
```

> Timing note: `McpAdapter::instance()` must run before `wp_abilities_api_init` fires. Call `Bootstrap::init()` on `plugins_loaded` (see Task 13). The adapter's own `register_default_abilities` runs on `wp_abilities_api_init` priority 10; our `DiscoverAbility::register` at 999 reliably overrides it.

- [ ] **Step 3: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Mcp/Server.php src/Mcp/Bootstrap.php
git commit -m "feat(mcp): default-server branding + guarded bootstrap"
```

---

### Task 13: Wire both subsystems into the main plugin

**Files:**
- Modify: `lw-site-manager.php:84-99` (init_hooks) and `:119` area

- [ ] **Step 1: Add subsystem bootstraps to `init_hooks()`**

In `Plugin::init_hooks()`, after the existing `add_action` calls, add:

```php
		// Skills subsystem (always available; rides on Abilities REST and MCP).
		Skills\Bootstrap::init();

		// MCP server subsystem (no-op unless enabled + adapter present).
		add_action( 'plugins_loaded', [ Mcp\Bootstrap::class, 'init' ], 6 );
```

(`init_hooks()` itself runs from the constructor, which fires on `plugins_loaded` priority 5; registering the MCP bootstrap at priority 6 ensures the adapter boots right after, still before `wp_abilities_api_init`.)

- [ ] **Step 2: Smoke-load the plugin in WP-CLI on the demo site (read-only check)**

Run (demo site, see project memory for SSH/WP-CLI access):
```bash
wp eval 'var_dump( class_exists("LightweightPlugins\\SiteManager\\Mcp\\Bootstrap") );'
```
Expected: `bool(true)` and no fatals on load.

- [ ] **Step 3: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add lw-site-manager.php
git commit -m "feat: bootstrap MCP + Skills subsystems"
```

---

## Phase 3 — Admin (fix dead ParentPage + settings page)

### Task 14: Relocate ParentPage + NoticeManager to src/Admin and wire the menu

**Files:**
- Create: `src/Admin/ParentPage.php`, `src/Admin/NoticeManager.php` (move bodies verbatim; they already use `namespace LightweightPlugins\SiteManager\Admin;`)
- Delete: `includes/Admin/ParentPage.php`, `includes/Admin/NoticeManager.php`
- Modify: `lw-site-manager.php` (wire `admin_menu`)

- [ ] **Step 1: Move the files (namespace already correct → PSR-4 picks them up from src/)**

```bash
git mv includes/Admin/ParentPage.php src/Admin/ParentPage.php
git mv includes/Admin/NoticeManager.php src/Admin/NoticeManager.php
rmdir includes/Admin includes 2>/dev/null || true
```

- [ ] **Step 2: Wire the parent menu in `Plugin::init_hooks()`**

Add:

```php
		add_action( 'admin_menu', [ Admin\ParentPage::class, 'maybe_register' ] );
```

- [ ] **Step 3: Verify the class autoloads and the menu registers**

Run: `php -r "require 'vendor/autoload.php'; var_dump(class_exists('LightweightPlugins\\\\SiteManager\\\\Admin\\\\ParentPage'));"`
Expected: `bool(true)`.

Then on the demo site: `wp eval 'do_action("admin_menu"); echo "ok";'` (expect no fatal). Manually confirm "LW Plugins" menu shows Site Manager.

- [ ] **Step 4: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add -A src/Admin includes lw-site-manager.php
git commit -m "fix(admin): relocate ParentPage/NoticeManager to src/Admin and wire admin_menu"
```

---

### Task 15: MCP settings submenu (toggle + .mcp.json + skills list)

**Files:**
- Create: `src/Admin/McpSettingsPage.php`
- Modify: `lw-site-manager.php` (register submenu + form handler)

- [ ] **Step 1: Implement McpSettingsPage**

```php
<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Admin;

use LightweightPlugins\SiteManager\Mcp\Server;
use LightweightPlugins\SiteManager\Mcp\Toggle;
use LightweightPlugins\SiteManager\Skills\Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "AI / MCP" submenu: enable toggle, .mcp.json snippet, bundled-skill list.
 */
final class McpSettingsPage {

	public const SLUG  = 'lw-site-manager-mcp';
	public const NONCE = 'lw_site_manager_mcp_toggle';

	public static function register_menu(): void {
		add_submenu_page(
			ParentPage::SLUG,
			__( 'AI / MCP', 'lw-site-manager' ),
			__( 'AI / MCP', 'lw-site-manager' ),
			'manage_options',
			self::SLUG,
			[ self::class, 'render' ]
		);
	}

	public static function handle_post(): void {
		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::NONCE, self::NONCE ) ) {
			return;
		}
		if ( ! empty( $_POST['lw_mcp_enable'] ) ) {
			Toggle::enable();
		} else {
			Toggle::disable();
		}
		wp_safe_redirect( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled = Toggle::is_enabled();
		echo '<div class="wrap"><h1>' . esc_html__( 'LW Site Manager — AI / MCP', 'lw-site-manager' ) . '</h1>';
		self::render_toggle_form( $enabled );
		if ( $enabled ) {
			self::render_connection_info();
		}
		self::render_skill_list();
		echo '</div>';
	}

	private static function render_toggle_form( bool $enabled ): void {
		echo '<form method="post">';
		wp_nonce_field( self::NONCE, self::NONCE );
		printf(
			'<p><label><input type="checkbox" name="lw_mcp_enable" value="1" %s> %s</label></p>',
			checked( $enabled, true, false ),
			esc_html__( 'Enable the built-in MCP server for AI agents (admin only).', 'lw-site-manager' )
		);
		echo '<p class="description">' . esc_html__( 'Warning: agents can run write/destructive abilities. Enable only on sites you control. The server auto-disables if the site domain changes.', 'lw-site-manager' ) . '</p>';
		submit_button( __( 'Save', 'lw-site-manager' ) );
		echo '</form>';
	}

	private static function render_connection_info(): void {
		$snippet = wp_json_encode(
			[
				'mcpServers' => [
					'lw-site-manager' => [
						'type'    => 'http',
						'url'     => Server::endpoint(),
						'headers' => [ 'Authorization' => 'Basic BASE64(user:application_password)' ],
					],
				],
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		echo '<h2>' . esc_html__( '.mcp.json', 'lw-site-manager' ) . '</h2>';
		echo '<p>' . esc_html__( 'Create an application password (Users → Profile) and paste this into your MCP client:', 'lw-site-manager' ) . '</p>';
		echo '<textarea readonly rows="10" style="width:100%;font-family:monospace;">' . esc_textarea( (string) $snippet ) . '</textarea>';
	}

	private static function render_skill_list(): void {
		echo '<h2>' . esc_html__( 'Bundled skills', 'lw-site-manager' ) . '</h2><ul>';
		foreach ( Sources::all() as $skill ) {
			printf(
				'<li><code>%s</code> <em>(%s)</em> — %s</li>',
				esc_html( (string) ( $skill['slug'] ?? '' ) ),
				esc_html( (string) ( $skill['source_label'] ?? '' ) ),
				esc_html( (string) ( $skill['description'] ?? '' ) )
			);
		}
		echo '</ul>';
	}
}
```

- [ ] **Step 2: Register the submenu + handler in `Plugin::init_hooks()`**

```php
		add_action( 'admin_menu', [ Admin\McpSettingsPage::class, 'register_menu' ], 11 );
		add_action( 'admin_init', [ Admin\McpSettingsPage::class, 'handle_post' ] );
```

- [ ] **Step 3: Manual verification on the demo site**

- Visit LW Plugins → AI / MCP; toggle on; confirm the `.mcp.json` snippet shows `…/wp-json/mcp/lw-site-manager`; confirm the three bundled skills are listed.
- Connect Claude Code with an admin application password; run `tools/list` → expect `mcp-adapter-*` tools; call discover-abilities → expect the `## Available Skills` catalog in `instructions`; call `site-manager-skill-get` with `site-health-triage` → expect full playbook.
- Try a non-admin application password → expect the transport gate to reject (HTTP 401/403).

- [ ] **Step 4: phpcs + commit**

```bash
composer phpcbf ; composer phpcs
git add src/Admin/McpSettingsPage.php lw-site-manager.php
git commit -m "feat(admin): AI/MCP settings page with toggle, .mcp.json, skills list"
```

---

## Phase 4 — Release

### Task 16: Version bump to 1.2.0

**Files:**
- Modify: `lw-site-manager.php:6` (header), `lw-site-manager.php:27` (constant), `readme.txt` (Stable tag + Changelog), `CHANGELOG.md`

- [ ] **Step 1: Bump all five locations**

- `lw-site-manager.php:6` → `* Version: 1.2.0`
- `lw-site-manager.php:27` → `define( 'LW_SITE_MANAGER_VERSION', '1.2.0' );`
- `readme.txt` → `Stable tag: 1.2.0`
- `readme.txt` Changelog (top):
```
= 1.2.0 =
* New: Built-in MCP server at /wp-json/mcp/lw-site-manager (disabled by default, admin-only, domain-locked).
* New: Bundled Skills library (site-health-triage, woocommerce-order-ops, safe-bulk-content) surfaced to AI agents.
* New: AI / MCP settings page with connection snippet and skill list.
* Fix: ParentPage/NoticeManager are now loaded (moved to src/Admin) — Site Manager appears in the LW Plugins menu.
```
- `CHANGELOG.md` (top):
```markdown
## [1.2.0] - 2026-06-03

### Added
- Built-in MCP server (`/wp-json/mcp/lw-site-manager`) via wordpress/mcp-adapter; disabled by default, admin-gated, domain-locked.
- Bundled static Skills library and `site-manager/skill-get` ability; catalog injected into MCP discovery.
- "AI / MCP" admin settings page (toggle, `.mcp.json` snippet, bundled-skill list).

### Fixed
- `Admin\ParentPage` / `Admin\NoticeManager` were never autoloaded (mapped outside `src/`); relocated to `src/Admin/` and wired to `admin_menu`.
```

- [ ] **Step 2: Final full verification**

Run:
```bash
composer phpcs
composer test
grep -n "1.2.0" lw-site-manager.php readme.txt CHANGELOG.md
```
Expected: phpcs clean, all tests pass, version `1.2.0` present in all files.

- [ ] **Step 3: Commit (do NOT push — wait for explicit consent)**

```bash
git add lw-site-manager.php readme.txt CHANGELOG.md
git commit -m "release: 1.2.0 — MCP server + bundled skills"
```

- [ ] **Step 4: STOP. Ask the user before any `git push` or tag.**

---

## Self-review (completed during planning)

- **Spec coverage:** MCP server (Tasks 9–13), own+opt-in exposure (Task 11 via mcp.public + adapter global discovery), admin-gate+per-ability auth (Tasks 10, 12), toggle+domain-lock (Task 8), bundled static skills (Tasks 2–7), own discover-abilities + filter (Task 10), admin menu fix (Task 14), settings page (Task 15), versioning (Task 16), graceful degradation (Task 12 guard), backward-compat (no existing ability touched; only buildMeta adds an inert key). All spec sections map to a task.
- **Placeholder scan:** none — every code step contains complete code.
- **Type consistency:** `Sources::find/all/discoverable`, `Parser::parse/normalize_slug/render_skill_md`, `Toggle::is_enabled/enable/disable/locked_domain`, `TransportGuard::capability/allow_read`, `ResultUnwrapper::filter`, `Server::brand/endpoint`, `Catalog::inject/inject_filter/render`, `SkillGetAbility::register/execute/can_run` — names are consistent across all referencing tasks.
- **Decomposition note:** Phases are independently shippable — stopping after Phase 1 yields a working Skills layer (usable over the existing Abilities REST); Phase 2 adds the MCP transport; Phase 3 the admin surface.
