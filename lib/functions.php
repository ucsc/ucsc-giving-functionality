<?php
/**
 * General functions
 */

/**
 * Enable theme support for post formats
 */

add_theme_support( 'post-formats', array( 'link' ) );

// Remove post-format support for posts
add_action( 'init', 'ucscgiving_remove_post_format_support' );

function ucscgiving_remove_post_format_support() {
	remove_post_type_support( 'post', 'post-formats' );
}

/**
 * Register custom ACF Text Meta Fields for Block binding
 *
 * Fields are defined in the ACF UI in the WP Dashboard
 * but as of WP 6.7.0, they still need to also be registered
 * in code to be available in the REST API. This will not be
 * necessary in future versions of WP.
 */

add_action( 'init', 'ucscgiving_register_text_meta' );

function ucscgiving_register_text_meta() {
	$fields = array(
		'button_text' => 'Fund button text',
	);
	foreach ( $fields as $slug => $label ) {
		register_post_meta(
			'fund',
			$slug,
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'wp_strip_all_tags',
				'label'							=> _( $label ),
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
	$baseurl = get_field('base_url', 'option');
	$designation = get_post_meta( get_the_ID(), 'designation', true );
	$fundurl = '';

	if ( !empty( $baseurl ) && !empty( $designation ) ) {
		$fundurl = $baseurl . $designation;
	} else if ( !empty( $baseurl ) ) {
		$fundurl = $baseurl;
	} else {
		$fundurl = '';
	}
	
	return esc_url( $fundurl );
}

/**
 * Set permalinks to the external Giving URL  
 */

add_filter('post_type_link', 'ucscgiving_link_filter', 10, 2);

function ucscgiving_link_filter($post_link, $post) {
		$baseurl = get_field('base_url', 'option');
		$designation = get_post_meta( get_the_ID(), 'designation', true );
		$fundurl = '';
		if ( ( 'fund' === $post->post_type ) ) {
			
			if (has_post_format('link', $post)){
				if ( !empty( $baseurl ) && !empty( $designation ) ) { 
					$fundurl = esc_attr($baseurl . $designation);
				} else if ( !empty( $baseurl ) ) {
					$fundurl = esc_attr($baseurl);
				} else {
					$fundurl = '';
				}
				return $fundurl;
			}
		}
		return $post_link;
}


/**
 * Customize Admin Columns for Fund Post Type
 */
$post_type = 'fund';
// Add attribute support for the post type
add_post_type_support( $post_type, 'page-attributes' );

// Register the columns
add_filter( 'manage_'.$post_type.'_posts_columns', 'ucscgiving_fund_columns' );

function ucscgiving_fund_columns( $columns ) {
	$columns['format'] = __('Format', 'ucsccgiving');
	return $columns;
}

// Populate the columns
add_action( 'manage_'. $post_type .'_posts_custom_column', 'ucscgiving_fund_columns_data', 10, 2 );

function ucscgiving_fund_columns_data( $column, $post_id ) {
	switch ( $column ) {
		case 'format' :
			echo get_post_format() ? : 'Priority';
			break;
	}
}

// Make the columns sortable
add_filter( 'manage_edit-'.$post_type.'_sortable_columns', 'ucscgiving_fund_sortable_columns' );
function ucscgiving_fund_sortable_columns( $columns ) {
	$columns['format'] = __('Format', 'ucsccgiving');
	return $columns;
}
