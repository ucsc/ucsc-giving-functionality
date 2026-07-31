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

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ucscgiving_add_settings_page' ) ) {
	/**
	 * Register new menu and page in WordPress Settings
	 *
	 * @return void
	 */
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
	/**
	 * Render the plugin settings/info page.
	 *
	 * @return void
	 */
	function ucscgiving_render_plugin_settings_page() {
		$plugin_data        = get_plugin_data( UCSCGIVING_PLUGIN_DIR . '/plugin.php' );
		$plugin_name        = $plugin_data['Name'];
		$plugin_version     = $plugin_data['Version'];
		$plugin_description = $plugin_data['Description'];
		?>
		<div class="wrap giving-cf-admin-settings-page">
		<h1><?php echo esc_html( $plugin_name ); ?></h1>
		<h2>Version: <?php echo esc_html( $plugin_version ); ?> <a href="<?php echo esc_url( 'https://github.com/ucsc/ucsc-giving-functionality/releases' ); ?>">(release notes)</a></h2>
		<p><?php echo wp_kses_post( $plugin_description ); ?></p>
		<hr>
		<h3>Features added by this plugin:</h3>
		<ul>
			<li><strong>Fund custom post type</strong> &mdash; Registers a <code>fund</code> post type for managing giving fund listings.</li>
			<li><strong>Fund taxonomies</strong> &mdash; Registers four custom taxonomies: <em>Areas</em>, <em>Keywords</em>, <em>Fund Type</em>, and <em>Themes</em>.</li>
			<li><strong>ACF field groups</strong> &mdash; Bundles ACF JSON definitions for <em>Fund Details</em> and <em>Giving Options</em> (options page) fields.</li>
			<li><strong>External giving URL filter</strong> &mdash; Rewrites fund permalink to the external giving URL (base URL + designation code) for funds with a <em>Standard</em> fund type.</li>
			<li><strong>Fund URL block binding</strong> &mdash; Registers a <code>ucscgiving/fund-url</code> block bindings source that exposes the computed giving URL to the block editor.</li>
			<li><strong>Fund Search block variation</strong> &mdash; Adds a <em>Fund Search</em> variation of the core Search block scoped to the <code>fund</code> post type.</li>
			<li><strong>Fund search template redirect</strong> &mdash; Routes fund post-type search queries through the fund archive template.</li>
			<li><strong>Block templates</strong> &mdash; Registers six block templates: <em>Fund Archives</em>, <em>Single Fund</em>, <em>Fund Area Archives</em>, <em>Fund Theme Archives</em>, <em>Fund Type Archives</em>, and <em>Fund Keyword Archives</em>.</li>
		</ul>
		</div>
		<?php
	}
}

add_filter( 'plugin_action_links_' . UCSCGIVING_PLUGIN_BASE, 'ucscgiving_settings_link' );

/**
 * Add link to plugin settings page from plugin list
 *
 * @param array $links Existing plugin action links.
 * @return array Action links with the settings link appended.
 */
function ucscgiving_settings_link( $links ) {
	// menu_page_url() resolves the registered parent (options-general.php),
	// so this stays correct if the page is ever re-parented. It returns an
	// empty string when the page was never registered — which happens when
	// add_options_page() bailed on a capability check — so fall back to the
	// known parent rather than dropping the link entirely.
	$url = menu_page_url( 'ucsc-giving-functionality-settings', false );

	if ( empty( $url ) ) {
		$url = admin_url( 'options-general.php?page=ucsc-giving-functionality-settings' );
	}

	$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ucscgiving' ) . '</a>';

	return $links;
}
