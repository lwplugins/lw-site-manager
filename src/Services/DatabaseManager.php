<?php
/**
 * Database Manager Service - Database optimization and cleanup
 */

declare(strict_types=1);

namespace WPSiteManager\Services;

class DatabaseManager extends AbstractService {

    /**
     * Optimize database tables
     */
    public static function optimize( array $input = [] ): array {
        global $wpdb;

        $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
        $optimized = [];
        $failed = [];

        foreach ( $tables as $table ) {
            $table_name = $table[0];

            // Only optimize WordPress tables
            if ( strpos( $table_name, $wpdb->prefix ) !== 0 ) {
                continue;
            }

            $result = $wpdb->query( "OPTIMIZE TABLE `{$table_name}`" );

            if ( $result !== false ) {
                $optimized[] = $table_name;
            } else {
                $failed[] = $table_name;
            }
        }

        return self::successResponse(
            [
                'optimized' => $optimized,
                'failed'    => $failed,
            ],
            sprintf(
                __( 'Optimized %d tables, %d failed', 'wp-site-manager' ),
                count( $optimized ),
                count( $failed )
            )
        );
    }

    /**
     * Cleanup database
     */
    public static function cleanup( array $input ): array {
        global $wpdb;

        $deleted = [
            'revisions'            => 0,
            'auto_drafts'          => 0,
            'trash_posts'          => 0,
            'spam_comments'        => 0,
            'trash_comments'       => 0,
            'expired_transients'   => 0,
            'all_transients'       => 0,
            'orphaned_postmeta'    => 0,
            'orphaned_commentmeta' => 0,
        ];

        // Delete revisions
        if ( $input['revisions'] ?? true ) {
            $deleted['revisions'] = $wpdb->query(
                "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
            );
        }

        // Delete auto-drafts
        if ( $input['auto_drafts'] ?? true ) {
            $deleted['auto_drafts'] = $wpdb->query(
                "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
            );
        }

        // Delete trash posts
        if ( $input['trash_posts'] ?? true ) {
            $deleted['trash_posts'] = $wpdb->query(
                "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'"
            );
        }

        // Delete spam comments
        if ( $input['spam_comments'] ?? true ) {
            $deleted['spam_comments'] = $wpdb->query(
                "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
            );
        }

        // Delete trash comments
        if ( $input['trash_comments'] ?? true ) {
            $deleted['trash_comments'] = $wpdb->query(
                "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
            );
        }

        // Delete expired transients
        if ( $input['expired_transients'] ?? true ) {
            $time = time();

            // Get expired transient names
            $expired = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options}
                     WHERE option_name LIKE %s
                     AND option_value < %d",
                    '_transient_timeout_%',
                    $time
                )
            );

            foreach ( $expired as $transient ) {
                $name = str_replace( '_transient_timeout_', '', $transient );
                delete_transient( $name );
                $deleted['expired_transients']++;
            }
        }

        // Delete ALL transients (more aggressive)
        if ( $input['all_transients'] ?? false ) {
            $deleted['all_transients'] = $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'"
            );
        }

        // Clean orphaned postmeta
        $deleted['orphaned_postmeta'] = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL"
        );

        // Clean orphaned commentmeta
        $deleted['orphaned_commentmeta'] = $wpdb->query(
            "DELETE cm FROM {$wpdb->commentmeta} cm
             LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
             WHERE c.comment_ID IS NULL"
        );

        // Clean orphaned term relationships
        $wpdb->query(
            "DELETE tr FROM {$wpdb->term_relationships} tr
             LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             WHERE p.ID IS NULL"
        );

        $total_deleted = array_sum( $deleted );

        return self::successResponse(
            [
                'deleted' => $deleted,
                'total'   => $total_deleted,
            ],
            sprintf(
                __( 'Cleaned up %d items from database', 'wp-site-manager' ),
                $total_deleted
            )
        );
    }

    /**
     * Get database statistics
     */
    public static function get_stats(): array {
        global $wpdb;

        $tables = $wpdb->get_results(
            "SELECT
                table_name AS 'name',
                table_rows AS 'rows',
                data_length AS 'data_size',
                index_length AS 'index_size',
                (data_length + index_length) AS 'total_size',
                engine
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name LIKE '{$wpdb->prefix}%'
             ORDER BY total_size DESC",
            ARRAY_A
        );

        $total_size = 0;
        $total_rows = 0;

        foreach ( $tables as &$table ) {
            $table['total_size_human'] = size_format( $table['total_size'] );
            $total_size += $table['total_size'];
            $total_rows += $table['rows'];
        }

        return [
            'tables'           => $tables,
            'table_count'      => count( $tables ),
            'total_size'       => $total_size,
            'total_size_human' => size_format( $total_size ),
            'total_rows'       => $total_rows,
        ];
    }

    /**
     * Repair database tables
     */
    public static function repair( array $input = [] ): array {
        global $wpdb;

        $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
        $repaired = [];
        $failed = [];

        foreach ( $tables as $table ) {
            $table_name = $table[0];

            if ( strpos( $table_name, $wpdb->prefix ) !== 0 ) {
                continue;
            }

            $result = $wpdb->query( "REPAIR TABLE `{$table_name}`" );

            if ( $result !== false ) {
                $repaired[] = $table_name;
            } else {
                $failed[] = $table_name;
            }
        }

        return self::successResponse(
            [
                'repaired' => $repaired,
                'failed'   => $failed,
            ],
            sprintf(
                __( 'Repaired %d tables, %d failed', 'wp-site-manager' ),
                count( $repaired ),
                count( $failed )
            )
        );
    }
}
