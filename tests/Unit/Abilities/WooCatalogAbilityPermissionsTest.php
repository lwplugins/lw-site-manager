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
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductAttributeAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductExtraAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductTermAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\VariationAbilities;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use PHPUnit\Framework\TestCase;

final class WooCatalogAbilityPermissionsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_registered_abilities();

        $permissions = new PermissionManager();
        AttributeAbilities::register( $permissions );
        AttributeTermAbilities::register( $permissions );
        ProductAbilities::register( $permissions );
        ProductExtraAbilities::register( $permissions );
        ProductAttributeAbilities::register( $permissions );
        VariationAbilities::register( $permissions );
        ProductTermAbilities::register( $permissions );
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
            // Products follow wc_rest_check_post_permissions( 'product', … ).
            [ 'site-manager/wc-list-products', 'can_read_products' ],
            [ 'site-manager/wc-get-product', 'can_read_products' ],
            [ 'site-manager/wc-list-variations', 'can_read_products' ],
            [ 'site-manager/wc-create-product', 'can_publish_products' ],
            [ 'site-manager/wc-generate-variations', 'can_publish_products' ],
            [ 'site-manager/wc-create-variation', 'can_publish_products' ],
            [ 'site-manager/wc-update-product', 'can_manage_products' ],
            [ 'site-manager/wc-update-stock', 'can_manage_products' ],
            [ 'site-manager/wc-update-variation', 'can_manage_products' ],
            [ 'site-manager/wc-set-product-attributes', 'can_manage_products' ],
            [ 'site-manager/wc-add-product-attribute', 'can_manage_products' ],
            [ 'site-manager/wc-remove-product-attribute', 'can_manage_products' ],
            [ 'site-manager/wc-delete-product', 'can_delete_products' ],
            [ 'site-manager/wc-delete-variation', 'can_delete_products' ],
            [ 'site-manager/wc-bulk-products', 'can_batch_products' ],
            // WooCommerce's duplicate action: woocommerce_duplicate_product_capability (manage_woocommerce).
            [ 'site-manager/wc-duplicate-product', 'can_duplicate_products' ],
            // Product terms (categories, tags, brands, shipping classes).
            [ 'site-manager/wc-list-product-categories', 'can_read_product_terms' ],
            [ 'site-manager/wc-list-product-terms', 'can_read_product_terms' ],
            [ 'site-manager/wc-get-product-term', 'can_read_product_terms' ],
            [ 'site-manager/wc-create-product-term', 'can_edit_product_terms' ],
            [ 'site-manager/wc-update-product-term', 'can_edit_product_terms' ],
            [ 'site-manager/wc-delete-product-term', 'can_delete_product_terms' ],
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

    public function test_post_capabilities_do_not_grant_product_access(): void {
        $manager = new PermissionManager();

        $GLOBALS['wp_granted_caps'] = [ 'edit_posts', 'publish_posts', 'delete_posts', 'edit_others_posts', 'read_private_posts' ];
        $this->assertFalse( $manager->can_read_products() );
        $this->assertFalse( $manager->can_publish_products() );
        $this->assertFalse( $manager->can_delete_products() );
        $this->assertFalse( $manager->can_batch_products() );
        $this->assertFalse( $manager->can_duplicate_products() );

        $GLOBALS['wp_granted_caps'] = [ 'read_private_products', 'publish_products', 'delete_products', 'edit_others_products', 'manage_woocommerce' ];
        $this->assertTrue( $manager->can_read_products() );
        $this->assertTrue( $manager->can_publish_products() );
        $this->assertTrue( $manager->can_delete_products() );
        $this->assertTrue( $manager->can_batch_products() );
        $this->assertTrue( $manager->can_duplicate_products() );
    }
}
