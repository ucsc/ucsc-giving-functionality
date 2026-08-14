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
 * Build the external Giving URL for a fund
 *
 * The single place the Giving URL is composed. Both the block binding and the
 * permalink filter go through here so the two cannot drift apart.
 *
 * `base_url` lives on an ACF options page registered by the ucsc-2022 theme,
 * not by this plugin, so it is empty whenever that theme is inactive. This
 * returns an empty string in that case and leaves the callers to decide what
 * to do about it — they legitimately differ.
 *
 * @param int $post_id Fund post ID.
 * @return string The escaped Giving URL, or an empty string if no base URL is set.
 */
function ucscgiving_build_fund_url( $post_id ) {
	$baseurl = get_field( 'base_url', 'option' );

	if ( empty( $baseurl ) ) {
		return '';
	}

	$designation = get_post_meta( $post_id, 'designation', true );

	return esc_url( $baseurl . $designation );
}

/**
 * Get Fund URL
 *
 * Callback function that returns the Fund URL
 *
 * @return string
 */
function ucscgiving_fund_url() {
	return ucscgiving_build_fund_url( get_the_ID() );
}

/**
 * Set permalinks to the external Giving URL
 *
 * Funds with a "Standard" fund type link out to the external giving site
 * rather than to their own single post. Everything else is left untouched.
 *
 * Fund type is read straight from the `fund-type` taxonomy, which is the only
 * place it is stored. It used to be read through the ACF field
 * `fund-type-term`, which resolved through the taxonomy anyway because the
 * field had `load_terms` on — the field was removed in #3 so that exactly one
 * system owns the value.
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
	// has_term() matches on name, slug or term ID, so the "Standard" term
	// still matches if its slug and name drift apart.
	if ( ! has_term( 'Standard', 'fund-type', $post->ID ) ) {
		return $post_link;
	}

	$fund_url = ucscgiving_build_fund_url( $post->ID );

	// No base URL means nothing to link out to, so keep the real permalink
	// rather than sending visitors to a truncated address.
	if ( '' === $fund_url ) {
		return $post_link;
	}

	return $fund_url;
}

add_filter( 'post_type_link', 'ucscgiving_link_filter', 10, 2 );

/**
 * Keep a fund to a single fund type
 *
 * A fund is either Standard or Priority, never both. The block editor is held
 * to that by lib/js/fund-type-radio.js, but quick edit and bulk edit still
 * render checkboxes, and REST clients, WP-CLI and imports bypass the admin
 * entirely. This enforces the invariant where none of them can get around it.
 *
 * This is a normaliser over the one store that owns fund type, not a third
 * writer arbitrating between two of them — the arrangement #3 removed and
 * PR #21 was closed for reproducing.
 *
 * `set_object_terms` is the hook rather than `save_post` because
 * WP_REST_Posts_Controller::update_item() runs wp_update_post() — and so
 * `save_post` — *before* handle_terms() writes the terms. This one fires from
 * inside wp_set_object_terms() itself, so it sees every write path alike.
 *
 * That does mean the corrective write re-enters this function. No reentrancy
 * flag is needed: the write always sets exactly one term, and both count
 * guards below bail on one term, so the second pass stops there. Those guards
 * are load-bearing, not just an early-out — weaken both and this recurses
 * until PHP runs out of memory.
 *
 * @param int    $object_id Object ID.
 * @param array  $terms     Term IDs or slugs that were set.
 * @param array  $tt_ids    Term taxonomy IDs that were set.
 * @param string $taxonomy  Taxonomy slug.
 * @return void
 */
function ucscgiving_enforce_single_fund_type( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'fund-type' !== $taxonomy || count( $tt_ids ) < 2 ) {
		return;
	}

	$term_ids = wp_get_object_terms( $object_id, 'fund-type', array( 'fields' => 'ids' ) );

	// A WP_Error is not an array, so this covers the failure case too.
	if ( ! is_array( $term_ids ) || count( $term_ids ) < 2 ) {
		return;
	}

	// Which term survives is arbitrary but must be deterministic. With the
	// radio in place nothing should reach this; the point is only that a fund
	// is never left in both states.
	$keep = (int) end( $term_ids );

	wp_set_object_terms( $object_id, array( $keep ), 'fund-type', false );
}

add_action( 'set_object_terms', 'ucscgiving_enforce_single_fund_type', 10, 4 );

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
