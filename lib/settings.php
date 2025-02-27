<?php
/**
 * Add Plugin settings and info page
 *
 * This file contains functions to add a settings/info page below WordPress Settings menu
 *
 * @package      ucsc-giving-functionality
 * @since        0.1.0
 * @link         https://github.com/ucsc/ucsc-giving-functionality-plugin
 * @author       UC Santa Cruz
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/** 
 * Register new menu and page
 */
if ( ! function_exists( 'ucscgiving_add_settings_page' ) ) {
	function ucscgiving_add_settings_page() {
		add_options_page( 'UCSC Giving Functionality plugin page', 'UCSC Giving Functionality', 'manage_options', 'ucsc-news-functionality-settings', 'ucscgiving_render_plugin_settings_page' );
	}
}
add_action( 'admin_menu', 'ucscgiving_add_settings_page' );


/** 
 * HTML output of Settings page 
 *
 * note: This page typically displays a form for displaying any settings options. 
 * It is not needed at this point.
 * https://developer.wordpress.org/plugins/settings/custom-settings-page/
 */
if ( ! function_exists( 'ucscgiving_render_plugin_settings_page' ) ) {
	function ucscgiving_render_plugin_settings_page() {
		$plugin_data = get_plugin_data( UCSC_GIVING_PLUGIN_DIR . '/plugin.php');
		?>
		<div class="wrap ucscgiving-admin-settings-page">
		<h1><?php echo $plugin_data['Name']; ?></h1>
		<h2>Version: <?php echo $plugin_data['Version']; ?> <a href="https://github.com/ucsc/ucsc-news-functionality/releases">(release notes)</a></h2>
		<p><?php echo $plugin_data['Description']; ?></p>
		<hr>
		<h3>Features added by this plugin:</h3>
		
		</div>
		<?php
	}
}