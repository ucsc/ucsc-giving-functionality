<?php
/**
 * Tests for the ACF JSON save and load points.
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the two filters that keep acf-json/ inside this plugin.
 */
class AcfJsonPointsTest extends TestCase {

	/**
	 * The save point always points at this plugin's acf-json directory.
	 *
	 * @return void
	 */
	public function test_save_point_returns_plugin_acf_json_directory() {
		$this->assertSame(
			UCSCGIVING_PLUGIN_DIR . 'acf-json',
			ucscgiving_acf_json_save_point( '/some/other/path' )
		);
	}

	/**
	 * The load point drops ACF's own default path and appends this plugin's.
	 *
	 * @return void
	 */
	public function test_load_point_replaces_the_default_path() {
		$paths = ucscgiving_acf_json_load_point( array( '/acf/default', '/another/path' ) );

		$this->assertNotContains( '/acf/default', $paths );
		$this->assertContains( '/another/path', $paths );
		$this->assertContains( UCSCGIVING_PLUGIN_DIR . 'acf-json', $paths );
	}

	/**
	 * The plugin's path is appended last, so it wins on load order.
	 *
	 * @return void
	 */
	public function test_load_point_appends_plugin_path_last() {
		$paths = array_values( ucscgiving_acf_json_load_point( array( '/acf/default' ) ) );

		$this->assertSame( UCSCGIVING_PLUGIN_DIR . 'acf-json', end( $paths ) );
	}

	/**
	 * Documents a latent fragility rather than asserting desired behaviour:
	 * the function removes index 0 specifically, so if ACF ever passes an
	 * array that is not zero-indexed, nothing is removed and the default
	 * path survives alongside the plugin's.
	 *
	 * @return void
	 */
	public function test_load_point_does_not_remove_a_non_zero_indexed_default() {
		$paths = ucscgiving_acf_json_load_point( array( 3 => '/acf/default' ) );

		$this->assertContains( '/acf/default', $paths );
		$this->assertContains( UCSCGIVING_PLUGIN_DIR . 'acf-json', $paths );
	}
}
