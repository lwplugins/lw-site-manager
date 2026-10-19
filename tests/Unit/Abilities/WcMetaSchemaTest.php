<?php
/**
 * Schema shape of the WooCommerce meta abilities.
 *
 * Regression guard: an untyped `value` property made WordPress' REST schema
 * validator emit "Undefined array key type" warnings on every call of
 * wc-get-product-meta and wc-get-order-meta.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Abilities;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\WcMetaAbilities;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use PHPUnit\Framework\TestCase;

final class WcMetaSchemaTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_registered_abilities();
        WcMetaAbilities::register( new PermissionManager() );
    }

    protected function tearDown(): void {
        reset_registered_abilities();
        parent::tearDown();
    }

    public function test_every_schema_property_declares_a_type(): void {
        $registered = get_registered_abilities();
        $this->assertNotEmpty( $registered );

        foreach ( $registered as $name => $args ) {
            foreach ( [ 'input_schema', 'output_schema' ] as $schema_key ) {
                $this->assert_typed( $args[ $schema_key ], $name . ' ' . $schema_key );
            }
        }
    }

    /**
     * Assert that a schema node and all its children declare a type.
     *
     * @param array<string, mixed> $schema Schema node.
     * @param string               $path   Location for the failure message.
     */
    private function assert_typed( array $schema, string $path ): void {
        $this->assertArrayHasKey( 'type', $schema, $path );

        foreach ( $schema['properties'] ?? [] as $property => $child ) {
            $this->assert_typed( $child, $path . '.' . $property );
        }

        if ( isset( $schema['items'] ) ) {
            $this->assert_typed( $schema['items'], $path . '[]' );
        }
    }
}
