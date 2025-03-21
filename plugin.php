<?php
/**
 * Plugin Name: UCSC Giving Functionality
 * Plugin URI: https://github.com/ucsc/ucsc-giving-functionality-plugin
 * Description: Adds custom functionality to UCSC Giving Website.
 * Version: 0.5.2
 * Requires at least: 6.5.0
 * Author: UC Santa Cruz
 * Author URI: https://github.com/ucsc
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI: https://github.com/ucsc/ucsc-giving-functionality-plugin/releases
 * Requires Plugins: advanced-custom-fields-pro
 * Text Domain: ucscgiving
 *
 * @package ucsc-giving-functionality
 */

// Set plugin directory and base name.
define( 'UCSCGIVING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Path to plugin directory.
define( 'UCSCGIVING_PLUGIN_BASE', plugin_basename( __FILE__ ) ); // Plugin base name 'plugin.php' at root.

// Include general functions.
if ( file_exists( UCSCGIVING_PLUGIN_DIR . 'lib/functions/general.php' ) ) {
	require_once UCSCGIVING_PLUGIN_DIR . 'lib/functions/general.php';
}
// Include settings.
if ( file_exists( UCSCGIVING_PLUGIN_DIR . '/lib/functions/settings.php' ) ) {
	include_once UCSCGIVING_PLUGIN_DIR . '/lib/functions/settings.php';
}
// Enqueue admin settings styles.
if ( ! function_exists( 'ucscgiving_enqueue_admin_styles' ) ) {
	/**
	 * Enqueue admin settings styles
	 *
	 * No styles are enqueued for raw HTML in setting panel.
	 * In order to output HTML in the settings panel we need some basic styles.
	 *
	 * @since 0.5.0
	 *
	 * @author UCSC
	 *
	 * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/#Example:_Load_CSS_File_from_a_plugin_on_specific_Admin_Page
	 */
	function ucscgiving_enqueue_admin_styles(): void {
		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$current_screen = get_current_screen();
		$plugin_data    = get_plugin_data( UCSCGIVING_PLUGIN_DIR . '/plugin.php' );
		$plugin_version = $plugin_data['Version'];
		// Check if it's "?page=ucsc-giving-functionality-settings." If not, just empty return.
		if ( strpos( $current_screen->base, 'ucsc-giving-functionality-settings' ) === false ) {
			return;
		}

		// Load css.
		wp_register_style( 'ucscgiving-cf-admin-settings', $settings_css, '', $plugin_version );
		wp_enqueue_style( 'ucscgiving-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucscgiving_enqueue_admin_styles' );

/**
 * ACF JSON Save Point
 *
 * @param [type] $path
 * @return $path
 * Description
 * @package ucsc-giving-functionality
 */
function ucscgiving_acf_json_save_point( $path ) {
	$path = UCSCGIVING_PLUGIN_DIR . 'acf-json';
	return $path;
}
// Set plugin directory for saving ACF JSON files.
add_filter( 'acf/settings/save_json', 'ucscgiving_acf_json_save_point' );

/**
 * ACF JSON Load Point
 *
 * @param [type] $paths
 * @return $paths
 * Description
 * @package ucsc-giving-functionality
 */
function ucscgiving_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = UCSCGIVING_PLUGIN_DIR . 'acf-json';
	return $paths;
}
// Set plugin directory for loading ACF JSON files.
add_filter( 'acf/settings/load_json', 'ucscgiving_acf_json_load_point' );
