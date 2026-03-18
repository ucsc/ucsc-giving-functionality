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
	if ( 'fund' !== $post->post_type ) {
		return $post_link;
	}

	$terms = wp_get_post_terms( $post->ID, 'fund-type', array( 'fields' => 'names' ) );

	if ( ! is_wp_error( $terms ) && in_array( 'Standard', $terms, true ) ) {
		$baseurl     = get_field( 'base_url', 'option' );
		$designation = get_post_meta( $post->ID, 'designation', true );

		if ( ! empty( $baseurl ) && ! empty( $designation ) ) {
			return esc_attr( $baseurl . $designation );
		} elseif ( ! empty( $baseurl ) ) {
			return esc_attr( $baseurl );
		}
	}

	return $post_link;
}

add_filter( 'post_type_link', 'ucscgiving_link_filter', 10, 2 );

/**
 * Sync fund-type taxonomy after Block Editor (REST API) save
 *
 * WordPress REST API save order in WP_REST_Posts_Controller::update_item():
 *   1. wp_update_post() — saves post data, fires save_post.
 *   2. update_additional_fields_for_object() — saves ACF REST meta fields,
 *      including fund-type-term with the user's new selection.
 *   3. handle_terms() — processes taxonomy terms from the REST request, but
 *      the Block Editor's taxonomy entity state was loaded with the old value
 *      and is not updated when the ACF sidebar field changes, so this may
 *      overwrite the fund-type taxonomy back to its pre-save value.
 *   4. rest_after_insert_{post_type} — fires after all of the above.
 *
 * This hook runs at priority 999 in step 4 to re-sync the fund-type taxonomy
 * from the fund-type-term meta (correctly saved in step 2), overriding any
 * stale value written by handle_terms() in step 3.
 *
 * @param WP_Post $post Inserted or updated post object.
 * @return void
 */
function ucscgiving_sync_fund_type_term( $post ) {
	$term_id = absint( get_post_meta( $post->ID, 'fund-type-term', true ) );
	$terms   = $term_id > 0 ? array( $term_id ) : array();
	wp_set_post_terms( $post->ID, $terms, 'fund-type' );
}

add_action( 'rest_after_insert_fund', 'ucscgiving_sync_fund_type_term', 999, 1 );

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
