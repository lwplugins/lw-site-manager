<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package LightweightPlugins\SiteManager\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Check if we should use WordPress test environment.
// Set RUN_WP_TESTS=1 to enable WordPress integration tests.
$_run_wp_tests = getenv( 'RUN_WP_TESTS' );

if ( $_run_wp_tests ) {
	// =========================================================================
	// INTEGRATION TESTS - Full WordPress environment
	// =========================================================================

	$_tests_dir = getenv( 'WP_TESTS_DIR' );

	if ( ! $_tests_dir ) {
		$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}

	if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
		echo "\n\033[31mWordPress test suite not found at: $_tests_dir\033[0m\n";
		echo "Run: bash tests/bin/install-wp-tests.sh <db-name> <db-user> <db-pass>\n\n";
		exit( 1 );
	}

	// Give access to tests_add_filter() function.
	require_once $_tests_dir . '/includes/functions.php';

	/**
	 * Manually load the plugin being tested.
	 */
	function _manually_load_plugin(): void {
		require dirname( __DIR__ ) . '/lw-site-manager.php';
	}

	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	// Start up the WP testing environment.
	require $_tests_dir . '/includes/bootstrap.php';

	echo "\n\033[32mRunning INTEGRATION tests with WordPress " . get_bloginfo( 'version' ) . "\033[0m\n\n";

} else {
	// =========================================================================
	// UNIT TESTS - Isolated without WordPress
	// =========================================================================

	echo "\n\033[33mRunning UNIT tests (isolated, no WordPress)\033[0m\n\n";

	// Define WordPress constants if not already defined.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/wordpress/' );
	}

	if ( ! defined( 'WPINC' ) ) {
		define( 'WPINC', 'wp-includes' );
	}

	// Several services `require_once ABSPATH . 'wp-admin/includes/...'` before
	// calling an admin API. Those files are not present under unit tests, so
	// create empty placeholders — the functions themselves come from the stubs
	// below, this only satisfies the require.
	$_admin_includes = ABSPATH . 'wp-admin/includes';
	if ( ! is_dir( $_admin_includes ) ) {
		mkdir( $_admin_includes, 0777, true );
	}
	foreach (
		[
			'class-wp-upgrader.php',
			'file.php',
			'image.php',
			'media.php',
			'plugin-install.php',
			'plugin.php',
			'theme.php',
			'update.php',
			'user.php',
		] as $_admin_file
	) {
		$_path = $_admin_includes . '/' . $_admin_file;
		if ( ! file_exists( $_path ) ) {
			file_put_contents( $_path, "<?php\n// Placeholder for unit tests.\n" );
		}
	}
	unset( $_admin_includes, $_admin_file, $_path );

	// Load WordPress function stubs for unit tests.
	require_once __DIR__ . '/stubs/wordpress-functions.php';
}
