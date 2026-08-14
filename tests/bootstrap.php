<?php
/**
 * PHPUnit bootstrap file.
 *
 * Dual mode, following tests/bootstrap.php in ucsc/ucsc-blocks:
 *
 * - Integration: when WP_TESTS_DIR points at a wordpress-develop checkout's
 *   tests/phpunit directory, the real WordPress test suite is loaded and the
 *   plugin is activated inside it.
 * - Standalone (the default): tests/stubs.php supplies the minimum WordPress
 *   stand-ins needed to load the plugin, so the suite runs on bare PHP plus
 *   Composer — no WordPress, no database, no ACF Pro licence and no
 *   ucsc-2022 theme.
 *
 * Run:
 *   composer test
 *   WP_TESTS_DIR=/path/to/wordpress-develop/tests/phpunit composer test
 *
 * @package ucsc-giving-functionality
 */

$ucscgiving_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $ucscgiving_tests_dir || '' === $ucscgiving_tests_dir ) {
	$ucscgiving_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( file_exists( $ucscgiving_tests_dir . '/includes/functions.php' ) ) {

	require_once $ucscgiving_tests_dir . '/includes/functions.php';

	/**
	 * Load the plugin under test inside the WordPress test suite.
	 *
	 * @return void
	 */
	function ucscgiving_manually_load_plugin() {
		require dirname( __DIR__ ) . '/plugin.php';
	}

	tests_add_filter( 'muplugins_loaded', 'ucscgiving_manually_load_plugin' );

	require $ucscgiving_tests_dir . '/includes/bootstrap.php';

} else {
	// Standalone mode. The stubs must be in place before plugin.php loads:
	// plugin.php calls plugin_dir_path() and plugin_basename() at file scope,
	// and both plugin.php and lib/functions/*.php register hooks at file
	// scope via add_action()/add_filter().
	require_once __DIR__ . '/doubles/class-ucscgiving-test-state.php';
	require_once __DIR__ . '/stubs.php';
	require_once dirname( __DIR__ ) . '/plugin.php';
}
