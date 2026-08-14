<?php
/**
 * Plugin Name: UCSC Giving Functionality
 * Plugin URI: https://github.com/ucsc/ucsc-giving-functionality-plugin
 * Description: Adds custom functionality to UCSC Giving Website.
 * Version: 0.5.9
 * Requires at least: 6.5.0
 * Requires PHP: 8.1
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

defined( 'ABSPATH' ) || exit;

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
		$current_screen = get_current_screen();

		// get_current_screen() returns null outside a real admin screen (ajax, customizer).
		if ( ! $current_screen instanceof WP_Screen ) {
			return;
		}

		if ( false === strpos( $current_screen->base, 'ucsc-giving-functionality-settings' ) ) {
			return;
		}

		$settings_css   = plugin_dir_url( __FILE__ ) . 'lib/css/admin-settings.css';
		$plugin_data    = get_plugin_data( UCSCGIVING_PLUGIN_DIR . 'plugin.php' );
		$plugin_version = $plugin_data['Version'];

		wp_register_style( 'ucscgiving-cf-admin-settings', $settings_css, array(), $plugin_version );
		wp_enqueue_style( 'ucscgiving-cf-admin-settings' );
	}
}
add_action( 'admin_enqueue_scripts', 'ucscgiving_enqueue_admin_styles' );

// Enqueue the block editor script that makes Fund Type a single choice.
if ( ! function_exists( 'ucscgiving_enqueue_block_editor_assets' ) ) {
	/**
	 * Enqueue block editor assets for the Fund post type
	 *
	 * Loads the script that swaps the Fund Type checkbox list for radio
	 * buttons. Only the fund editor needs it, so every other screen is left
	 * alone.
	 *
	 * Both this and ucscgiving_enqueue_admin_styles() build their URL with
	 * plugin_dir_url( __FILE__ ), which resolves to the plugin root only
	 * because this file sits there. Moving either into lib/functions/ would
	 * silently produce a broken URL.
	 *
	 * @since 0.5.9
	 * @package ucsc-giving-functionality
	 */
	function ucscgiving_enqueue_block_editor_assets(): void {
		$current_screen = get_current_screen();

		// get_current_screen() returns null outside a real admin screen.
		if ( ! $current_screen instanceof WP_Screen ) {
			return;
		}

		if ( 'fund' !== $current_screen->post_type ) {
			return;
		}

		$script         = plugin_dir_url( __FILE__ ) . 'lib/js/fund-type-radio.js';
		$plugin_data    = get_plugin_data( UCSCGIVING_PLUGIN_DIR . 'plugin.php' );
		$plugin_version = $plugin_data['Version'];

		wp_register_script(
			'ucscgiving-fund-type-radio',
			$script,
			array( 'wp-hooks', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data' ),
			$plugin_version,
			true
		);

		// Translatable strings stay in PHP, where the ucscgiving text domain is
		// already enforced, rather than needing script translations.
		wp_localize_script(
			'ucscgiving-fund-type-radio',
			'ucscgivingFundType',
			array(
				'taxonomy' => 'fund-type',
				'label'    => __( 'Fund Type', 'ucscgiving' ),
			)
		);

		wp_enqueue_script( 'ucscgiving-fund-type-radio' );
	}
}
add_action( 'enqueue_block_editor_assets', 'ucscgiving_enqueue_block_editor_assets' );

/**
 * ACF JSON Save Point
 *
 * @param string $path Default ACF JSON save path.
 * @return string Path to this plugin's acf-json directory.
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
 * @param array $paths Default ACF JSON load paths.
 * @return array Load paths with this plugin's acf-json directory substituted in.
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
 * @param string $template Template filename, relative to lib/templates/.
 * @return string Rendered template markup.
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
		'archive-fund'        => array(
			'title'       => __( 'Fund Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for Giving Funds.', 'ucscgiving' ),
		),
		'taxonomy-area'       => array(
			'title'       => __( 'Fund Area Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund areas.', 'ucscgiving' ),
		),
		'taxonomy-fund-theme' => array(
			'title'       => __( 'Fund Theme Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund themes.', 'ucscgiving' ),
		),
		'taxonomy-fund-type'  => array(
			'title'       => __( 'Fund Type Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund types.', 'ucscgiving' ),
		),
		'taxonomy-keyword'    => array(
			'title'       => __( 'Fund Keyword Archives', 'ucscgiving' ),
			'description' => __( 'Displays the archive template for the Fund keywords.', 'ucscgiving' ),
		),
		'single-fund'         => array(
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
