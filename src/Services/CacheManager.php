<?php
/**
 * Cache Manager Service - Cache flushing and management
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

class CacheManager extends AbstractService {

    /**
     * Flush all caches
     */
    public static function flush( array $input ): array {
        $flushed = [];

        $object_cache = $input['object_cache'] ?? true;
        $page_cache = $input['page_cache'] ?? true;
        $opcache = $input['opcache'] ?? true;

        // Flush WordPress object cache
        if ( $object_cache ) {
            wp_cache_flush();
            $flushed[] = 'object_cache';
        }

        // Flush OPcache
        if ( $opcache && function_exists( 'opcache_reset' ) ) {
            opcache_reset();
            $flushed[] = 'opcache';
        }

        // Try to flush popular caching plugins
        if ( $page_cache ) {
            // WP Super Cache
            if ( function_exists( 'wp_cache_clear_cache' ) ) {
                wp_cache_clear_cache();
                $flushed[] = 'wp_super_cache';
            }

            // W3 Total Cache
            if ( function_exists( 'w3tc_flush_all' ) ) {
                w3tc_flush_all();
                $flushed[] = 'w3_total_cache';
            }

            // WP Fastest Cache
            if ( class_exists( 'WpFastestCache' ) ) {
                $wpfc = new \WpFastestCache();
                $wpfc->deleteCache();
                $flushed[] = 'wp_fastest_cache';
            }

            // LiteSpeed Cache
            if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
                \LiteSpeed_Cache_API::purge_all();
                $flushed[] = 'litespeed_cache';
            }

            // WP Rocket
            if ( function_exists( 'rocket_clean_domain' ) ) {
                rocket_clean_domain();
                $flushed[] = 'wp_rocket';
            }

            // Cache Enabler
            if ( class_exists( 'Cache_Enabler' ) ) {
                \Cache_Enabler::clear_total_cache();
                $flushed[] = 'cache_enabler';
            }

            // Autoptimize
            if ( class_exists( 'autoptimizeCache' ) ) {
                \autoptimizeCache::clearall();
                $flushed[] = 'autoptimize';
            }

            // Kinsta Cache (MU plugin)
            if ( class_exists( 'Kinsta\Cache' ) ) {
                wp_remote_get( home_url( '/?kinsta-clear-cache=all' ), [ 'blocking' => false ] );
                $flushed[] = 'kinsta_cache';
            }

            // SG Optimizer (SiteGround)
            if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
                sg_cachepress_purge_cache();
                $flushed[] = 'sg_optimizer';
            }

            // Cloudflare (if using WP plugin)
            if ( class_exists( 'CF\WordPress\Hooks' ) ) {
                do_action( 'cloudflare_purge_by_url', home_url() );
                $flushed[] = 'cloudflare';
            }

            // Redis Object Cache
            if ( function_exists( 'wp_cache_flush_redis' ) ) {
                wp_cache_flush_redis();
                $flushed[] = 'redis';
            }

            // Varnish
            if ( function_exists( 'varnish_purge_url' ) ) {
                varnish_purge_url( home_url() );
                $flushed[] = 'varnish';
            }
        }

        // Clear WordPress rewrite rules cache
        flush_rewrite_rules();
        $flushed[] = 'rewrite_rules';

        return self::successResponse(
            [ 'flushed' => $flushed ],
            sprintf(
                __( 'Flushed %1$d cache(s): %2$s', 'lw-site-manager' ),
                count( $flushed ),
                implode( ', ', $flushed )
            )
        );
    }

    /**
     * Get cache status
     */
    public static function get_status(): array {
        $status = [
            'object_cache' => [
                'enabled' => wp_using_ext_object_cache(),
                'type'    => self::detect_object_cache_type(),
            ],
            'opcache' => [
                'enabled' => function_exists( 'opcache_get_status' ),
                'stats'   => null,
            ],
            'page_cache' => [
                'detected' => self::detect_page_cache(),
            ],
        ];

        // Get OPcache stats if available
        if ( function_exists( 'opcache_get_status' ) ) {
            $opcache = @opcache_get_status( false );
            if ( $opcache ) {
                $status['opcache']['stats'] = [
                    'memory_usage' => [
                        'used'   => size_format( $opcache['memory_usage']['used_memory'] ?? 0 ),
                        'free'   => size_format( $opcache['memory_usage']['free_memory'] ?? 0 ),
                        'wasted' => size_format( $opcache['memory_usage']['wasted_memory'] ?? 0 ),
                    ],
                    'scripts'      => $opcache['opcache_statistics']['num_cached_scripts'] ?? 0,
                    'hit_rate'     => round( $opcache['opcache_statistics']['opcache_hit_rate'] ?? 0, 2 ) . '%',
                ];
            }
        }

        return $status;
    }

    /**
     * Detect object cache type
     */
    private static function detect_object_cache_type(): string {
        global $wp_object_cache;

        if ( ! wp_using_ext_object_cache() ) {
            return 'none';
        }

        // Check for common object cache implementations
        if ( class_exists( 'Redis' ) && isset( $wp_object_cache->redis ) ) {
            return 'redis';
        }

        if ( class_exists( 'Memcached' ) && isset( $wp_object_cache->mc ) ) {
            return 'memcached';
        }

        if ( class_exists( 'Memcache' ) && isset( $wp_object_cache->mc ) ) {
            return 'memcache';
        }

        if ( defined( 'WP_REDIS_DISABLED' ) && ! WP_REDIS_DISABLED ) {
            return 'redis';
        }

        return 'external';
    }

    /**
     * Detect page cache plugin
     */
    private static function detect_page_cache(): array {
        $detected = [];

        if ( defined( 'WPCACHEHOME' ) ) {
            $detected[] = 'WP Super Cache';
        }

        if ( defined( 'W3TC' ) ) {
            $detected[] = 'W3 Total Cache';
        }

        if ( class_exists( 'WpFastestCache' ) ) {
            $detected[] = 'WP Fastest Cache';
        }

        if ( defined( 'LSCWP_V' ) ) {
            $detected[] = 'LiteSpeed Cache';
        }

        if ( defined( 'WP_ROCKET_VERSION' ) ) {
            $detected[] = 'WP Rocket';
        }

        if ( class_exists( 'Cache_Enabler' ) ) {
            $detected[] = 'Cache Enabler';
        }

        if ( defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) ) {
            $detected[] = 'Autoptimize';
        }

        if ( class_exists( 'SiteGround_Optimizer\\Supercacher\\Supercacher' ) ) {
            $detected[] = 'SG Optimizer';
        }

        return $detected;
    }

    /**
     * Preload cache (for compatible plugins)
     */
    public static function preload(): array {
        $preloaded = [];

        // WP Rocket preload
        if ( function_exists( 'run_rocket_sitemap_preload' ) ) {
            run_rocket_sitemap_preload();
            $preloaded[] = 'wp_rocket';
        }

        // WP Super Cache preload
        if ( function_exists( 'wp_cache_preload' ) ) {
            wp_cache_preload();
            $preloaded[] = 'wp_super_cache';
        }

        // LiteSpeed Cache preload
        if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'preload' ) ) {
            \LiteSpeed_Cache_API::preload();
            $preloaded[] = 'litespeed_cache';
        }

        if ( empty( $preloaded ) ) {
            return self::successResponse(
                [ 'preloaded' => [] ],
                __( 'No compatible caching plugin found for preloading', 'lw-site-manager' )
            );
        }

        return self::successResponse(
            [ 'preloaded' => $preloaded ],
            sprintf(
                __( 'Started preloading for: %s', 'lw-site-manager' ),
                implode( ', ', $preloaded )
            )
        );
    }
}
