<?php
/**
 * General functions
 */

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
		$termID = get_field('priority-fund');
		$term = get_term($termID);
		$fundurl = '';
		if ( ( 'fund' === $post->post_type ) ) {
			if( ! is_wp_error( $term ) ) {
				$term_name = $term->name;
				if ($term_name === 'Link') {
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
		}
		return $post_link;
}

