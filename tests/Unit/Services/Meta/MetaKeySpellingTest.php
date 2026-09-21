<?php
/**
 * Meta key spellings that WordPress or the database map onto another key.
 *
 * Security regression guard. The key policies checked the raw key, but
 * update/add/delete_metadata() unslash it, and meta_key lookups follow the
 * column's case-, accent- and trailing-space-insensitive collation. So
 * "wp_capabilities\" (unslashed to wp_capabilities) or "wp_capabilitiés"
 * (collation-equal to it) passed every check and then wrote the role
 * assignment: anyone allowed to edit their own user meta could make
 * themselves an administrator.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\MetaManager;
use PHPUnit\Framework\TestCase;

final class MetaKeySpellingTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_post_meta();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        parent::tearDown();
    }

    /**
     * @dataProvider provide_slashed_privilege_keys
     */
    public function test_slashed_privilege_key_is_refused( string $key ): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = MetaManager::set_user_meta( [ 'user_id' => 2, 'key' => $key, 'value' => [ 'administrator' => true ] ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public static function provide_slashed_privilege_keys(): array {
        return [
            'trailing slash on capabilities' => [ 'wp_capabilities\\' ],
            'slash inside user level'        => [ 'wp_user\\_level' ],
            'trailing slash on sessions'     => [ 'session_tokens\\' ],
        ];
    }

    public function test_slashed_protected_key_is_refused_for_non_admin(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '\\_plugin_state', 'value' => 'x' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    /**
     * @dataProvider provide_collation_variants
     */
    public function test_key_the_database_could_match_to_another_key_is_refused( string $key ): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = MetaManager::set_user_meta( [ 'user_id' => 2, 'key' => $key, 'value' => [ 'administrator' => true ] ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'invalid_meta_key', $result->get_error_code() );
    }

    public static function provide_collation_variants(): array {
        return [
            'accented letter'  => [ 'wp_capabilitiés' ],
            'trailing space'   => [ 'wp_capabilities ' ],
            'control char'     => [ "wp_capa\x00bilities" ],
            'trailing newline' => [ "wp_capabilities\n" ],
        ];
    }

    public function test_upper_case_privilege_key_is_refused(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = MetaManager::set_user_meta( [ 'user_id' => 2, 'key' => 'WP_Capabilities', 'value' => [ 'administrator' => true ] ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_plain_ascii_key_with_inner_space_stays_writable(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'hero title', 'value' => 'x' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_slashed_key_is_refused_on_delete_too(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = MetaManager::delete_post_meta( [ 'post_id' => 42, 'key' => '\\_plugin_state' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }
}
