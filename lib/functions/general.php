<?php
/**
 * General functions
 *
 * This file contains general functions for the UCSC Giving Functionality Plugin
 *
 * @since 1.0.0
 * @package ucsc-giving-functionality
 */

defined( 'ABSPATH' ) || exit;

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
 * Funds with a "Standard" fund type link out to the external giving site
 * rather than to their own single post. Everything else is left untouched.
 *
 * @param string  $post_link The post's permalink.
 * @param WP_Post $post      The post being linked to.
 * @return string The external giving URL, or the unmodified permalink.
 */
function ucscgiving_link_filter( $post_link, $post ) {
	if ( 'fund' !== $post->post_type ) {
		return $post_link;
	}

	// Read against $post->ID rather than loop state, so this is correct in
	// admin list tables, REST responses and feeds as well as the main loop.
	$fund_id = get_field( 'fund-type-term', $post->ID );

	if ( empty( $fund_id ) ) {
		return $post_link;
	}

	$fund = get_term( $fund_id );

	if ( is_wp_error( $fund ) || ! $fund instanceof WP_Term || 'Standard' !== $fund->name ) {
		return $post_link;
	}

	$baseurl = get_field( 'base_url', 'option' );

	if ( empty( $baseurl ) ) {
		return $post_link;
	}

	$designation = get_post_meta( $post->ID, 'designation', true );

	return esc_url( $baseurl . $designation );
}

add_filter( 'post_type_link', 'ucscgiving_link_filter', 10, 2 );

/**
 * Register Search block variation for Fund post type
 * description: Registers a custom block variation for the Fund post type
 *
 * @param array         $variations Registered variations for the block type.
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
 * @param string $template Path to the search template WordPress resolved.
 * @return string Empty string to fall through to the archive template, or the original path.
 */
function ucscgiving_fund_search_template( $template ) {
	if ( is_search() && 'fund' === get_query_var( 'post_type' ) ) {
		return locate_template( '' ); // this will return search results in the archive template.
	}

	return $template;
}

add_filter( 'search_template', 'ucscgiving_fund_search_template' );
