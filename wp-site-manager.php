<?php
/**
 * Plugin Name: WP Site Manager
 * Plugin URI: https://github.com/trueqap/wp-site-manager
 * Description: WordPress Site Manager using Abilities API - Full site maintenance via AI/REST
 * Version: 1.0.3
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: trueqap
 * Author URI: https://github.com/trueqap
 * License: GPL-2.0-or-later
 * Text Domain: wp-site-manager
 */

declare(strict_types=1);

namespace WPSiteManager;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WPSM_VERSION', '1.0.3' );
define( 'WPSM_PLUGIN_FILE', __FILE__ );
define( 'WPSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Composer autoloader
if ( file_exists( WPSM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WPSM_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * Main plugin class
 */
final class Plugin {

    private static ?Plugin $instance = null;

    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        // Check for Abilities API
        add_action( 'plugins_loaded', [ $this, 'check_dependencies' ] );

        // Register ability categories first
        add_action( 'wp_abilities_api_categories_init', [ $this, 'register_categories' ] );

        // Register abilities
        add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );

        // Error handler for updates
        add_action( 'init', [ $this, 'init_error_handler' ] );

        // Initialize backup cron hooks
        add_action( 'init', [ $this, 'init_backup_system' ] );

        // Initialize self-updater
        add_action( 'init', [ $this, 'init_updater' ] );
    }

    public function init_updater(): void {
        $updater = new Updater\SelfUpdater();
        $updater->init();
    }

    public function check_dependencies(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                esc_html_e( 'WP Site Manager requires the WordPress Abilities API plugin to be active.', 'wp-site-manager' );
                echo '</p></div>';
            });
        }
    }

    public function register_categories(): void {
        // Maintenance category
        wp_register_ability_category( 'maintenance', [
            'label'       => __( 'Maintenance', 'wp-site-manager' ),
            'description' => __( 'Site maintenance abilities including updates, backups, and optimization', 'wp-site-manager' ),
        ]);

        // Diagnostics category
        wp_register_ability_category( 'diagnostics', [
            'label'       => __( 'Diagnostics', 'wp-site-manager' ),
            'description' => __( 'Site health and diagnostic abilities', 'wp-site-manager' ),
        ]);

        // Plugins category
        wp_register_ability_category( 'plugins', [
            'label'       => __( 'Plugins', 'wp-site-manager' ),
            'description' => __( 'Plugin management abilities', 'wp-site-manager' ),
        ]);

        // Themes category
        wp_register_ability_category( 'themes', [
            'label'       => __( 'Themes', 'wp-site-manager' ),
            'description' => __( 'Theme management abilities', 'wp-site-manager' ),
        ]);

        // Users category
        wp_register_ability_category( 'users', [
            'label'       => __( 'Users', 'wp-site-manager' ),
            'description' => __( 'User management abilities', 'wp-site-manager' ),
        ]);

        // Comments category
        wp_register_ability_category( 'comments', [
            'label'       => __( 'Comments', 'wp-site-manager' ),
            'description' => __( 'Comment management abilities', 'wp-site-manager' ),
        ]);

        // Content category
        wp_register_ability_category( 'content', [
            'label'       => __( 'Content', 'wp-site-manager' ),
            'description' => __( 'Posts and pages management abilities', 'wp-site-manager' ),
        ]);

        // Media category
        wp_register_ability_category( 'media', [
            'label'       => __( 'Media', 'wp-site-manager' ),
            'description' => __( 'Media library management abilities', 'wp-site-manager' ),
        ]);

        // Taxonomy category
        wp_register_ability_category( 'taxonomy', [
            'label'       => __( 'Taxonomy', 'wp-site-manager' ),
            'description' => __( 'Category and tag management abilities', 'wp-site-manager' ),
        ]);

        // Meta category
        wp_register_ability_category( 'meta', [
            'label'       => __( 'Meta', 'wp-site-manager' ),
            'description' => __( 'Post, user, and term meta management abilities', 'wp-site-manager' ),
        ]);

        // Settings category
        wp_register_ability_category( 'settings', [
            'label'       => __( 'Settings', 'wp-site-manager' ),
            'description' => __( 'WordPress settings management abilities', 'wp-site-manager' ),
        ]);

        // WooCommerce categories (only if WooCommerce is active)
        if ( class_exists( 'WooCommerce' ) || class_exists( '\WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', [] ), true ) ) {
            wp_register_ability_category( 'wc-products', [
                'label'       => __( 'WooCommerce Products', 'wp-site-manager' ),
                'description' => __( 'WooCommerce product management abilities', 'wp-site-manager' ),
            ]);

            wp_register_ability_category( 'wc-orders', [
                'label'       => __( 'WooCommerce Orders', 'wp-site-manager' ),
                'description' => __( 'WooCommerce order management abilities', 'wp-site-manager' ),
            ]);

            wp_register_ability_category( 'wc-customers', [
                'label'       => __( 'WooCommerce Customers', 'wp-site-manager' ),
                'description' => __( 'WooCommerce customer management abilities', 'wp-site-manager' ),
            ]);

            wp_register_ability_category( 'wc-coupons', [
                'label'       => __( 'WooCommerce Coupons', 'wp-site-manager' ),
                'description' => __( 'WooCommerce coupon management abilities', 'wp-site-manager' ),
            ]);

            wp_register_ability_category( 'wc-settings', [
                'label'       => __( 'WooCommerce Settings', 'wp-site-manager' ),
                'description' => __( 'WooCommerce settings management abilities', 'wp-site-manager' ),
            ]);

            wp_register_ability_category( 'wc-reports', [
                'label'       => __( 'WooCommerce Reports', 'wp-site-manager' ),
                'description' => __( 'WooCommerce reports and analytics abilities', 'wp-site-manager' ),
            ]);
        }
    }

    public function register_abilities(): void {
        $registrar = new Abilities\Registrar();
        $registrar->register_all();
    }

    public function init_error_handler(): void {
        Handlers\ErrorHandler::instance()->init();
    }

    public function init_backup_system(): void {
        Services\BackupManager::init();
    }
}

// Initialize plugin
add_action( 'plugins_loaded', function() {
    Plugin::instance();
}, 5 );
