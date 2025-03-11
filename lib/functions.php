<?php
/**
 * General functions
 */

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
		$termID = get_field('type-of-fund');
		$term = get_term($termID);
		$fundurl = '';
		if ( ( 'fund' === $post->post_type ) ) {
			if( ! is_wp_error( $term ) ) {
				$term_name = $term->name;
				if ($term_name === 'Standard') {
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

