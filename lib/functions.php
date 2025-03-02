<?php
/**
 * General functions
 */

/**
 * Enable theme support for post formats
 */
add_theme_support( 'post-formats', array( 'link' ) );

/**
 * Register custom ACF Text Meta Fields
 *
 * Fields are defined in the ACF UI in the WP Dashboard
 * but as of WP 6.7.0, they still need to also be registered
 * in code to be available in the REST API. This will not be
 * necessary in future versions of WP.
 */

add_action( 'init', 'ucscgiving_register_text_meta' );

function ucscgiving_register_text_meta() {
	$fields = array(
		'aq_code' => 'AQ_Code',
		'button_text' => 'Fund button text',
	);
	foreach ($fields as $slug => $label) {
		register_post_meta(
			'fund',
			$slug,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'wp_strip_all_tags',
				'label'							=> _($label),
			)
		);
	}
}

/** 
 * Register Custom Block Binding Source
 *
 * Registers a custom callback that concatenates
 * the Giving BASE url with the AQ_Code
 */
add_action( 'init', 'ucscgiving_register_fund_url_block_binding' );

function ucscgiving_register_fund_url_block_binding() {
	register_block_bindings_source( 'ucscgiving/fund-url', array(
		'label'              => __( 'Fund URL', 'ucsc' ),
		'get_value_callback' => 'ucscgiving_fund_url'
	) );
}

function ucscgiving_fund_url() {
	$baseurl = 'https://give.ucsc.edu/campaigns/38026/donations/new?designation=';
	$aqcode = get_post_meta( get_the_ID(), 'aq_code', true );
	$fundurl = '';

	if ( !empty($aqcode) ) {
		$fundurl = $baseurl . $aqcode;
	} else {
		$fundurl = $baseurl;
	}
	
	return esc_url( $fundurl );
}

/**
*	Set permalinks to the external Giving URL  
*/
if ( ! function_exists( 'ucscgiving_link_filter' ) ){
	function ucscgiving_link_filter($post_link, $post) {
		$baseurl = 'https://give.ucsc.edu/campaigns/38026/donations/new?designation=';
		$aqcode = get_post_meta( get_the_ID(), 'aq_code', true );
		$fundurl = '';
		if ( ( 'fund' === $post->post_type ) ) {
			
			if (has_post_format('link', $post)){
				$fundurl = esc_attr($baseurl . $aqcode);
				return $fundurl;
			}
		}
		return $post_link;
	}
}
add_filter('post_type_link', 'ucscgiving_link_filter', 10, 2);

/**
 * Customize Admin Columns for Fund Post Type
 */
// Register the columns
add_filter( 'manage_fund_posts_columns', 'ucscgiving_fund_columns' );
function ucscgiving_fund_columns( $columns ) {
	$columns['area'] = __('Areas', 'ucsccgiving');
	$columns['keyword'] = __('Keywords', 'ucsccgiving');
	$columns['cause'] = __('Causes', 'ucsccgiving');
	$columns['format'] = __('Format', 'ucsccgiving');
	return $columns;
}

// Populate the columns
add_action( 'manage_fund_posts_custom_column', 'ucscgiving_fund_columns_data', 10, 2 );
function ucscgiving_fund_columns_data( $column, $post_id ) {
	switch ( $column ) {
		case 'area' :
			$terms = get_the_term_list( $post_id , 'area' , '' , ',' , '' );
			if ( is_string( $terms ) )
					echo $terms;
			else
					_e( 'No areas', 'ucscgiving' );
			break;
		case 'keyword' :
			$terms = get_the_term_list( $post_id , 'keyword' , '' , ',' , '' );
			if ( is_string( $terms ) )
					echo $terms;
			else
					_e( 'No keywords', 'ucscgiving' );
			break;
		case 'cause' :
			$terms = get_the_term_list( $post_id , 'cause' , '' , ',' , '' );
			if ( is_string( $terms ) )
					echo $terms;
			else
					_e( 'No causes', 'ucscgiving' );
			break;
		case 'format' :
			echo get_post_format() ? : 'Priority';
			break;
	}
}