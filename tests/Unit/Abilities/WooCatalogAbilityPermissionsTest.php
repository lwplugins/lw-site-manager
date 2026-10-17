<?php
/**
 * Capability wiring of the WooCommerce catalog abilities.
 *
 * Security regression guard: global attributes, attribute terms and product
 * abilities were gated on post capabilities (edit_posts / publish_posts /
 * delete_posts) that Editors, Authors and Contributors hold. They must use
 * the capabilities WooCommerce itself requires (see wc_rest_check_*).
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Abilities;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\AttributeAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\AttributeTermAbilities;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use PHPUnit\Framework\TestCase;

final class WooCatalogAbilityPermissionsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_registered_abilities();

        $permissions = new PermissionManager();
        AttributeAbilities::register( $permissions );
        AttributeTermAbilities::register( $permissions );
    }

    protected function tearDown(): void {
        reset_registered_abilities();
        unset( $GLOBALS['wp_granted_caps'] );
        parent::tearDown();
    }

    /**
     * @dataProvider provide_abilities
     */
    public function test_ability_uses_the_woocommerce_capability( string $ability, string $method ): void {
        $registered = get_registered_abilities();

        $this->assertArrayHasKey( $ability, $registered );
        $this->assertSame( $method, $registered[ $ability ]['permission_callback'][1], $ability );
    }

    public static function provide_abilities(): array {
        return [
            // Global attributes: WooCommerce's attributes screen and REST use manage_product_terms.
            [ 'site-manager/wc-list-attributes', 'can_read_product_terms' ],
            [ 'site-manager/wc-get-attribute', 'can_read_product_terms' ],
            [ 'site-manager/wc-create-attribute', 'can_read_product_terms' ],
            [ 'site-manager/wc-update-attribute', 'can_read_product_terms' ],
            [ 'site-manager/wc-delete-attribute', 'can_read_product_terms' ],
            // Attribute terms follow the pa_* taxonomy caps (manage/edit/delete_product_terms).
            [ 'site-manager/wc-list-attribute-terms', 'can_read_product_terms' ],
            [ 'site-manager/wc-create-attribute-term', 'can_edit_product_terms' ],
            [ 'site-manager/wc-update-attribute-term', 'can_edit_product_terms' ],
            [ 'site-manager/wc-delete-attribute-term', 'can_delete_product_terms' ],
        ];
    }

    public function test_post_capabilities_do_not_grant_product_term_access(): void {
        $manager = new PermissionManager();

        $GLOBALS['wp_granted_caps'] = [ 'edit_posts', 'publish_posts', 'delete_posts', 'manage_categories' ];
        $this->assertFalse( $manager->can_read_product_terms() );
        $this->assertFalse( $manager->can_edit_product_terms() );
        $this->assertFalse( $manager->can_delete_product_terms() );

        $GLOBALS['wp_granted_caps'] = [ 'manage_product_terms', 'edit_product_terms', 'delete_product_terms' ];
        $this->assertTrue( $manager->can_read_product_terms() );
        $this->assertTrue( $manager->can_edit_product_terms() );
        $this->assertTrue( $manager->can_delete_product_terms() );
    }
}
