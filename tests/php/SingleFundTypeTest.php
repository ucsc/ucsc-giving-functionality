<?php
/**
 * Tests for ucscgiving_enforce_single_fund_type().
 *
 * @package ucsc-giving-functionality
 */

use PHPUnit\Framework\TestCase;

/**
 * Covers the invariant that a fund carries at most one fund-type term.
 *
 * The block editor is held to this by lib/js/fund-type-radio.js, which has no
 * test harness. These cover the server-side normaliser, which is what quick
 * edit, bulk edit, REST clients and WP-CLI actually hit.
 */
class SingleFundTypeTest extends TestCase {

	/**
	 * Reset the WordPress stubs before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		UCSCGiving_Test_State::reset();
	}

	/**
	 * Assign fund-type terms to a post, bypassing the stubbed writer.
	 *
	 * @param array $terms Term ID to term name.
	 * @param int   $post_id Post ID.
	 * @return void
	 */
	private function assign_fund_types( $terms, $post_id = 7 ) {
		UCSCGiving_Test_State::$object_terms[ $post_id . ':fund-type' ] = $terms;
	}

	/**
	 * Two fund types collapse to one.
	 *
	 * @return void
	 */
	public function test_trims_two_fund_types_to_one() {
		$this->assign_fund_types(
			array(
				98 => 'Priority',
				99 => 'Standard',
			)
		);

		ucscgiving_enforce_single_fund_type( 7, array( 98, 99 ), array( 98, 99 ), 'fund-type' );

		$this->assertSame(
			array( 99 ),
			array_keys( UCSCGiving_Test_State::$object_terms['7:fund-type'] )
		);
	}

	/**
	 * The surviving term is the last one, deterministically.
	 *
	 * Which term wins is arbitrary; that it is always the same one is not.
	 *
	 * @return void
	 */
	public function test_keeps_the_last_term_and_writes_once() {
		$this->assign_fund_types(
			array(
				98 => 'Priority',
				99 => 'Standard',
			)
		);

		ucscgiving_enforce_single_fund_type( 7, array( 98, 99 ), array( 98, 99 ), 'fund-type' );

		$this->assertCount( 1, UCSCGiving_Test_State::$term_writes );
		$this->assertSame(
			array( 7, array( 99 ), 'fund-type', false ),
			UCSCGiving_Test_State::$term_writes[0]
		);
	}

	/**
	 * The corrective write must settle rather than cascade.
	 *
	 * The write re-enters the normaliser, because the stubbed
	 * wp_set_object_terms() fires the hook the way core does. It terminates
	 * because that write sets exactly one term, which both count guards bail
	 * on. Weaken both and this recurses until PHP exhausts memory — which is
	 * why this asserts the write count rather than just the end state.
	 *
	 * @return void
	 */
	public function test_does_not_recurse_on_its_own_write() {
		$this->assign_fund_types(
			array(
				97 => 'Priority',
				98 => 'Standard',
				99 => 'Legacy',
			)
		);

		ucscgiving_enforce_single_fund_type( 7, array( 97, 98, 99 ), array( 97, 98, 99 ), 'fund-type' );

		$this->assertCount( 1, UCSCGiving_Test_State::$term_writes );
	}

	/**
	 * A single fund type is left completely alone.
	 *
	 * The common path must not write to the database at all.
	 *
	 * @return void
	 */
	public function test_leaves_a_single_fund_type_untouched() {
		$this->assign_fund_types( array( 99 => 'Standard' ) );

		ucscgiving_enforce_single_fund_type( 7, array( 99 ), array( 99 ), 'fund-type' );

		$this->assertSame( array(), UCSCGiving_Test_State::$term_writes );
		$this->assertSame(
			array( 99 => 'Standard' ),
			UCSCGiving_Test_State::$object_terms['7:fund-type']
		);
	}

	/**
	 * Removing every fund type is not corrected into one.
	 *
	 * @return void
	 */
	public function test_leaves_a_fund_with_no_type_untouched() {
		ucscgiving_enforce_single_fund_type( 7, array(), array(), 'fund-type' );

		$this->assertSame( array(), UCSCGiving_Test_State::$term_writes );
	}

	/**
	 * Other taxonomies are none of its business.
	 *
	 * Areas and Keywords are legitimately multi-term.
	 *
	 * @return void
	 */
	public function test_ignores_other_taxonomies() {
		UCSCGiving_Test_State::$object_terms['7:area'] = array(
			10 => 'Arts',
			11 => 'Engineering',
		);

		ucscgiving_enforce_single_fund_type( 7, array( 10, 11 ), array( 10, 11 ), 'area' );

		$this->assertSame( array(), UCSCGiving_Test_State::$term_writes );
		$this->assertCount( 2, UCSCGiving_Test_State::$object_terms['7:area'] );
	}

	/**
	 * The normaliser works off the post it is handed, not another one.
	 *
	 * @return void
	 */
	public function test_normalises_only_the_given_post() {
		$this->assign_fund_types(
			array(
				98 => 'Priority',
				99 => 'Standard',
			),
			7
		);
		$this->assign_fund_types(
			array(
				98 => 'Priority',
				99 => 'Standard',
			),
			8
		);

		ucscgiving_enforce_single_fund_type( 7, array( 98, 99 ), array( 98, 99 ), 'fund-type' );

		$this->assertCount( 1, UCSCGiving_Test_State::$object_terms['7:fund-type'] );
		$this->assertCount( 2, UCSCGiving_Test_State::$object_terms['8:fund-type'] );
	}
}
