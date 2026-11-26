<?php
/**
 * Writing a value that is already stored is a success, not a failure.
 *
 * Regression guard: core's update_{type}_meta() and update_option() return false
 * both on a real failure and when the new value equals the stored one, and the
 * meta/settings abilities reported the second case as a 500 update_failed.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\MetaManager;
use LightweightPlugins\SiteManager\Services\SettingsManager;
use PHPUnit\Framework\TestCase;

final class UnchangedWriteTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
        reset_wp_post_meta();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
        $GLOBALS['wp_user_meta']              = [];
        $GLOBALS['wp_comment_meta']           = [];
        $GLOBALS['wp_stub_meta_update_fails'] = false;
        $GLOBALS['wp_stub_revision_parent']   = [];
        grant_wp_caps( [ 'edit_posts', 'edit_users', 'edit_post:42', 'edit_user:2', 'edit_comment:9', 'edit_term:7', 'manage_options', 'manage_categories' ] );
    }

    protected function tearDown(): void {
        $GLOBALS['wp_stub_meta_update_fails'] = false;
        $GLOBALS['wp_stub_revision_parent']   = [];
        reset_wp_caps();
        reset_wp_terms();
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function metaTypes(): array {
        return [
            'post'    => [ 'set_post_meta', 'post_id', 42 ],
            'user'    => [ 'set_user_meta', 'user_id', 2 ],
            'comment' => [ 'set_comment_meta', 'comment_id', 9 ],
            'term'    => [ 'set_term_meta', 'term_id', 7 ],
        ];
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function values(): array {
        return [
            'string'     => [ 'red' ],
            'int'        => [ 5 ],
            'bool'       => [ true ],
            'array'      => [ [ 'a' => 1, 'b' => [ 'x', 'y' ] ] ],
            'serialized' => [ 'a:1:{i:0;s:1:"x";}' ],
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: mixed}>
     */
    public static function typesAndValues(): iterable {
        foreach ( self::metaTypes() as $type => $meta ) {
            foreach ( self::values() as $label => $value ) {
                yield "{$type} {$label}" => [ $meta[0], $meta[1], $meta[2], $value[0] ];
            }
        }
    }

    /**
     * @dataProvider typesAndValues
     * @param mixed $value Meta value.
     */
    public function test_setting_the_stored_value_again_succeeds_unchanged( string $method, string $idKey, int $id, $value ): void {
        $input = [ $idKey => $id, 'key' => 'colour', 'value' => $value ];

        $first = MetaManager::$method( $input );
        $this->assertIsArray( $first );
        $this->assertTrue( $first['changed'] );

        $second = MetaManager::$method( $input );
        $this->assertNotInstanceOf( \WP_Error::class, $second );
        $this->assertTrue( $second['success'] );
        $this->assertFalse( $second['changed'] );
    }

    /**
     * @dataProvider metaTypes
     */
    public function test_changing_the_value_reports_changed( string $method, string $idKey, int $id ): void {
        MetaManager::$method( [ $idKey => $id, 'key' => 'colour', 'value' => 'red' ] );

        $result = MetaManager::$method( [ $idKey => $id, 'key' => 'colour', 'value' => 'blue' ] );

        $this->assertIsArray( $result );
        $this->assertTrue( $result['changed'] );
    }

    /**
     * @dataProvider metaTypes
     */
    public function test_a_real_failure_is_still_an_error( string $method, string $idKey, int $id ): void {
        MetaManager::$method( [ $idKey => $id, 'key' => 'colour', 'value' => 'red' ] );
        $GLOBALS['wp_stub_meta_update_fails'] = true;

        $result = MetaManager::$method( [ $idKey => $id, 'key' => 'colour', 'value' => 'blue' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'update_failed', $result->get_error_code() );
    }

    /**
     * @dataProvider metaTypes
     */
    public function test_a_failed_first_write_is_an_error( string $method, string $idKey, int $id ): void {
        $GLOBALS['wp_stub_meta_update_fails'] = true;

        $result = MetaManager::$method( [ $idKey => $id, 'key' => 'colour', 'value' => '' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'update_failed', $result->get_error_code() );
    }

    public function test_value_equal_after_unslashing_counts_as_unchanged(): void {
        $GLOBALS['wp_post_meta'][42]['path'] = 'C:dir';

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'path', 'value' => 'C:\\dir' ] );

        $this->assertIsArray( $result );
        $this->assertFalse( $result['changed'] );
    }

    public function test_value_equal_after_registered_sanitizer_counts_as_unchanged(): void {
        add_filter( 'sanitize_post_meta_price', static fn ( $v ) => trim( (string) $v ) );
        $GLOBALS['wp_post_meta'][42]['price'] = '10';

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'price', 'value' => ' 10 ' ] );

        reset_wp_filters();
        $this->assertIsArray( $result );
        $this->assertFalse( $result['changed'] );
    }

    public function test_revision_meta_is_compared_on_the_parent_post(): void {
        $GLOBALS['wp_stub_revision_parent'] = [ 43 => 42 ];
        $GLOBALS['wp_post_meta'][42]['colour'] = 'red';
        grant_wp_caps( [ 'edit_posts', 'edit_post:43' ] );

        // update_post_meta() itself writes to the parent; the stub does not, so
        // force "false" and check that the comparison looks at post 42.
        $GLOBALS['wp_stub_meta_update_fails'] = true;
        $result = MetaManager::set_post_meta( [ 'post_id' => 43, 'key' => 'colour', 'value' => 'red' ] );

        $this->assertIsArray( $result );
        $this->assertFalse( $result['changed'] );
    }

    public function test_shared_term_error_is_returned_not_reported_as_success(): void {
        add_filter( 'lwsm_stub_term_meta_result', static fn () => new \WP_Error( 'ambiguous_term_id', 'Term meta cannot be added to terms that are shared between taxonomies.' ) );

        $result = MetaManager::set_term_meta( [ 'term_id' => 7, 'key' => 'colour', 'value' => 'red' ] );

        reset_wp_filters();
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'ambiguous_term_id', $result->get_error_code() );
    }

    // =========================================================================
    // Settings: integer settings submitted again were reported as failed
    // =========================================================================

    public function test_general_settings_resubmitting_stored_integers_is_not_a_failure(): void {
        $GLOBALS['wp_options']['start_of_week']      = '1';
        $GLOBALS['wp_options']['users_can_register'] = '0';

        $result = SettingsManager::update_general_settings( [ 'start_of_week' => 1, 'users_can_register' => false ] );

        $this->assertIsArray( $result );
        $this->assertEmpty( $result['failed'] ?? [] );
    }

    public function test_settings_real_failure_still_reported(): void {
        $GLOBALS['wp_options']['blogname'] = 'Old';
        add_filter( 'pre_update_option_blogname', static fn ( $new, $old ) => $old, 10, 2 );

        $result = SettingsManager::update_general_settings( [ 'blogname' => 'New' ] );

        reset_wp_filters();
        $this->assertInstanceOf( \WP_Error::class, $result );
    }
}
