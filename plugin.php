<?php
/**
 * Plugin Name: UCSC Giving Functionality
 * Plugin URI: https://github.com/ucsc/ucsc-giving-functionality-plugin
 * Description: Adds custom functionality to UCSC Giving Website.
 * Version: 0.1.0
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

// Set plugin directory.
define( 'UCSC_GIVING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include general functions.
if ( file_exists( UCSC_GIVING_PLUGIN_DIR . 'lib/functions.php' ) ) {
	require_once UCSC_GIVING_PLUGIN_DIR . 'lib/functions.php';
}
// Include settings.
if ( file_exists( UCSC_GIVING_PLUGIN_DIR . '/lib/settings.php' ) ) {
	include_once UCSC_GIVING_PLUGIN_DIR . '/lib/settings.php';
}

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
