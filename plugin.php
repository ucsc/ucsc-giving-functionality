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
	 * @since 0.5.0
	 * @author UCSC
	 * @package ucsc-giving-functionality
	 */
	function ucscgiving_enqueue_admin_styles(): void {
		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$current_screen = get_current_screen();
		$plugin_data    = get_plugin_data( UCSCGIVING_PLUGIN_DIR . '/plugin.php' );
		$plugin_version = $plugin_data['Version'];
		if ( strpos( $current_screen->base, 'ucsc-giving-functionality-settings' ) === false ) {
			return;
		}
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
 * @package ucsc-giving-functionality
 */
function ucscgiving_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = UCSCGIVING_PLUGIN_DIR . 'acf-json';
	return $paths;
}
// Set plugin directory for loading ACF JSON files.
add_filter( 'acf/settings/load_json', 'ucscgiving_acf_json_load_point' );


/**
 * Callback function to retrieve custom template content
 *
 * @param $template
 * @parameter $template
 * @return $template
 * @package ucsc-giving-functionality
 */
function ucscgiving_get_template_content( $template ) {
	ob_start();
	include __DIR__ . "/lib/templates/{$template}";
	return ob_get_clean();
}

/**
 * Register Fund block templates
 *
 * @return void
 * @package ucsc-giving-functionality
 */
function ucscgiving_register_block_templates() {
	$templates = array(
		'archive-fund'       => array(
			'title'       => __( 'Fund Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for Giving Funds.', 'ucscgiving' ),
		),
		'taxonomy-area'      => array(
			'title'       => __( 'Fund Area Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund areas.', 'ucscgiving' ),
		),
		'taxonomy-cause'     => array(
			'title'       => __( 'Fund Cause Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund causes.', 'ucscgiving' ),
		),
		'taxonomy-fund-type' => array(
			'title'       => __( 'Fund Type Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund types.', 'ucscgiving' ),
		),
		'taxonomy-keyword'   => array(
			'title'       => __( 'Fund Keyword Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund keywords.', 'ucscgiving' ),
		),
		'single-fund'        => array(
			'title'       => __( 'Single Funds Posts', 'ucscgiving' ),
			'description' => __( 'Displays the single post template for Funds.', 'ucscgiving' ),
		),
	);

	foreach ( $templates as $slug => $data ) {
		register_block_template(
			'ucscgiving//' . $slug,
			array(
				'title'       => $data['title'],
				'description' => $data['description'],
				'content'     => ucscgiving_get_template_content( $slug . '.php' ),
			)
		);
	}
}

add_action( 'init', 'ucscgiving_register_block_templates' );
