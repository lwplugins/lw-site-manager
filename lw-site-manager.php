<?php
/**
 * Plugin Name: Lightweight Site Manager
 * Plugin URI: https://github.com/lwplugins/lw-site-manager
 * Description: WordPress Site Manager using Abilities API - Full site maintenance via AI/REST
 * Version: 1.1.6
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: LW Plugins
 * Author URI: https://lwplugins.com
 * License: GPL-2.0-or-later
 * Text Domain: lw-site-manager
 *
 * @package LightweightPlugins\SiteManager
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'LW_SITE_MANAGER_VERSION', '1.1.6' );
define( 'LW_SITE_MANAGER_FILE', __FILE__ );
define( 'LW_SITE_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
define( 'LW_SITE_MANAGER_URL', plugin_dir_url( __FILE__ ) );

// Composer autoloader.
if ( file_exists( LW_SITE_MANAGER_DIR . 'vendor/autoload.php' ) ) {
	require_once LW_SITE_MANAGER_DIR . 'vendor/autoload.php';
}

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Check for Abilities API.
		add_action( 'plugins_loaded', [ $this, 'check_dependencies' ] );

		// Register ability categories first.
		add_action( 'wp_abilities_api_categories_init', [ $this, 'register_categories' ] );

		// Register abilities.
		add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );

		// Error handler for updates.
		add_action( 'init', [ $this, 'init_error_handler' ] );

		// Initialize backup cron hooks.
		add_action( 'init', [ $this, 'init_backup_system' ] );
	}

	/**
	 * Check dependencies.
	 *
	 * @return void
	 */
	public function check_dependencies(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'LW Site Manager requires WordPress 6.9 or higher (Abilities API).', 'lw-site-manager' );
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Register ability categories.
	 *
	 * @return void
	 */
	public function register_categories(): void {
		// Maintenance category.
		wp_register_ability_category(
			'maintenance',
			[
				'label'       => __( 'Maintenance', 'lw-site-manager' ),
				'description' => __( 'Site maintenance abilities including updates, backups, and optimization', 'lw-site-manager' ),
			]
		);

		// Diagnostics category.
		wp_register_ability_category(
			'diagnostics',
			[
				'label'       => __( 'Diagnostics', 'lw-site-manager' ),
				'description' => __( 'Site health and diagnostic abilities', 'lw-site-manager' ),
			]
		);

		// Plugins category.
		wp_register_ability_category(
			'plugins',
			[
				'label'       => __( 'Plugins', 'lw-site-manager' ),
				'description' => __( 'Plugin management abilities', 'lw-site-manager' ),
			]
		);

		// Themes category.
		wp_register_ability_category(
			'themes',
			[
				'label'       => __( 'Themes', 'lw-site-manager' ),
				'description' => __( 'Theme management abilities', 'lw-site-manager' ),
			]
		);

		// Users category.
		wp_register_ability_category(
			'users',
			[
				'label'       => __( 'Users', 'lw-site-manager' ),
				'description' => __( 'User management abilities', 'lw-site-manager' ),
			]
		);

		// Comments category.
		wp_register_ability_category(
			'comments',
			[
				'label'       => __( 'Comments', 'lw-site-manager' ),
				'description' => __( 'Comment management abilities', 'lw-site-manager' ),
			]
		);

		// Content category.
		wp_register_ability_category(
			'content',
			[
				'label'       => __( 'Content', 'lw-site-manager' ),
				'description' => __( 'Posts and pages management abilities', 'lw-site-manager' ),
			]
		);

		// Media category.
		wp_register_ability_category(
			'media',
			[
				'label'       => __( 'Media', 'lw-site-manager' ),
				'description' => __( 'Media library management abilities', 'lw-site-manager' ),
			]
		);

		// Taxonomy category.
		wp_register_ability_category(
			'taxonomy',
			[
				'label'       => __( 'Taxonomy', 'lw-site-manager' ),
				'description' => __( 'Category and tag management abilities', 'lw-site-manager' ),
			]
		);

		// Meta category.
		wp_register_ability_category(
			'meta',
			[
				'label'       => __( 'Meta', 'lw-site-manager' ),
				'description' => __( 'Post, user, and term meta management abilities', 'lw-site-manager' ),
			]
		);

		// Settings category.
		wp_register_ability_category(
			'settings',
			[
				'label'       => __( 'Settings', 'lw-site-manager' ),
				'description' => __( 'WordPress settings management abilities', 'lw-site-manager' ),
			]
		);

		// WooCommerce categories (only if WooCommerce is active).
		if ( class_exists( 'WooCommerce' ) || class_exists( '\WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', [] ), true ) ) {
			$this->register_woocommerce_categories();
		}
	}

	/**
	 * Register WooCommerce ability categories.
	 *
	 * @return void
	 */
	private function register_woocommerce_categories(): void {
		wp_register_ability_category(
			'wc-products',
			[
				'label'       => __( 'WooCommerce Products', 'lw-site-manager' ),
				'description' => __( 'WooCommerce product management abilities', 'lw-site-manager' ),
			]
		);

		wp_register_ability_category(
			'wc-orders',
			[
				'label'       => __( 'WooCommerce Orders', 'lw-site-manager' ),
				'description' => __( 'WooCommerce order management abilities', 'lw-site-manager' ),
			]
		);

		wp_register_ability_category(
			'wc-customers',
			[
				'label'       => __( 'WooCommerce Customers', 'lw-site-manager' ),
				'description' => __( 'WooCommerce customer management abilities', 'lw-site-manager' ),
			]
		);

		wp_register_ability_category(
			'wc-coupons',
			[
				'label'       => __( 'WooCommerce Coupons', 'lw-site-manager' ),
				'description' => __( 'WooCommerce coupon management abilities', 'lw-site-manager' ),
			]
		);

		wp_register_ability_category(
			'wc-settings',
			[
				'label'       => __( 'WooCommerce Settings', 'lw-site-manager' ),
				'description' => __( 'WooCommerce settings management abilities', 'lw-site-manager' ),
			]
		);

		wp_register_ability_category(
			'wc-reports',
			[
				'label'       => __( 'WooCommerce Reports', 'lw-site-manager' ),
				'description' => __( 'WooCommerce reports and analytics abilities', 'lw-site-manager' ),
			]
		);
	}

	/**
	 * Register abilities.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$registrar = new Abilities\Registrar();
		$registrar->register_all();
	}

	/**
	 * Initialize error handler.
	 *
	 * @return void
	 */
	public function init_error_handler(): void {
		Handlers\ErrorHandler::instance()->init();
	}

	/**
	 * Initialize backup system.
	 *
	 * @return void
	 */
	public function init_backup_system(): void {
		Services\BackupManager::init();
	}
}

// Initialize plugin.
add_action(
	'plugins_loaded',
	function () {
		Plugin::instance();
	},
	5
);
