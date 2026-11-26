<?php
/**
 * Post, comment, term and user fields keep their backslashes.
 *
 * Regression guard: wp_insert_post(), wp_update_post(), wp_insert_term(),
 * wp_update_term(), wp_insert_comment(), wp_update_comment(), wp_insert_user()
 * and wp_update_user() expect slashed input and unslash it before saving. The
 * abilities passed raw values, so `C:\dir` was saved as `C:dir`. The test
 * stubs unslash like core and record what would be saved.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\CommentManager;
use LightweightPlugins\SiteManager\Services\PostManager;
use LightweightPlugins\SiteManager\Services\TaxonomyManager;
use LightweightPlugins\SiteManager\Services\UserManager;
use PHPUnit\Framework\TestCase;

final class BackslashContentTest extends TestCase {

    private const CONTENT = "C:\\dir \\\\ {\"a\":\"b\\\"c\"}\n<pre>printf(\"x\\n\");</pre>";

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_options();
        reset_wp_post_meta();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
        $GLOBALS['wp_saved_posts']       = [];
        $GLOBALS['wp_inserted_comments'] = [];
        $GLOBALS['wp_updated_comments']  = [];
        $GLOBALS['wp_inserted_users']    = [];
        $GLOBALS['wp_last_updated_user'] = null;
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_terms();
        parent::tearDown();
    }

    private function lastSavedPost(): array {
        return end( $GLOBALS['wp_saved_posts'] );
    }

    public function test_create_post_saves_content_and_excerpt_exactly(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] );

        PostManager::create_post( [ 'title' => 'T', 'content' => self::CONTENT, 'excerpt' => 'C:\\dir' ] );

        $saved = $this->lastSavedPost();
        $this->assertSame( self::CONTENT, $saved['post_content'] );
        $this->assertSame( 'C:\\dir', $saved['post_excerpt'] );
    }

    public function test_update_post_saves_content_exactly(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        PostManager::update_post( [ 'id' => 42, 'content' => self::CONTENT, 'excerpt' => 'C:\\dir' ] );

        $saved = $this->lastSavedPost();
        $this->assertSame( self::CONTENT, $saved['post_content'] );
        $this->assertSame( 'C:\\dir', $saved['post_excerpt'] );
    }

    public function test_create_comment_saves_content_exactly(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_post:42' ] );

        CommentManager::create_comment( [ 'post_id' => 42, 'content' => 'C:\\dir a\\"b' ] );

        $this->assertSame( 'C:\\dir a\\"b', end( $GLOBALS['wp_inserted_comments'] )['comment_content'] );
    }

    public function test_update_comment_saves_content_exactly(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_comment:9' ] );

        CommentManager::update_comment( [ 'id' => 9, 'content' => 'C:\\dir' ] );

        $this->assertSame( 'C:\\dir', end( $GLOBALS['wp_updated_comments'] )['comment_content'] );
    }

    public function test_create_term_saves_name_and_description_exactly(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $result = TaxonomyManager::create_term( [ 'name' => 'A\\B', 'taxonomy' => 'category', 'description' => 'C:\\dir' ] );

        $this->assertIsArray( $result );
        $terms = $GLOBALS['wp_terms'];
        $term  = end( $terms );
        $this->assertSame( 'A\\B', $term->name );
        $this->assertSame( 'C:\\dir', $term->description );
    }

    public function test_update_term_saves_description_exactly(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        TaxonomyManager::update_term( [ 'id' => 7, 'taxonomy' => 'category', 'description' => 'C:\\dir' ] );

        $this->assertSame( 'C:\\dir', get_term( 7 )->description );
    }

    public function test_create_user_saves_fields_exactly_and_hashes_the_password_like_wp_login(): void {
        grant_wp_caps( [ 'create_users', 'promote_users' ] );

        UserManager::create_user( [ 'username' => 'eve', 'email' => 'eve@example.com', 'display_name' => 'E\\ve', 'password' => 'p\\w"d' ] );

        $saved = end( $GLOBALS['wp_inserted_users'] );
        $this->assertSame( 'E\\ve', $saved['display_name'] );
        // wp-login.php checks the slashed $_POST['pwd']: the hash must be of the slashed password.
        $this->assertSame( wp_slash( 'p\\w"d' ), $saved['user_pass'] );
    }

    public function test_update_user_password_is_hashed_like_wp_login(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2' ] );

        UserManager::update_user( [ 'id' => 2, 'password' => 'p\\w"d', 'last_name' => 'O\\Neil' ] );

        $this->assertSame( wp_slash( 'p\\w"d' ), $GLOBALS['wp_last_updated_user']['user_pass'] );
        $this->assertSame( 'O\\Neil', $GLOBALS['wp_last_updated_user']['last_name'] );
    }

    public function test_reset_password_is_hashed_like_wp_login(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2' ] );

        UserManager::reset_password( [ 'id' => 2, 'new_password' => 'p\\w"d', 'send_notification' => false ] );

        $this->assertSame( wp_slash( 'p\\w"d' ), $GLOBALS['wp_last_set_password']['password'] );
    }
}
