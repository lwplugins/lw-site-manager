<?php
/**
 * Compact registration helper for WooCommerce abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;

final class AbilityDefinition {

    /**
     * Register `site-manager/<name>` with an object input schema.
     *
     * @param PermissionManager $permissions Permission manager.
     * @param string            $name        Ability name without the site-manager/ prefix.
     * @param array             $def         label, description, category, properties, required,
     *                                       output, callback, permission (PermissionManager method), meta.
     */
    public static function register( PermissionManager $permissions, string $name, array $def ): void {
        $input = [
            'type'       => 'object',
            'default'    => [],
            'properties' => $def['properties'] ?? [],
        ];
        if ( ! empty( $def['required'] ) ) {
            $input['required'] = $def['required'];
        }

        wp_register_ability(
            'site-manager/' . $name,
            [
                'label'               => $def['label'],
                'description'         => $def['description'],
                'category'            => $def['category'],
                'input_schema'        => $input,
                'output_schema'       => $def['output'],
                'execute_callback'    => $def['callback'],
                'permission_callback' => $permissions->callback( $def['permission'] ),
                'meta'                => $def['meta'],
            ]
        );
    }
}
