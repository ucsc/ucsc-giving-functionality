/**
 * Render the Fund Type taxonomy as radio buttons in the block editor.
 *
 * A fund is either Standard or Priority, never both. WordPress renders a
 * hierarchical taxonomy as a checkbox list, which does not express that, so
 * this swaps the control for a single-choice radio via the documented
 * `editor.PostTaxonomyType` filter.
 *
 * The filter is the only supported way in: a taxonomy's `meta_box_cb` is
 * registered with `__back_compat_meta_box => true` unconditionally by core, so
 * the block editor discards it and a custom radio meta box would only ever
 * appear in the classic editor.
 *
 * This is the editor affordance only. `ucscgiving_enforce_single_fund_type()`
 * in lib/functions/general.php enforces the same invariant server-side, where
 * quick edit, bulk edit, REST clients and WP-CLI cannot bypass it.
 *
 * Hand-written against the wp.* globals: the plugin has no build step, so
 * there is no JSX and no ES module syntax here.
 *
 * @package ucsc-giving-functionality
 */

( function ( wp, config ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! wp.element || ! wp.data || ! wp.components || ! config ) {
		return;
	}

	var el = wp.element.createElement;

	// Every term, name-ordered. Only id and name are needed to draw the radios.
	var TERM_QUERY = {
		per_page: -1,
		orderby: 'name',
		order: 'asc',
		_fields: 'id,name',
		context: 'view',
	};

	/**
	 * A single-choice control over the fund-type terms.
	 *
	 * Reads and writes the same editor entity state the default checkbox list
	 * uses, so saving is unchanged — only the affordance differs.
	 *
	 * @return {Object|null} The radio control, or null while terms load.
	 */
	function FundTypeRadio() {
		// rest_base is what the editor keys the post's terms under. It is
		// empty in the taxonomy JSON, so WordPress derives it from the
		// taxonomy name — read it rather than assuming either.
		var taxonomy = wp.data.useSelect( function ( select ) {
			return select( 'core' ).getTaxonomy( config.taxonomy );
		}, [] );

		var terms = wp.data.useSelect( function ( select ) {
			return select( 'core' ).getEntityRecords( 'taxonomy', config.taxonomy, TERM_QUERY );
		}, [] );

		var selected = wp.data.useSelect(
			function ( select ) {
				if ( ! taxonomy ) {
					return null;
				}

				return select( 'core/editor' ).getEditedPostAttribute( taxonomy.rest_base );
			},
			[ taxonomy ]
		);

		var editPost = wp.data.useDispatch( 'core/editor' ).editPost;

		if ( ! taxonomy || ! terms ) {
			return null;
		}

		var options = terms.map( function ( term ) {
			return { label: term.name, value: String( term.id ) };
		} );

		return el( wp.components.RadioControl, {
			__nextHasNoMarginBottom: true,
			label: config.label,
			selected: selected && selected.length ? String( selected[ 0 ] ) : null,
			options: options,
			onChange: function ( value ) {
				var update = {};

				// Always a one-element array. This is what makes the control
				// single-choice as far as the save cycle is concerned.
				update[ taxonomy.rest_base ] = [ parseInt( value, 10 ) ];

				editPost( update );
			},
		} );
	}

	wp.hooks.addFilter(
		'editor.PostTaxonomyType',
		'ucscgiving/fund-type-radio',
		function ( OriginalComponent ) {
			return function ( props ) {
				// Everything that is not fund-type keeps the default control.
				// Note that OriginalComponent is passed through rather than
				// HierarchicalTermSelector being called directly, which the
				// Gutenberg docs warn locks the editor up.
				if ( props.slug !== config.taxonomy ) {
					return el( OriginalComponent, props );
				}

				return el( FundTypeRadio, props );
			};
		}
	);
} )( window.wp, window.ucscgivingFundType );
