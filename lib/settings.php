<?php
/**
 * Add Plugin settings and info page
 *
 * This file contains functions to add a settings/info page below WordPress Settings menu
 *
 * @package      ucsc-giving-functionality
 * @since        1.7.0
 * @link         https://github.com/ucsc/ucsc-giving-functionality.git
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Register new menu and page in WordPress Settings
 */

if ( ! function_exists( 'ucscgiving_add_settings_page' ) ) {
	function ucscgiving_add_settings_page() {
		add_options_page( 'UCSC Giving Functionality plugin page', 'UCSC Giving Functionality', 'manage_options', 'ucsc-giving-functionality-settings', 'ucscgiving_render_plugin_settings_page' );
	}
}
add_action( 'admin_menu', 'ucscgiving_add_settings_page' );


/**
 * HTML output of Settings page
 *
 * Note: This page typically displays a form for displaying any settings options.
 * It is not needed at this point.
 * https://developer.wordpress.org/plugins/settings/giving-settings-page/
 */

if ( ! function_exists( 'ucscgiving_render_plugin_settings_page' ) ) {
	function ucscgiving_render_plugin_settings_page() {
		$plugin_data        = get_plugin_data( UCSC_GIVING_PLUGIN_DIR . '/plugin.php' );
		$plugin_name        = $plugin_data['Name'];
		$plugin_version     = $plugin_data['Version'];
		$plugin_description = $plugin_data['Description'];
		?>
		<div class="wrap cf-admin-settings-page">
		<h1><?php echo $plugin_name; ?></h1>
		<h2>Version: <?php echo $plugin_version; ?> <a href="https://github.com/ucsc/ucsc-giving-functionality/releases">(release notes)</a></h2>
		<p><?php echo $plugin_description; ?></p>
		<hr>
		<h3>Features added by this plugin:</h3>
		</div>
		<?php
	}
}

/**
 * Add link to plugin settings page from plugin list
 */

add_filter( 'plugin_action_links_' . UCSC_GIVING_PLUGIN_BASE, 'ucscgiving_settings_link' );
function ucscgiving_settings_link( $links ) {
	// Build and escape the URL.
	$url = esc_url( add_query_arg(
		'page',
		'ucsc-giving-functionality-settings',
		get_admin_url() . 'admin.php'
	) );
	// Create the link.
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	// Adds the link to the end of the array.
	array_push(
		$links,
		$settings_link
	);
	return $links;
}