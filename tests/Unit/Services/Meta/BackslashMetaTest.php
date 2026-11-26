<?php
/**
 * Meta values keep their backslashes.
 *
 * Regression guard: update_{type}_meta() expects slashed input and runs
 * wp_unslash() on it. The meta abilities passed raw JSON-decoded values, so
 * `C:\dir` was stored as `C:dir` and escaped quotes in JSON lost their escape.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\CommentManager;
use LightweightPlugins\SiteManager\Services\MetaManager;
use LightweightPlugins\SiteManager\Services\PostManager;
use LightweightPlugins\SiteManager\Services\TaxonomyManager;
use LightweightPlugins\SiteManager\Services\UserManager;
use PHPUnit\Framework\TestCase;

final class BackslashMetaTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_options();
        reset_wp_post_meta();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
        $GLOBALS['wp_user_meta']    = [];
        $GLOBALS['wp_comment_meta'] = [];
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'edit_users', 'edit_post:42', 'edit_user:2', 'edit_comment:9', 'edit_term:7', 'manage_categories' ] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_terms();
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function values(): array {
        return [
            'windows path'           => [ 'C:\\dir' ],
            'escaped quote'          => [ 'a\\"b' ],
            'json with escapes'      => [ '{"a":"x\\"y","p":"C:\\\\dir"}' ],
            'array with backslashes' => [ [ 'path' => 'C:\\dir', 'list' => [ 'a\\b', 'q\\"' ] ] ],
            'serialized w/ slashes'  => [ serialize( [ 'C:\\dir' ] ) ],
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: mixed}>
     */
    public static function typesAndValues(): iterable {
        $types = [
            'post'    => [ 'set_post_meta', 'post_id', 42 ],
            'user'    => [ 'set_user_meta', 'user_id', 2 ],
            'comment' => [ 'set_comment_meta', 'comment_id', 9 ],
            'term'    => [ 'set_term_meta', 'term_id', 7 ],
        ];
        foreach ( $types as $type => $meta ) {
            foreach ( self::values() as $label => $value ) {
                yield "{$type} {$label}" => [ $type, $meta[0], $meta[1] . ':' . $meta[2], $value[0] ];
            }
        }
    }

    /**
     * @dataProvider typesAndValues
     * @param mixed $value Meta value.
     */
    public function test_set_meta_stores_the_value_exactly_and_resend_is_unchanged( string $type, string $method, string $target, $value ): void {
        [ $idKey, $id ] = explode( ':', $target );
        $input          = [ $idKey => (int) $id, 'key' => 'k', 'value' => $value ];

        $first = MetaManager::$method( $input );
        $this->assertIsArray( $first );
        $this->assertSame( [ $value ], get_metadata_raw( $type, (int) $id, 'k' ) );

        $second = MetaManager::$method( $input );
        $this->assertIsArray( $second );
        $this->assertFalse( $second['changed'] );
    }

    public function test_create_post_meta_map_keeps_backslashes(): void {
        $result = PostManager::create_post( [ 'title' => 'Hi', 'meta' => [ 'k' => 'C:\\dir' ] ] );

        $this->assertIsArray( $result );
        $id = (int) ( $result['post']['id'] ?? $result['id'] ?? 0 );
        $this->assertSame( [ 'C:\\dir' ], get_metadata_raw( 'post', $id, 'k' ) );
    }

    public function test_update_post_meta_map_keeps_backslashes(): void {
        PostManager::update_post( [ 'id' => 42, 'meta' => [ 'k' => 'a\\"b' ] ] );

        $this->assertSame( [ 'a\\"b' ], get_metadata_raw( 'post', 42, 'k' ) );
    }

    public function test_update_user_meta_map_keeps_backslashes(): void {
        UserManager::update_user( [ 'id' => 2, 'meta' => [ 'k' => [ 'p' => 'C:\\dir' ] ] ] );

        $this->assertSame( [ [ 'p' => 'C:\\dir' ] ], get_metadata_raw( 'user', 2, 'k' ) );
    }

    public function test_update_comment_meta_map_keeps_backslashes(): void {
        CommentManager::update_comment( [ 'id' => 9, 'meta' => [ 'k' => 'C:\\dir' ] ] );

        $this->assertSame( [ 'C:\\dir' ], get_metadata_raw( 'comment', 9, 'k' ) );
    }

    public function test_update_term_meta_map_keeps_backslashes(): void {
        TaxonomyManager::update_term( [ 'id' => 7, 'taxonomy' => 'category', 'meta' => [ 'k' => 'C:\\dir' ] ] );

        $this->assertSame( [ 'C:\\dir' ], get_metadata_raw( 'term', 7, 'k' ) );
    }
}
