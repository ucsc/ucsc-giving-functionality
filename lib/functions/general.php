<?php
/**
 * General functions
 *
 * This file contains general functions for the UCSC Giving Functionality Plugin
 *
 * @since 1.0.0
 * @package ucsc-giving-functionality
 */

add_action( 'init', 'ucscgiving_register_fund_url_block_binding' );

/**
 * Register Custom Block Binding Source
 *
 * Registers a custom callback that concatenates
 * the Giving BASE url with the Fund Designation Code
 *
 * @return void
 */
function ucscgiving_register_fund_url_block_binding() {
	register_block_bindings_source(
		'ucscgiving/fund-url',
		array(
			'label'              => __( 'Fund URL', 'ucscgiving' ),
			'get_value_callback' => 'ucscgiving_fund_url',
		)
	);
}

/**
 * Get Fund URL
 *
 * Callback function that returns the Fund URL
 *
 * @return string
 */
function ucscgiving_fund_url() {
	$baseurl     = get_field( 'base_url', 'option' );
	$designation = get_post_meta( get_the_ID(), 'designation', true );
	$fundurl     = '';

	if ( ! empty( $baseurl ) && ! empty( $designation ) ) {
		$fundurl = $baseurl . $designation;
	} elseif ( ! empty( $baseurl ) ) {
		$fundurl = $baseurl;
	} else {
		$fundurl = '';
	}

	return esc_url( $fundurl );
}

/**
 * Set permalinks to the external Giving URL
 *
 * @return string
 */
function ucscgiving_link_filter( $post_link, $post ) {
		$baseurl     = get_field( 'base_url', 'option' );
		$designation = get_post_meta( get_the_ID(), 'designation', true );
		$fund_id     = get_field( 'fund-type-term' );
		$fund        = get_term( $fund_id );
		$fundurl     = '';
	if ( ( 'fund' === $post->post_type ) ) {
		if ( ! is_wp_error( $fund ) ) {
			$fundname = $fund->name;
			if ( $fundname === 'Standard' ) {
				if ( ! empty( $baseurl ) && ! empty( $designation ) ) {
					$fundurl = esc_attr( $baseurl . $designation );
				} elseif ( ! empty( $baseurl ) ) {
					$fundurl = esc_attr( $baseurl );
				} else {
					$fundurl = '';
				}
				return $fundurl;
			}
		}
	}
		return $post_link;
}

add_filter( 'post_type_link', 'ucscgiving_link_filter', 10, 2 );

/**
 * Register Search block variation for Fund post type
 * description: Registers a custom block variation for the Fund post type
 *
 * @param mixed         $variations
 * @param WP_Block_Type $block_type The block type being filtered.
 * @return mixed
 */
function ucscgiving_create_fund_search_variation( $variations, $block_type ) {
	if ( 'core/search' !== $block_type->name ) {
			return $variations;
	}

		$variations[] = array(
			'name'        => 'fund-search',
			'title'       => __( 'Fund Search', 'ucscgiving' ),
			'description' => __( 'Search only Funds posts', 'ucscgiving' ),
			'attributes'  => array(
				'query'       => array(
					'post_type' => 'fund',
				),
				'placeholder' => __( 'Search Funds', 'ucscgiving' ),
				'buttonText'  => __( 'Search Funds', 'ucscgiving' ),
				'label'       => __( 'Search Funds', 'ucscgiving' ),
			),
		);

		return $variations;
}

add_filter( 'get_block_type_variations', 'ucscgiving_create_fund_search_variation', 10, 2 );

/**
 * Return Fund search results in Fund archive template
 * description: Returns the Fund search results in its archive template.
 *
 * @param string $template
 * @return string
 */
function ucscgiving_fund_search_template( $template ) {
	if ( is_search() && 'fund' === get_query_var( 'post_type' ) ) {
		return locate_template( '' ); // this will return search results in the archive template.
	}

	return $template;
}

add_action( 'search_template', 'ucscgiving_fund_search_template' );
