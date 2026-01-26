<?php
/**
 * Media Abilities - Media Library management
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\MediaManager;

class MediaAbilities {

    public static function register( PermissionManager $permissions ): void {
        // List media
        wp_register_ability(
            'site-manager/list-media',
            [
                'label'       => __( 'List Media', 'lw-site-manager' ),
                'description' => __( 'List media library items', 'lw-site-manager' ),
                'category'    => 'media',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'mime_type' => [
                            'type'        => 'string',
                            'description' => 'Filter by mime type (e.g., image, image/jpeg, video)',
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search term',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'date',
                            'enum'    => [ 'date', 'title', 'modified' ],
                        ],
                        'order' => [
                            'type'    => 'string',
                            'default' => 'DESC',
                            'enum'    => [ 'ASC', 'DESC' ],
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => [ MediaManager::class, 'list_media' ],
                'permission_callback' => $permissions->callback( 'can_upload_files' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get media
        wp_register_ability(
            'site-manager/get-media',
            [
                'label'       => __( 'Get Media', 'lw-site-manager' ),
                'description' => __( 'Get detailed information about a media item', 'lw-site-manager' ),
                'category'    => 'media',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Media ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ MediaManager::class, 'get_media' ],
                'permission_callback' => $permissions->callback( 'can_upload_files' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Upload media (from URL or base64)
        wp_register_ability(
            'site-manager/upload-media',
            [
                'label'       => __( 'Upload Media', 'lw-site-manager' ),
                'description' => __( 'Upload media from URL or base64 encoded data', 'lw-site-manager' ),
                'category'    => 'media',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'url' => [
                            'type'        => 'string',
                            'description' => 'URL of the file to upload (use this OR data+filename)',
                        ],
                        'data' => [
                            'type'        => 'string',
                            'description' => 'Base64 encoded file data (use with filename)',
                        ],
                        'filename' => [
                            'type'        => 'string',
                            'description' => 'Filename with extension (required when using data)',
                        ],
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Title for the media',
                        ],
                        'alt' => [
                            'type'        => 'string',
                            'description' => 'Alt text for images',
                        ],
                        'caption' => [
                            'type'        => 'string',
                            'description' => 'Caption',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Description',
                        ],
                    ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ MediaManager::class, 'upload_media' ],
                'permission_callback' => $permissions->callback( 'can_upload_files' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update media
        wp_register_ability(
            'site-manager/update-media',
            [
                'label'       => __( 'Update Media', 'lw-site-manager' ),
                'description' => __( 'Update media item metadata', 'lw-site-manager' ),
                'category'    => 'media',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Media ID',
                        ],
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Title',
                        ],
                        'alt' => [
                            'type'        => 'string',
                            'description' => 'Alt text',
                        ],
                        'caption' => [
                            'type'        => 'string',
                            'description' => 'Caption',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Description',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ MediaManager::class, 'update_media' ],
                'permission_callback' => $permissions->callback( 'can_upload_files' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete media
        wp_register_ability(
            'site-manager/delete-media',
            [
                'label'       => __( 'Delete Media', 'lw-site-manager' ),
                'description' => __( 'Delete a media item', 'lw-site-manager' ),
                'category'    => 'media',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Media ID',
                        ],
                        'force' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Permanently delete (skip trash)',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'id'      => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ MediaManager::class, 'delete_media' ],
                'permission_callback' => $permissions->callback( 'can_upload_files' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    private static function readOnlyMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ];
    }

    private static function writeMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ];
    }

    private static function destructiveMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ],
        ];
    }

    private static function getListOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'media' => [
                    'type'  => 'array',
                    'items' => self::getMediaSchema(),
                ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
            ],
        ];
    }

    private static function getEntityOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'media'   => self::getMediaSchema( true ),
            ],
        ];
    }

    private static function getMediaSchema( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'        => [ 'type' => 'integer' ],
                'title'     => [ 'type' => 'string' ],
                'url'       => [ 'type' => 'string' ],
                'mime_type' => [ 'type' => 'string' ],
                'date'      => [ 'type' => 'string' ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['alt']         = [ 'type' => 'string' ];
            $schema['properties']['caption']     = [ 'type' => 'string' ];
            $schema['properties']['description'] = [ 'type' => 'string' ];
            $schema['properties']['filename']    = [ 'type' => 'string' ];
            $schema['properties']['width']       = [ 'type' => [ 'integer', 'null' ] ];
            $schema['properties']['height']      = [ 'type' => [ 'integer', 'null' ] ];
            $schema['properties']['filesize']    = [ 'type' => [ 'integer', 'null' ] ];
            $schema['properties']['sizes']       = [
                'type' => 'object',
                'additionalProperties' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'url'    => [ 'type' => 'string' ],
                        'width'  => [ 'type' => 'integer' ],
                        'height' => [ 'type' => 'integer' ],
                    ],
                ],
            ];
        }

        return $schema;
    }
}
