<?php
/**
 * Output schema shared by the set-*-meta abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The response of set-post/user/term/comment-meta (MetaManager::set_*_meta()).
 */
final class MetaOutputSchema {

    /**
     * Output schema of one set-*-meta ability.
     *
     * @param string $idKey Object ID field: post_id, user_id, term_id or comment_id.
     * @return array<string, mixed>
     */
    public static function set( string $idKey ): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'changed' => [ 'type' => 'boolean', 'description' => 'False when the value was already stored (nothing written)' ],
                'message' => [ 'type' => 'string' ],
                $idKey    => [ 'type' => 'integer' ],
                'key'     => [ 'type' => 'string' ],
                'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
            ],
        ];
    }
}
