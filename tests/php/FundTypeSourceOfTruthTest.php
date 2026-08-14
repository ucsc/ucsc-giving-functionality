<?php
/**
 * Tests pinning fund type to a single source of truth.
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Issue #3 resolved fund type's dual ownership in favour of the `fund-type`
 * taxonomy: the ACF field `fund-type-term` is gone, and `show_ui` is on so the
 * term is edited through WordPress's own taxonomy panel. That leaves
 * handle_terms() as the only writer and the taxonomy as the only store.
 *
 * The resolution lives entirely in `acf-json/`, which the ACF admin UI
 * round-trips rather than anyone editing by hand. Re-adding a taxonomy field
 * for `fund-type` would quietly reinstate the second writer and bring the
 * double-save back with it, so these assertions guard against that.
 */
class FundTypeSourceOfTruthTest extends TestCase {

	/**
	 * Decode one of the plugin's ACF JSON definitions.
	 *
	 * @param string $filename File name within acf-json/.
	 * @return array<string,mixed>
	 */
	private function acf_json( $filename ) {
		$path = dirname( __DIR__, 2 ) . '/acf-json/' . $filename;

		$this->assertFileExists( $path, $filename . ' is missing from acf-json/.' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a file from this repository, not making a request.
		$decoded = json_decode( file_get_contents( $path ), true );

		$this->assertIsArray( $decoded, $filename . ' is not valid JSON.' );

		return $decoded;
	}

	/**
	 * The Fund Type taxonomy definition.
	 *
	 * @return array<string,mixed>
	 */
	private function fund_type_taxonomy() {
		return $this->acf_json( 'taxonomy_67cc736da6678.json' );
	}

	/**
	 * The Fund Details field group.
	 *
	 * @return array<string,mixed>
	 */
	private function fund_details_group() {
		return $this->acf_json( 'group_67b76c100ca57.json' );
	}

	/**
	 * The mechanism: an editable panel is what makes the REST round-trip sane.
	 *
	 * With `show_ui` off, the Block Editor still loaded the post's fund-type
	 * terms into entity state and wrote them back through handle_terms() on
	 * every save, but no control ever updated that state — so it round-tripped
	 * a stale value and raced ACF. Turning the panel on is the fix, not a
	 * cosmetic change.
	 *
	 * @return void
	 */
	public function test_fund_type_taxonomy_has_an_editor_panel() {
		$taxonomy = $this->fund_type_taxonomy();

		$this->assertSame( 1, $taxonomy['show_ui'] );
	}

	/**
	 * REST exposure is what carries the term through the editor's save.
	 *
	 * @return void
	 */
	public function test_fund_type_taxonomy_is_rest_exposed() {
		$taxonomy = $this->fund_type_taxonomy();

		$this->assertSame( 1, $taxonomy['show_in_rest'] );
	}

	/**
	 * A new fund starts as Priority.
	 *
	 * The old ACF radio had `allow_null: 0` and preselected a value; a radio
	 * over the taxonomy shows nothing selected until a term exists. The
	 * taxonomy's own default term restores that, and Priority is the safe
	 * default of the two — it keeps the fund on its own page rather than
	 * sending visitors to an external giving form for a fund that has no
	 * designation code yet.
	 *
	 * The name has to match the existing term exactly. WordPress resolves the
	 * default with term_exists( name, taxonomy ) and creates a **new** term
	 * when it misses, so a typo here silently produces a duplicate.
	 *
	 * @return void
	 */
	public function test_a_new_fund_defaults_to_priority() {
		$taxonomy = $this->fund_type_taxonomy();

		$this->assertSame( '1', $taxonomy['default_term']['default_term_enabled'] );
		$this->assertSame( 'Priority', $taxonomy['default_term']['default_term_name'] );
	}

	/**
	 * The front end still needs the taxonomy public and queryable.
	 *
	 * `taxonomy-fund-type.php` is a registered block template and /fund-type/
	 * archives are publicly reachable.
	 *
	 * @return void
	 */
	public function test_fund_type_taxonomy_is_still_publicly_queryable() {
		$taxonomy = $this->fund_type_taxonomy();

		$this->assertSame( 1, $taxonomy['public'] );
		$this->assertSame( 1, $taxonomy['publicly_queryable'] );
	}

	/**
	 * The whole point: no ACF field may write to `fund-type`.
	 *
	 * A taxonomy field with `save_terms` on is a second writer regardless of
	 * what it is named, so this checks the taxonomy it targets rather than the
	 * old field name.
	 *
	 * @return void
	 */
	public function test_no_acf_field_writes_to_the_fund_type_taxonomy() {
		$group = $this->fund_details_group();

		foreach ( $group['fields'] as $field ) {
			$this->assertNotSame(
				'fund-type',
				$field['taxonomy'] ?? null,
				'Field "' . $field['name'] . '" targets the fund-type taxonomy, reintroducing the second writer #3 removed.'
			);
		}
	}

	/**
	 * The field group keeps the two fields that are still its own.
	 *
	 * Removing `fund-type-term` must not have taken anything else with it —
	 * `designation` in particular feeds ucscgiving_build_fund_url().
	 *
	 * @return void
	 */
	public function test_fund_details_group_keeps_its_remaining_fields() {
		$group = $this->fund_details_group();
		$names = array_column( $group['fields'], 'name' );

		$this->assertSame( array( 'button-text', 'designation' ), $names );
	}
}
