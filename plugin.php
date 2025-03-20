<?php
/**
 * Plugin Name: UCSC Giving Functionality
 * Plugin URI: https://github.com/ucsc/ucsc-giving-functionality-plugin
 * Description: Adds custom functionality to UCSC Giving Website.
 * Version: 0.5.1
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
define( 'UCSC_GIVING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UCSC_GIVING_PLUGIN_BASE', plugin_basename( __FILE__ ) );

// Include general functions.
if ( file_exists( UCSC_GIVING_PLUGIN_DIR . 'lib/functions/general.php' ) ) {
	require_once UCSC_GIVING_PLUGIN_DIR . 'lib/functions/general.php';
}
// Include settings.
if ( file_exists( UCSC_GIVING_PLUGIN_DIR . '/lib/functions/settings.php' ) ) {
	include_once UCSC_GIVING_PLUGIN_DIR . '/lib/functions/settings.php';
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
	function ucscgiving_enqueue_admin_styles( $hook ): void {
		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$current_screen = get_current_screen();
		// Check if it's "?page=ucsc-giving-functionality-settings." If not, just empty return.
		if ( strpos( $current_screen->base, 'ucsc-giving-functionality-settings' ) === false ) {
			return;
		}

		// Load css.
		wp_register_style( 'ucscgiving-cf-admin-settings', $settings_css, );
		wp_enqueue_style( 'ucscgiving-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucscgiving_enqueue_admin_styles' );

// Set plugin directory for syncing ACF JSON files.
add_filter( 'acf/settings/save_json', 'ucscgiving_acf_json_save_point' );
function ucscgiving_acf_json_save_point( $path ) {
	$path = UCSC_GIVING_PLUGIN_DIR . 'acf-json';
	return $path;
}

// Set plugin directory for loading ACF JSON files.
add_filter( 'acf/settings/load_json', 'ucscgiving_acf_json_load_point' );
function ucscgiving_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = UCSC_GIVING_PLUGIN_DIR . 'acf-json';
	return $paths;
}
